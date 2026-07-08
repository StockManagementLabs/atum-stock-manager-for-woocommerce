/**
 * ATUM production build (Vite 8 / Rolldown).
 *
 * Vite/Rollup cannot emit `format: 'iife'` with multiple inputs in a single
 * build, and a single ESM build with code-splitting produces entry files with
 * top-level `import` — invalid inside the classic `<script>` tags WordPress
 * enqueues. So we build **one self-contained IIFE bundle per JS entry**
 * (externals referenced as globals) and a **single CSS build** for all SCSS
 * entries. JS entries build in a bounded-concurrency pool. Output goes to
 * `dist/` exactly like the dev/serve config expects.
 *
 * For local development use `bun run dev` (HMR) — there is no build watch mode.
 *
 * Usage: `bun build/build.mjs`
 */

import fs from 'fs';
import os from 'os';
import path from 'path';
import { pathToFileURL, fileURLToPath } from 'url';
import { build, transformWithOxc } from 'vite';

import { resolveAtumOptions } from './create-atum-vite-config.mjs';
import { getVendorAssets as getBaseVendorAssets } from './vendor-assets.mjs';
import {
	wordpressAssetPhpPlugin,
	wordpressPostBuildPlugin,
	wordpressGlobalsShimPlugin,
	wrapVendor,
	getWordPressOptimizeDeps,
	getWordPressCssConfig,
	getWordPressResolveConfig,
	getWordPressRollupOutput,
} from './vite.shared.mjs';

const JS_BUILD_CONCURRENCY = Math.max( 2, Math.min( 6, os.cpus().length ) );

/**
 * Load addon/base build options from a Vite config module.
 *
 * Addons keep their build-specific values beside the Vite serve config and
 * export them as `atumBuildOptions`. The older/addon-specific names are
 * accepted so existing wrappers can be simplified incrementally.
 *
 * @param {string} configPath
 * @returns {Promise<object>}
 */
async function loadBuildOptionsFromConfig( configPath ) {
	const absolutePath = path.resolve( process.cwd(), configPath );
	const configModule = await import( pathToFileURL( absolutePath ).href );
	const options = configModule.atumBuildOptions
		?? configModule.atumAddonOptions
		?? configModule.atumMiOptions
		?? configModule.atumBaseOptions;

	if ( !options || typeof options !== 'object' ) {
		throw new Error(
			`No ATUM build options export found in ${ absolutePath }. `
			+ 'Expected `atumBuildOptions`.',
		);
	}

	return options;
}

/**
 * Run `worker` over `items` with at most `limit` in flight at once.
 *
 * @template T
 * @param {T[]} items
 * @param {number} limit
 * @param {(item: T, index: number) => Promise<void>} worker
 */
async function runPool( items, limit, worker ) {
	let cursor = 0;
	const runners = Array.from(
		{ length: Math.min( limit, items.length ) },
		async () => {
			while ( cursor < items.length ) {
				const index = cursor++;

				await worker( items[ index ], index );
			}
		},
	);

	await Promise.all( runners );
}

/**
 * Safe JS identifier for the IIFE wrapper name (unused at runtime, but Rollup
 * requires `output.name` for the `iife` format).
 *
 * @param {string} base
 * @returns {string}
 */
function iifeName( base ) {
	return `atum_${ base.replace( /[^a-zA-Z0-9_$]/g, '_' ) }`;
}

/**
 * Write a Vite-format manifest covering every entry so the PHP dev-server
 * gate (`ViteDevServer` → `mrottow/vite-wordpress`) finds a valid manifest and
 * the "Vite stopped → serve built dist" fallback keeps working.
 *
 * @param {object} resolved Output of `resolveAtumOptions`.
 */
function writeManifest( resolved ) {
	const { pluginRoot, entries } = resolved;
	const manifest = {};

	for ( const [ key, src ] of Object.entries( entries ) ) {
		const base = key.split( '/' ).pop();
		const isCss = key.startsWith( 'css/' );
		const rel = path.relative( pluginRoot, src ).split( path.sep ).join( '/' );

		manifest[ rel ] = {
			file   : isCss ? `css/${ base }.css` : `js/${ base }.js`,
			name   : base,
			src    : rel,
			isEntry: true,
		};
	}

	const viteDir = path.join( pluginRoot, 'dist', '.vite' );

	fs.mkdirSync( viteDir, { recursive: true } );
	fs.writeFileSync(
		path.join( viteDir, 'manifest.json' ),
		JSON.stringify( manifest, null, 2 ),
	);
}

/**
 * Resolve the vendor manifest for `pluginRoot`. If the plugin ships its own
 * `build/vendor-assets.mjs`, load it dynamically (addons declare their own
 * vendors there). Otherwise fall back to the base manifest.
 *
 * @param {string} pluginRoot
 * @returns {Promise<Array<object>>}
 */
async function resolveVendorAssets( pluginRoot ) {
	const local = path.join( pluginRoot, 'build', 'vendor-assets.mjs' );

	if ( !fs.existsSync( local ) ) {
		return getBaseVendorAssets( pluginRoot );
	}

	let mod;

	try {
		mod = await import( pathToFileURL( local ).href );
	}
	catch ( err ) {
		throw new Error(
			`Failed to load addon vendor manifest at ${ local }: ${ err.message }`,
		);
	}

	if ( typeof mod.getVendorAssets !== 'function' ) {
		throw new Error(
			`Addon vendor manifest at ${ local } does not export `
			+ '`getVendorAssets(pluginRoot)` function.',
		);
	}

	return mod.getVendorAssets( pluginRoot );
}

/**
 * Ship every entry from the resolved vendor manifest to `dist/vendor/`.
 * Minifies non-`.min` sources via Oxc and applies the isolation wrapper when
 * the entry declares `isolate: { capture, expose }`.
 *
 * @param {string} pluginRoot
 */
async function shipVendorAssets( pluginRoot ) {
	const jsVendorDir  = path.join( pluginRoot, 'dist', 'js', 'vendor' );
	const cssVendorDir = path.join( pluginRoot, 'dist', 'css', 'vendor' );

	fs.mkdirSync( jsVendorDir, { recursive: true } );
	fs.mkdirSync( cssVendorDir, { recursive: true } );

	const assets = await resolveVendorAssets( pluginRoot );

	for ( const asset of assets ) {
		if ( !fs.existsSync( asset.src ) ) {
			console.warn( `  ⚠ vendor source missing, skipped: ${ path.relative( pluginRoot, asset.src ) }` );
			continue;
		}

		const kind = asset.kind === 'css' ? 'css' : 'js';
		let code   = fs.readFileSync( asset.src, 'utf8' );

		if ( asset.minify ) {
			/*
			 * Oxc minify (Vite 8). Conservative: keeps global IIFE vendor
			 * scripts intact (no tree-shaking, unlike `minifySync`).
			 */
			const result = await transformWithOxc( code, asset.dest, { minify: true } );

			code = result.code;
		}

		if ( asset.isolate ) {
			code = wrapVendor( code, asset.isolate.capture, asset.isolate.expose );
		}

		const destDir = kind === 'css' ? cssVendorDir : jsVendorDir;

		fs.writeFileSync( path.join( destDir, asset.dest ), code );

		const tag = asset.isolate
			? `(isolated → window.${ asset.isolate.expose })`
			: asset.minify ? '(minified)' : '';

		console.log( `  ✓ ${ kind }/vendor/${ asset.dest } ${ tag }`.trimEnd() );
	}
}

/**
 * @param {object} [options] Same shape as `createAtumViteConfig` options.
 */
export async function runBuild( options = {} ) {
	const resolved = resolveAtumOptions( options );
	const {
		pluginRoot,
		pluginSlug,
		jsEntries,
		cssEntries,
		copyDirs,
		displayName,
		cssBanner,
		cssReplacements,
		deletePaths,
		vendorAssets,
	} = resolved;

	// Empty dist once (per-entry builds run with emptyOutDir:false to accumulate).
	fs.rmSync( path.join( pluginRoot, 'dist' ), { recursive: true, force: true } );

	/*
	 * Asset URLs in built CSS are emitted with this base prefix, so a CSS at
	 * `dist/css/atum-X.css` referencing an image emits `/wp-content/plugins/
	 * <slug>/dist/images/Y.png` instead of `../images/Y.png`. This avoids any
	 * server- or browser-side quirks normalizing the `..` traversal (some
	 * setups eat the `..` segment and the request lands at
	 * `dist/css/images/Y.png` → 404). Absolute paths from the domain root
	 * are unambiguous.
	 */
	const baseShared = {
		configFile  : false,
		root        : pluginRoot,
		logLevel    : 'warn',
		resolve     : getWordPressResolveConfig( { pluginRoot } ),
		css         : getWordPressCssConfig(),
		optimizeDeps: getWordPressOptimizeDeps(),
		base        : `/wp-content/plugins/${ pluginSlug }/dist/`,
	};

	const buildBase = {
		outDir               : 'dist',
		emptyOutDir          : false,
		manifest             : false,
		sourcemap            : false,
		minify               : true,
		cssMinify            : true,
		target               : 'es2020',
		assetsInlineLimit    : 0,
		copyPublicDir        : false,
		/*
		 * Each entry is a deliberate single self-contained IIFE (no code
		 * splitting), so big admin bundles are expected — silence the noise.
		 */
		chunkSizeWarningLimit: 4000,
	};

	const jsList = Object.entries( jsEntries );
	const cssCount = Object.keys( cssEntries ).length;

	console.log(
		`\n📦 ${ displayName } build — ${ jsList.length } JS entries (IIFE, `
		+ `concurrency ${ JS_BUILD_CONCURRENCY }), ${ cssCount } CSS entries\n`,
	);

	/*
	 * 1) One self-contained IIFE bundle per JS entry (no code splitting), in
	 *    a bounded-concurrency pool. Each build writes distinct files into
	 *    dist/ so parallel runs don't collide.
	 */
	await runPool( jsList, JS_BUILD_CONCURRENCY, async ( [ key, src ] ) => {
		const base = key.split( '/' ).pop();

		await build( {
			...baseShared,
			/*
			 * The shim resolves jquery/swal/moment/chart to virtual ESM
			 * modules whose real `export default` is the runtime global (no
			 * interop `.default` wrapper) and prepends the jQuery imports that
			 * ATUM source relies on as free globals (ProvidePlugin style).
			 */
			plugins: [
				wordpressGlobalsShimPlugin(),
				wordpressAssetPhpPlugin(),
			],
			build: {
				...buildBase,
				rollupOptions: {
					input : { [ base ]: src },
					output: {
						format        : 'iife',
						name          : iifeName( base ),
						/*
						 * `format: 'iife'` already forces a single chunk
						 * (codeSplitting:false), which makes inlineDynamicImports
						 * implicit — setting it explicitly only triggers a warning.
						 */
						entryFileNames: `js/${ base }.js`,
						assetFileNames: 'images/[name][extname]',
					},
				},
			},
		} );

		console.log( `  ✓ js/${ base }.js` );
	} );

	// 2) Single CSS build for all SCSS entries (banner + image copy in post-build).
	if ( cssCount ) {
		await build( {
			...baseShared,
			plugins: [
				wordpressAssetPhpPlugin(),
				wordpressPostBuildPlugin( {
					displayName,
					copyDirs,
					cssBanner,
					cssReplacements,
					deletePaths,
				} ),
			],
			build: {
				...buildBase,
				rollupOptions: {
					input : cssEntries,
					output: getWordPressRollupOutput(),
				},
			},
		} );
		console.log( `  ✓ ${ cssCount } CSS files` );
	}

	// 3) Vendor assets (npm UMDs + static files) → dist/vendor/.
	if ( vendorAssets ) {
		await shipVendorAssets( pluginRoot );
	}

	writeManifest( resolved );

	console.log( `\n✅ ${ displayName } build complete → dist/ (${ pluginSlug })\n` );
}

// Run when invoked directly (not when imported by an addon's build script).
const invokedDirectly
	= process.argv[ 1 ]
		&& path.resolve( process.argv[ 1 ] ) === path.resolve( fileURLToPath( import.meta.url ) );

if ( invokedDirectly ) {
	const options = process.argv[ 2 ]
		? await loadBuildOptionsFromConfig( process.argv[ 2 ] )
		: ( await import( './atum-base-options.mjs' ) ).atumBaseOptions;

	runBuild( options ).catch( ( err ) => {
		console.error( err );
		process.exit( 1 );
	} );
}
