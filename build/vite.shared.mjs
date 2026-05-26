/**
 * Shared Vite utilities for ATUM WordPress plugins (Vite 8 / Rolldown).
 */

import crypto from 'crypto';
import fs from 'fs';
import path from 'path';

/**
 * Short, stable cache-busting token derived from the asset's own content.
 * Replaces a build timestamp so unchanged assets keep their browser cache
 * across releases (and identical content always yields the same version).
 *
 * @param {string|Uint8Array} content
 * @returns {string}
 */
function contentVersion( content ) {
	return crypto
		.createHash( 'sha256' )
		.update( content ?? '' )
		.digest( 'hex' )
		.slice( 0, 12 );
}

/**
 * Wrap a UMD script so its top-level global is captured under a **private**
 * namespace and the original `window[capture]` is restored after load. Lets
 * ATUM ship its own copy of a third-party library without colliding with
 * other WordPress plugins that may ship a different version of the same lib.
 *
 *   wrapVendor(code, 'Chart', 'atumChart')
 *     → window.atumChart === Chart, window.Chart === (prev or undefined)
 *
 * @param {string} code     Raw vendor source (UMD).
 * @param {string} capture  Global the UMD sets on `window` (e.g. 'Chart').
 * @param {string} expose   Private namespace to publish under (e.g. 'atumChart').
 * @returns {string}
 */
export function wrapVendor( code, capture, expose ) {
	return (
		`/*! ATUM-wrapped: window.${ expose } (window.${ capture } restored). */\n`
		+ '(function () {\n'
		+ `var __atum_prev = window.${ capture };\n`
		+ code + '\n'
		+ `;window.${ expose } = window.${ capture };\n`
		+ `window.${ capture } = __atum_prev;\n`
		+ '})();\n'
	);
}

const STATIC_EXTERNALS = {
	'jquery'                        : { global: 'window.jQuery', handle: 'jquery' },
	'$'                             : { global: 'window.jQuery', handle: 'jquery' },
	'sweetalert2'                   : { global: 'window.Swal', handle: 'atum-sweetalert2' },
	'sweetalert2-neutral'           : { global: 'window.Swal', handle: 'atum-sweetalert2' },
	'moment'                         : { global: 'window.moment', handle: 'moment' },
	'moment/min/moment-with-locales.min': { global: 'window.moment', handle: 'moment' },
	/*
	 * Keep the heavy Chart.js UMD out of atum-dashboard.js and load it as a
	 * WordPress-registered script (handle `atum-chartjs`).
	 *
	 * The vendor file is wrapped at build time to expose Chart under the
	 * **private** `window.atumChart` namespace and restore `window.Chart` to
	 * whatever it was before, so a third-party plugin shipping its own Chart
	 * version cannot collide with ours (and vice versa).
	 */
	'chart.js/auto': { global: 'window.atumChart', handle: 'atum-chartjs' },

	/*
	 * Bootstrap 5: shipped as a single isolated UMD (window.atumBootstrap)
	 * loaded under the `atum-bootstrap` handle. Submodule imports resolve to
	 * specific classes off that namespace.
	 */
	'bootstrap/js/dist/tooltip': { global: 'window.atumBootstrap.Tooltip', handle: 'atum-bootstrap' },
	'bootstrap/js/dist/popover': { global: 'window.atumBootstrap.Popover', handle: 'atum-bootstrap' },
	'bootstrap/js/dist/collapse': { global: 'window.atumBootstrap.Collapse', handle: 'atum-bootstrap' },

	// Intro.js — isolated as window.atumIntroJs.
	'intro.js/minified/intro.min': { global: 'window.atumIntroJs', handle: 'atum-introjs' },

	// Hammer.js — isolated as window.atumHammer.
	'hammerjs/hammer.min': { global: 'window.atumHammer', handle: 'atum-hammer' },
};

export function getExternalMapping( specifier ) {
	if ( STATIC_EXTERNALS[ specifier ] ) {
		return STATIC_EXTERNALS[ specifier ];
	}

	if ( specifier.startsWith( '@wordpress/' ) ) {
		const name = specifier.replace( '@wordpress/', '' );
		const camelName = name.replace( /-([a-z])/g, ( g ) => g[ 1 ].toUpperCase() );

		return { global: `wp.${ camelName }`, handle: `wp-${ name }` };
	}

	return null;
}

function transformWordPressImports( code ) {
	const importPattern = /import\s+(?:([\w$]+)\s*,\s*)?(?:\*\s+as\s+([\w$]+)|\{([^}]+)\}|([\w$]+))\s*from\s*["']([^"']+)["'];?/g;

	return code.replace( importPattern, ( match, combinedDefault, namespace, named, defaultOnly, specifier ) => {
		const mapping = getExternalMapping( specifier );

		if ( !mapping ) {
			return match;
		}

		const g = mapping.global;
		let result = '';

		if ( combinedDefault ) {
			result += `var ${ combinedDefault } = ${ g };`;
		}

		if ( namespace ) {
			result += `var ${ namespace } = ${ g };`;
		}
		else if ( named ) {
			const imports = named
				.split( ',' )
				.map( ( s ) => s.trim() )
				.filter( Boolean )
				.map( ( s ) => s.replace( /^(\S+)\s+as\s+(\S+)$/, '$1: $2' ) );

			result += `var { ${ imports.join( ', ' ) } } = ${ g };`;
		}
		else if ( defaultOnly ) {
			result += `var ${ defaultOnly } = ${ g };`;
		}

		return result || match;
	} );
}

export function isWordPressExternal( id ) {
	return getExternalMapping( id ) !== null;
}

const GLOBAL_SHIM_PREFIX = '\0wpglobal:';

/**
 * External mapping for a (possibly already shim-resolved) module id.
 *
 * @param {string} id
 * @returns {object|null}
 */
function mappingForModuleId( id ) {
	const real = id.startsWith( GLOBAL_SHIM_PREFIX )
		? id.slice( GLOBAL_SHIM_PREFIX.length )
		: id;

	return getExternalMapping( real );
}

/**
 * Production build: resolve every mapped external (jquery, sweetalert2,
 * moment, chart.js, @wordpress/*) to a tiny virtual ES module that re-exports
 * the runtime global as a real `default` export.
 *
 * This is deliberately NOT Rollup `external` + `output.globals`: an external
 * default import goes through interop and compiles to `global.default`
 * (undefined for `window.jQuery` → "$ is not a function"). A genuine ESM
 * `export default` has no interop wrapper, so `import $ from 'jquery'` and the
 * identifiers injected by @rollup/plugin-inject both bind to the callable
 * global directly.
 */
export function wordpressGlobalsShimPlugin() {
	return {
		name   : 'atum-wordpress-globals-shim',
		/*
		 * Runs in BOTH `serve` and `build`. In dev it's the only thing that
		 * can resolve `import 'chart.js/auto'` and friends
		 * (Vite's normal resolver chokes on the extensionless deep path);
		 * without it the dashboard JS entry fails to load and the page
		 * renders empty.
		 *
		 * `pre`: prepend the jQuery imports on the raw source, before Vite's
		 * TS/oxc transform — otherwise the transform hook is skipped for .ts.
		 */
		enforce: 'pre',
		resolveId( id ) {
			return getExternalMapping( id ) ? GLOBAL_SHIM_PREFIX + id : null;
		},
		load( id ) {
			if ( !id.startsWith( GLOBAL_SHIM_PREFIX ) ) {
				return null;
			}

			const mapping = getExternalMapping( id.slice( GLOBAL_SHIM_PREFIX.length ) );

			return `const __wpGlobal = ${ mapping.global };\nexport default __wpGlobal;`;
		},
		/*
		 * ProvidePlugin equivalent. ATUM source uses jQuery as free globals
		 * (`jQuery( ($) => …)`) the way Webpack's ProvidePlugin supplied them.
		 * @rollup/plugin-inject does NOT run reliably under Rolldown-Vite, so
		 * we deterministically prepend the imports ourselves (resolved by the
		 * shim above to `window.jQuery`). Skipped when the module already
		 * imports from 'jquery' (avoids a duplicate binding).
		 */
		transform( code, id ) {
			if (
				id.startsWith( GLOBAL_SHIM_PREFIX )
				|| id.includes( 'node_modules' )
				|| !/\.(tsx?|jsx?|mjs)$/.test( id )
				|| /from\s*['"]jquery['"]/.test( code )
			) {
				return null;
			}

			const usesJQuery = /\bjQuery\b/.test( code );
			const usesDollar = /(^|[^\w$.])\$(?![\w$])/.test( code );

			if ( !usesJQuery && !usesDollar ) {
				return null;
			}

			let prologue = '';

			if ( usesDollar ) {
				prologue += "import $ from 'jquery';\n";
			}

			if ( usesJQuery ) {
				prologue += "import jQuery from 'jquery';\n";
			}

			return { code: prologue + code, map: null };
		},
	};
}

/**
 * Dev-only plugin: for each JS entry that has a matching SCSS entry by slug
 * (`assets/js/src/dashboard.ts` ↔ `assets/scss/atum-dashboard.scss`), prepend
 * an `import` of the SCSS into the JS source. Vite then handles the SCSS as
 * a CSS module — compiled on the fly, injected as a `<style>` tag with HMR
 * tracking — so partial edits hot-reload without a page refresh.
 *
 * Production builds ignore this plugin (`apply: 'serve'`): the CSS keeps its
 * own per-entry build via `build/build.mjs` and is enqueued by PHP.
 *
 * @param {object} opts
 * @param {Record<string, string>} opts.entries Discovered entries (both js/ and css/).
 */
export function atumDevScssAutoImportPlugin( { entries, aliases = {} } ) {
	/*
	 * Manual JS-slug → CSS-slug aliases for cases where the two entries don't
	 * share a name. Default covers `atum-list-tables.js` (Stock Central JS)
	 * → `atum-list.css` (Stock Central CSS), the only mismatch in the base
	 * plugin today. Addons can pass extras via `createAtumViteConfig`.
	 */
	const slugAliases = {
		'atum-list-tables': 'atum-list',
		...aliases,
	};

	// Build a quick lookup: absolute JS source path → absolute SCSS source path.
	const jsSlugByPath = {};
	const cssPathBySlug = {};

	for ( const [ key, src ] of Object.entries( entries ) ) {
		if ( key.startsWith( 'js/' ) ) {
			jsSlugByPath[ src ] = key.slice( 3 ); // 'atum-dashboard'
		}
		else if ( key.startsWith( 'css/' ) ) {
			cssPathBySlug[ key.slice( 4 ) ] = src;
		}
	}

	const jsToScss = {};

	for ( const [ jsPath, jsSlug ] of Object.entries( jsSlugByPath ) ) {
		const cssSlug = slugAliases[ jsSlug ] || jsSlug;

		if ( cssPathBySlug[ cssSlug ] ) {
			jsToScss[ jsPath ] = cssPathBySlug[ cssSlug ];
		}
	}

	return {
		name   : 'atum-dev-scss-auto-import',
		apply  : 'serve',
		enforce: 'pre',
		transform( code, id ) {
			const cleanId = id.split( '?' )[ 0 ].split( '#' )[ 0 ];
			const scssPath = jsToScss[ cleanId ];

			if ( !scssPath ) {
				return null;
			}

			// Avoid double-inject if our previous run already added it.
			if ( code.includes( scssPath ) ) {
				return null;
			}

			return {
				code: `import ${ JSON.stringify( scssPath ) };\n${ code }`,
				map : null,
			};
		},
	};
}

export function wordpressExternalsPlugin() {
	let isServe = false;

	return {
		name   : 'atum-wordpress-externals',
		enforce: 'pre',
		config( config, { command } ) {
			isServe = command === 'serve';
		},
		resolveId( id ) {
			if ( !isServe ) {
				return null;
			}

			if ( getExternalMapping( id ) ) {
				return { id, external: true };
			}

			return null;
		},
		transform( code, id ) {
			if ( !/\.(js|jsx|ts|tsx)$/.test( id ) || id.includes( 'node_modules' ) ) {
				return null;
			}

			if ( !code.includes( ' from ' ) ) {
				return null;
			}

			const transformed = transformWordPressImports( code );

			if ( transformed !== code ) {
				return { code: transformed, map: null };
			}

			return null;
		},
	};
}

/**
 * Rollup `output.globals` resolver: maps an external specifier to the global
 * expression that the IIFE bundle references at runtime (e.g. `window.jQuery`).
 *
 * @returns {(id: string) => string|undefined}
 */
export function getExternalGlobals() {
	return ( id ) => {
		const mapping = getExternalMapping( id );

		return mapping ? mapping.global : undefined;
	};
}

/**
 * Walk an entry chunk's module graph and collect the WordPress script handles
 * for every external specifier it imports (static or dynamic, at any depth).
 *
 * Reliable because externals are resolved as external by Rollup, so their
 * specifier survives in each module's `importedIds`/`dynamicallyImportedIds`
 * regardless of how the code is later transformed/wrapped.
 *
 * @param {object} ctx   Rollup plugin context (`this` inside generateBundle).
 * @param {object} chunk Entry chunk.
 * @returns {string[]} Sorted, de-duplicated handles.
 */
function collectEntryExternalHandles( ctx, chunk ) {
	const handles = new Set();
	const seen = new Set();
	const queue = [ ...( chunk.moduleIds || [] ) ];

	while ( queue.length ) {
		const id = queue.shift();

		if ( seen.has( id ) ) {
			continue;
		}

		seen.add( id );

		const mapping = mappingForModuleId( id );

		if ( mapping ) {
			handles.add( mapping.handle );
			continue;
		}

		const info = ctx.getModuleInfo?.( id );

		if ( !info ) {
			continue;
		}

		for ( const dep of [
			...( info.importedIds || [] ),
			...( info.dynamicallyImportedIds || [] ),
		] ) {
			const depMapping = mappingForModuleId( dep );

			if ( depMapping ) {
				handles.add( depMapping.handle );
			}
			else if ( !seen.has( dep ) ) {
				queue.push( dep );
			}
		}
	}

	return [ ...handles ].sort();
}

/**
 * Emits `<name>.asset.php` next to each JS entry and CSS file, and removes the
 * empty JS stub Rollup generates for `.scss` entry points.
 *
 * JS dependencies are derived from the entry's external module graph (best
 * effort); the PHP layer (`Helpers::get_asset_dependencies`) unions these with
 * declared fallbacks so a partial detection can never drop a dependency.
 */
export function wordpressAssetPhpPlugin() {
	return {
		name : 'atum-wordpress-asset-php',
		apply: 'build',
		generateBundle( options, bundle ) {
			for ( const [ fileName, chunk ] of Object.entries( bundle ) ) {
				// Drop the empty JS stub Rollup emits for SCSS-only entries.
				if (
					chunk.type === 'chunk'
					&& chunk.isEntry
					&& typeof chunk.facadeModuleId === 'string'
					&& /\.s?css$/.test( chunk.facadeModuleId )
				) {
					delete bundle[ fileName ];
					continue;
				}

				if ( chunk.type === 'chunk' && chunk.isEntry && fileName.endsWith( '.js' ) ) {
					const deps = collectEntryExternalHandles( this, chunk );
					const depsArray = deps.map( ( d ) => `'${ d }'` ).join( ', ' );
					const version = contentVersion( chunk.code );
					const assetPhp = `<?php return array('dependencies' => array(${ depsArray }), 'version' => '${ version }');`;

					this.emitFile( {
						type    : 'asset',
						fileName: fileName.replace( /\.js$/, '.asset.php' ),
						source  : assetPhp,
					} );
				}

				if ( chunk.type === 'asset' && fileName.endsWith( '.css' ) ) {
					const version = contentVersion( chunk.source );
					const assetPhp = `<?php return array('dependencies' => array(), 'version' => '${ version }');`;

					this.emitFile( {
						type    : 'asset',
						fileName: fileName.replace( /\.css$/, '.asset.php' ),
						source  : assetPhp,
					} );
				}
			}
		},
	};
}

export function viteWordPressServerPlugin( { base, entries, pluginRoot } ) {
	return {
		name: 'atum-vite-wordpress-server',
		configureServer( server ) {
			server.middlewares.use( '/vite-wordpress.json', ( req, res ) => {
				const buildMap = {};

				for ( const [ outputName, srcPath ] of Object.entries( entries ) ) {
					/*
					 * Pick the destination extension by entry kind:
					 *   - `js/atum-X`  → `js/atum-X.js`
					 *   - `css/atum-X` → `css/atum-X.css`
					 *
					 * Used by mrottow/vite-wordpress to rewrite the WP-registered
					 * asset URLs to the corresponding source in the dev server.
					 */
					const isCss = outputName.startsWith( 'css/' );
					const outputFile = isCss ? `${ outputName }.css` : `${ outputName }.js`;
					const src = pluginRoot
						? path.relative( pluginRoot, srcPath ).split( path.sep ).join( '/' )
						: srcPath;

					buildMap[ outputFile ] = {
						file   : outputFile,
						src,
						isEntry: true,
					};
				}

				const config = {
					/*
					 * `base` MUST NOT have a trailing slash. mrottow's
					 * `get_file_name()` does `explode("{base}/{outDir}/", $url)`;
					 * if base ends in `/` the separator becomes `//dist/`
					 * (double slash) and never matches the real asset URL —
					 * URL rewriting silently fails and HMR does nothing.
					 */
					base  : base.replace( /\/+$/, '' ),
					outDir: 'dist',
					srcDir: '.',
					// SCSS sources for the file-system fallback (.css → .scss).
					css   : 'scss',
					buildMap,
				};

				res.setHeader( 'Content-Type', 'application/json' );
				res.setHeader( 'Access-Control-Allow-Origin', '*' );
				res.end( JSON.stringify( config ) );
			} );
		},
	};
}

export function devImageServerPlugin( { assetsDir, basePath } ) {
	return {
		name: 'atum-dev-image-server',
		configureServer( server ) {
			server.middlewares.use( ( req, res, next ) => {
				const imgMatch = req.url?.match(
					new RegExp( `(?:${ basePath.replace( /\//g, '\\/' ) })?/assets/(img|images)/(.+)` ),
				);

				if ( imgMatch ) {
					const imgFolder = imgMatch[ 1 ];
					const imgFile = imgMatch[ 2 ];
					const imgPath = path.join( assetsDir, imgFolder, imgFile );

					if ( fs.existsSync( imgPath ) ) {
						const ext = path.extname( imgPath ).toLowerCase();
						const mimeTypes = {
							'.svg' : 'image/svg+xml',
							'.png' : 'image/png',
							'.jpg' : 'image/jpeg',
							'.jpeg': 'image/jpeg',
							'.gif' : 'image/gif',
							'.webp': 'image/webp',
							'.ico' : 'image/x-icon',
						};

						res.setHeader( 'Content-Type', mimeTypes[ ext ] || 'application/octet-stream' );
						res.setHeader( 'Access-Control-Allow-Origin', '*' );
						fs.createReadStream( imgPath ).pipe( res );

						return;
					}
				}

				next();
			} );
		},
	};
}

export function copyDirSync( src, dest ) {
	if ( !fs.existsSync( src ) ) {
		return;
	}

	fs.mkdirSync( dest, { recursive: true } );
	for ( const entry of fs.readdirSync( src, { withFileTypes: true } ) ) {
		const srcPath = path.join( src, entry.name );
		const destPath = path.join( dest, entry.name );

		if ( entry.isDirectory() ) {
			copyDirSync( srcPath, destPath );
		}
		else {
			fs.copyFileSync( srcPath, destPath );
		}
	}
}

function removeEmptyParentsUntil( startDir, stopDir ) {
	let current = startDir;
	const stop = path.resolve( stopDir );

	while ( path.resolve( current ).startsWith( stop ) && path.resolve( current ) !== stop ) {
		if ( !fs.existsSync( current ) || fs.readdirSync( current ).length ) {
			break;
		}

		fs.rmdirSync( current );
		current = path.dirname( current );
	}
}

export function wordpressPostBuildPlugin( options = {} ) {
	const {
		displayName = 'ATUM',
		copyDirs = [],
		cssBanner = '',
		cssReplacements = [],
		deletePaths = [],
	} = options;

	return {
		name       : 'atum-wordpress-post-build',
		closeBundle: {
			sequential: true,
			async handler() {
				console.log( `\n📦 ${ displayName } post-build...` );

				if ( cssBanner && fs.existsSync( 'dist/css' ) ) {
					for ( const file of fs.readdirSync( 'dist/css' ) ) {
						if ( !file.endsWith( '.css' ) ) {
							continue;
						}

						const cssPath = path.join( 'dist/css', file );
						const contents = fs.readFileSync( cssPath, 'utf-8' );

						if ( !contents.startsWith( '/**' ) ) {
							fs.writeFileSync( cssPath, cssBanner + contents );
						}
					}
				}

				if ( cssReplacements.length && fs.existsSync( 'dist/css' ) ) {
					for ( const file of fs.readdirSync( 'dist/css' ) ) {
						if ( !file.endsWith( '.css' ) ) {
							continue;
						}

						const cssPath = path.join( 'dist/css', file );
						let contents = fs.readFileSync( cssPath, 'utf-8' );

						for ( const { search, replace } of cssReplacements ) {
							contents = 'string' === typeof search
								? contents.split( search ).join( replace )
								: contents.replace( search, replace );
						}

						fs.writeFileSync( cssPath, contents );
					}
				}

				for ( const { src, dest, label } of copyDirs ) {
					if ( fs.existsSync( src ) ) {
						copyDirSync( src, dest );
						console.log( `  ✓ ${ label }` );
					}
				}

				for ( const deletePath of deletePaths ) {
					const target = path.resolve( deletePath );

					if ( fs.existsSync( target ) ) {
						const stat = fs.statSync( target );

						fs.rmSync( target, { recursive: true, force: true } );

						if ( stat.isFile() ) {
							removeEmptyParentsUntil( path.dirname( target ), path.resolve( 'dist' ) );
						}
					}
				}

				console.log( `✅ ${ displayName } post-build complete\n` );
			},
		},
	};
}

export function getWordPressOptimizeDeps() {
	return {
		exclude: [ '@wordpress/*', ...Object.keys( STATIC_EXTERNALS ) ],
	};
}

export function getWordPressCssConfig() {
	return {
		devSourcemap       : true,
		preprocessorOptions: {
			scss: {
				includePaths        : [ 'assets/scss' ],
				silenceDeprecations : [ 'import' ],
			},
		},
	};
}

export function getWordPressServerConfig( port ) {
	return {
		host      : 'localhost',
		port,
		strictPort: true,
		cors      : true,
		/*
		 * HTTPS via @vitejs/plugin-basic-ssl (added in createAtumViteConfig).
		 * `origin` must match so HMR + asset URLs all use the same scheme.
		 */
		origin    : `https://localhost:${ port }`,
		watch     : {
			/*
			 * IDEs with "safe write" (PHPStorm/IntelliJ family, by default)
			 * save edits as `write-tmp → rename`; chokidar's fsevents on macOS
			 * sometimes misses that pattern and HMR never fires. Polling
			 * sidesteps the issue and works with any editor. Modest CPU cost
			 * (~0.5%/file) acceptable in dev.
			 */
			usePolling: true,
			interval  : 300,
		},
	};
}

function getOutputBaseName( assetInfo ) {
	const name = assetInfo.name || '';
	const parts = name.split( '/' );

	return parts[ parts.length - 1 ];
}

export function getWordPressRollupOutput() {
	return {
		entryFileNames: ( chunkInfo ) => {
			const name = chunkInfo.name || '';
			const base = name.includes( '/' ) ? name.split( '/' ).pop() : name;

			return `js/${ base }.js`;
		},
		chunkFileNames: 'js/chunks/[name]-[hash].js',
		assetFileNames: ( assetInfo ) => {
			const base = getOutputBaseName( assetInfo );

			if ( /\.(png|jpe?g|gif|svg|webp|ico)$/i.test( base ) ) {
				return `images/${ base }`;
			}

			if ( /\.(woff2?|ttf|otf|eot)$/i.test( base ) ) {
				return `fonts/${ base }`;
			}

			if ( base.endsWith( '.css' ) || assetInfo.names?.some( ( n ) => n.endsWith( '.css' ) ) ) {
				const cssName = base.endsWith( '.css' ) ? base : `${ base }.css`;

				return `css/${ cssName }`;
			}

			/*
			 * `base` already carries the original extension under Rolldown's
			 * `[name]` semantics — appending `[extname]` would double it
			 * (e.g. `foo.ttf.ttf`).
			 */
			return `assets/${ base }`;
		},
	};
}

export function getWordPressBase( basePath, command ) {
	return command === 'serve' ? basePath : './';
}

export function getWordPressResolveConfig( { pluginRoot } ) {
	return {
		alias: {
			'@img': path.join( pluginRoot, 'assets/images' ),
		},
	};
}

export function getWordPressBuildConfig( options = {} ) {
	return {
		outDir           : 'dist',
		manifest         : true,
		sourcemap        : false,
		minify           : true,
		cssMinify        : true,
		target           : 'es2020',
		assetsInlineLimit: 0,
		copyPublicDir    : false,
		emptyOutDir      : true,
		...options,
	};
}
