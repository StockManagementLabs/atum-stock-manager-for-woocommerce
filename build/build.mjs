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
import { build, transformWithEsbuild } from 'vite';

import { resolveAtumOptions } from './create-atum-vite-config.mjs';
import { getVendorAssets } from './vendor-assets.mjs';
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
 * Ship every entry from `vendor-assets.mjs` to `dist/vendor/`. Minifies
 * non-`.min` sources via esbuild and applies the isolation wrapper when the
 * entry declares `isolate: { capture, expose }`.
 *
 * @param {string} pluginRoot
 */
async function shipVendorAssets( pluginRoot ) {
	const vendorDir = path.join( pluginRoot, 'dist', 'js', 'vendor' );

	fs.mkdirSync( vendorDir, { recursive: true } );

	for ( const asset of getVendorAssets( pluginRoot ) ) {
		if ( !fs.existsSync( asset.src ) ) {
			console.warn( `  ⚠ vendor source missing, skipped: ${ path.relative( pluginRoot, asset.src ) }` );
			continue;
		}

		let code = fs.readFileSync( asset.src, 'utf8' );

		if ( asset.minify ) {
			const result = await transformWithEsbuild( code, asset.dest, {
				minify  : true,
				loader  : 'js',
				platform: 'browser',
				target  : 'es2017',
			} );

			code = result.code;
		}

		if ( asset.isolate ) {
			code = wrapVendor( code, asset.isolate.capture, asset.isolate.expose );
		}

		fs.writeFileSync( path.join( vendorDir, asset.dest ), code );

		const tag = asset.isolate
			? `(isolated → window.${ asset.isolate.expose })`
			: asset.minify ? '(minified)' : '';

		console.log( `  ✓ js/vendor/${ asset.dest } ${ tag }`.trimEnd() );
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
	} = resolved;

	// Empty dist once (per-entry builds run with emptyOutDir:false to accumulate).
	fs.rmSync( path.join( pluginRoot, 'dist' ), { recursive: true, force: true } );

	const baseShared = {
		configFile  : false,
		root        : pluginRoot,
		logLevel    : 'warn',
		resolve     : getWordPressResolveConfig( { pluginRoot } ),
		css         : getWordPressCssConfig(),
		optimizeDeps: getWordPressOptimizeDeps(),
		base        : './',
	};

	const buildBase = {
		outDir           : 'dist',
		emptyOutDir      : false,
		manifest         : false,
		sourcemap        : false,
		minify           : true,
		cssMinify        : true,
		target           : 'es2020',
		assetsInlineLimit: 0,
		copyPublicDir    : false,
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
						format              : 'iife',
						name                : iifeName( base ),
						inlineDynamicImports: true,
						entryFileNames      : `js/${ base }.js`,
						assetFileNames      : 'images/[name][extname]',
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
	await shipVendorAssets( pluginRoot );

	writeManifest( resolved );

	console.log( `\n✅ ${ displayName } build complete → dist/ (${ pluginSlug })\n` );
}

// Run when invoked directly (not when imported by an addon's build script).
const invokedDirectly
	= process.argv[ 1 ]
		&& path.resolve( process.argv[ 1 ] ) === path.resolve( new URL( import.meta.url ).pathname );

if ( invokedDirectly ) {
	const { atumBaseOptions } = await import( './atum-base-options.mjs' );

	runBuild( atumBaseOptions ).catch( ( err ) => {
		console.error( err );
		process.exit( 1 );
	} );
}
