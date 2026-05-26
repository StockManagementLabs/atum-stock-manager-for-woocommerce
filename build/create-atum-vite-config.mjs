/**
 * Factory for ATUM plugin Vite configs (base + addons).
 *
 * - `createAtumViteConfig()` builds the **dev / serve** config (HMR in wp-admin).
 * - `resolveAtumOptions()` exposes the resolved entry map + paths so the
 *   production builder (`build/build.mjs`) can compile one IIFE bundle per entry.
 *
 * Production builds do NOT go through this `defineConfig` (Vite/Rollup cannot
 * emit `format: 'iife'` with multiple inputs in a single build). Use
 * `bun build/build.mjs` instead.
 */

import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vite';
import basicSsl from '@vitejs/plugin-basic-ssl';

import { discoverAtumEntries, getJsEntriesForViteWordPress } from './discover-entries.mjs';
import {
	wordpressExternalsPlugin,
	wordpressGlobalsShimPlugin,
	atumDevScssAutoImportPlugin,
	viteWordPressServerPlugin,
	devImageServerPlugin,
	getWordPressOptimizeDeps,
	getWordPressCssConfig,
	getWordPressServerConfig,
	getWordPressBase,
	getWordPressResolveConfig,
} from './vite.shared.mjs';

const ATUM_BUILD_DIR = path.dirname( fileURLToPath( import.meta.url ) );

/**
 * CSS-only entries (`.scss`) from the discovered entry map.
 *
 * @param {Record<string, string>} entries
 * @returns {Record<string, string>}
 */
function getCssEntries( entries ) {
	const cssEntries = {};

	for ( const [ key, src ] of Object.entries( entries ) ) {
		if ( key.startsWith( 'css/' ) ) {
			cssEntries[ key ] = src;
		}
	}

	return cssEntries;
}

/**
 * Resolve the shared, fully-computed options for a plugin (paths, entries,
 * ports). Consumed by both the serve config and the production builder.
 *
 * @param {object} options See `createAtumViteConfig`.
 */
export function resolveAtumOptions( options = {} ) {
	const pluginRoot = options.pluginRoot || path.resolve( ATUM_BUILD_DIR, '..' );
	const pluginSlug = options.pluginSlug || path.basename( pluginRoot );
	const port = options.port ?? 5173;
	const basePath = `/wp-content/plugins/${ pluginSlug }/`;

	const entries = discoverAtumEntries( pluginRoot, {
		jsFilePrefix: options.jsFilePrefix ?? 'atum-',
		jsRename    : options.jsRename,
	} );

	const jsEntries = getJsEntriesForViteWordPress( entries );
	const cssEntries = getCssEntries( entries );

	const copyDirs = options.copyDirs ?? [
		{
			src  : path.join( pluginRoot, 'assets/images' ),
			dest : path.join( pluginRoot, 'dist/images' ),
			label: 'images/',
		},
	];

	return {
		pluginRoot,
		pluginSlug,
		port,
		basePath,
		entries,
		jsEntries,
		cssEntries,
		copyDirs,
		displayName: options.displayName || pluginSlug,
		cssBanner  : options.cssBanner || '',
		scssAliases: options.scssAliases || {},
		cssReplacements: options.cssReplacements || [],
		deletePaths: options.deletePaths || [],
		vendorAssets: options.vendorAssets ?? true,
	};
}

/**
 * @param {object} options
 * @param {string} [options.pluginSlug] WordPress plugin folder name.
 * @param {number} [options.port] Dev server port.
 * @param {string} [options.jsFilePrefix] Output JS prefix (e.g. atum-, atum-mi-).
 * @param {Record<string, string>} [options.jsRename]
 * @param {string} [options.cssBanner] Prepended to built CSS files.
 * @param {string} [options.displayName] Post-build log label.
 * @param {string} [options.pluginRoot] Absolute plugin root (default: parent of build/).
 * @param {Array<{src: string, dest: string, label: string}>} [options.copyDirs]
 * @param {Record<string, string>} [options.scssAliases] JS slug → CSS slug aliases for dev HMR.
 * @param {Array<{search: string|RegExp, replace: string}>} [options.cssReplacements] Post-build CSS replacements.
 * @param {string[]} [options.deletePaths] Post-build paths to delete (relative to plugin root or absolute).
 * @param {boolean} [options.vendorAssets] Whether to ship base vendor assets in production.
 */
export function createAtumViteConfig( options = {} ) {
	const resolved = resolveAtumOptions( options );
	const { pluginRoot, pluginSlug, port, basePath, entries, scssAliases } = resolved;

	return defineConfig( ( { command } ) => ( {
		root        : pluginRoot,
		resolve     : getWordPressResolveConfig( { pluginRoot } ),
		css         : getWordPressCssConfig(),
		optimizeDeps: getWordPressOptimizeDeps(),
		base        : getWordPressBase( basePath, command ),
		server      : getWordPressServerConfig( port ),
		plugins     : [
			/*
			 * Self-signed cert so the dev server runs over HTTPS, matching
			 * the parent site (HTTPS atum.loc / .local). Without this the
			 * browser warns "Mixed Content" for every asset URL the rewriter
			 * sends to the dev server (and some — especially images
			 * referenced from CSS — are blocked outright). User accepts the
			 * self-signed cert once in the browser (visit the configured
			 * dev-server URL and "Proceed"). All ATUM/addon dev servers share
			 * the base plugin cert cache so accepting it for ATUM also covers
			 * addon ports.
			 */
			...( command === 'serve' ? [
				basicSsl( {
					certDir: path.join( path.resolve( ATUM_BUILD_DIR, '..' ), 'node_modules/.vite/basic-ssl' ),
				} ),
			] : [] ),
			/*
			 * Shim FIRST so its resolveId provides virtual modules for
			 * `import 'chart.js/auto'` and the rest of the
			 * STATIC_EXTERNALS that Vite's default resolver can't find,
			 * AND its transform prepends jQuery imports for source files
			 * that use them as free globals (ProvidePlugin style).
			 */
			wordpressGlobalsShimPlugin(),
			/*
			 * Auto-import each entry's matching SCSS into its JS entry, so
			 * Vite handles CSS as a JS module → real HMR for SCSS partials
			 * without a page reload. Dev-only (`apply: 'serve'`); production
			 * keeps the separate CSS build via `build/build.mjs`.
			 */
			atumDevScssAutoImportPlugin( { entries, aliases: scssAliases } ),
			wordpressExternalsPlugin(),
			devImageServerPlugin( {
				assetsDir: path.join( pluginRoot, 'assets' ),
				basePath : `/wp-content/plugins/${ pluginSlug }`,
			} ),
			viteWordPressServerPlugin( {
				base      : basePath,
				entries,
				pluginRoot,
			} ),
		],
	} ) );
}
