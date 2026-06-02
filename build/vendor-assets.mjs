/**
 * Vendor assets pipeline for the ATUM base plugin.
 *
 * Each entry is shipped at build time. The destination directory under `dist/`
 * is derived from `kind`:
 *   - `kind: 'js'`  (default) → `dist/js/vendor/<dest>`
 *   - `kind: 'css'`           → `dist/css/vendor/<dest>`
 *
 * Fields:
 *   - `src`     Absolute path to the source (node_modules or assets/{js,css}/vendor).
 *   - `dest`    File name under the destination directory.
 *   - `kind`    Optional. 'js' (default) or 'css'.
 *   - `minify`  Optional. Run through esbuild minifier (use for non-`.min` sources).
 *   - `isolate` Optional, JS-only. Wrap the script so its global is captured
 *               under a private namespace and the original `window[capture]` is
 *               restored after load — same pattern as the chart.js pilot:
 *                  { capture: 'Chart', expose: 'atumChart' }
 *               Use ONLY for libraries that own a top-level global. Never for
 *               scripts that patch jQuery / jQuery UI (they need to mutate the
 *               real global to work).
 *
 * @param {string} pluginRoot Absolute plugin root.
 * @returns {Array<{src:string, dest:string, kind?:'js'|'css', minify?:boolean, isolate?:{capture:string, expose:string}}>}
 */
import path from 'path';

export function getVendorAssets( pluginRoot ) {
	const nm     = ( p ) => path.join( pluginRoot, 'node_modules', p );
	const srcJs  = ( p ) => path.join( pluginRoot, 'assets/js/vendor', p );
	const srcCss = ( p ) => path.join( pluginRoot, 'assets/css/vendor', p );

	return [
		/*
		 * --- JS: Source-imported npm libraries ---
		 * Externalized via the globals shim (see STATIC_EXTERNALS in
		 * build/vite.shared.mjs) and isolated so they never collide with
		 * another plugin shipping its own version.
		 */
		{
			src    : nm( 'chart.js/dist/chart.umd.min.js' ),
			dest   : 'chart.bundle.min.js',
			isolate: { capture: 'Chart', expose: 'atumChart' },
		},
		{
			src    : nm( 'bootstrap/dist/js/bootstrap.bundle.min.js' ),
			dest   : 'bootstrap.bundle.min.js',
			isolate: { capture: 'bootstrap', expose: 'atumBootstrap' },
		},
		{
			src    : nm( 'intro.js/minified/intro.min.js' ),
			dest   : 'introjs.min.js',
			isolate: { capture: 'introJs', expose: 'atumIntroJs' },
		},
		{
			src    : nm( 'hammerjs/hammer.min.js' ),
			dest   : 'hammer.min.js',
			isolate: { capture: 'Hammer', expose: 'atumHammer' },
		},

		/*
		 * --- JS: PHP-enqueued static vendor files ---
		 * Not isolated: scripts that patch jQuery need to mutate the real
		 * global to work.
		 */
		{
			src   : srcJs( 'wp-color-picker-alpha.js' ),
			dest  : 'wp-color-picker-alpha.min.js',
			minify: true,
		},
		{ src: srcJs( 'sweetalert2.min.js' ),       dest: 'sweetalert2.min.js' },
		{ src: srcJs( 'jquery.nicescroll.min.js' ), dest: 'jquery.nicescroll.min.js' },

		/*
		 * --- JS: UMD jQuery/DOM plugins, externalized after the Vite migration ---
		 * These TS entries used to do `import '../vendor/<file>'` so the UMD's
		 * "browser globals" branch would run and attach to the real jQuery.
		 * Rolldown takes the CommonJS branch and never invokes the factory, so
		 * they're shipped as standalone vendor scripts and enqueued via their
		 * own WP handle (`atum-select2`, `atum-floatthead`, …) in PHP.
		 * Not isolated: they must patch the real `window.jQuery`.
		 */
		{ src: srcJs( 'select2.js' ),              dest: 'select2.min.js',              minify: true },
		{ src: srcJs( 'jquery.address.min.js' ),   dest: 'jquery.address.min.js'                     },
		{ src: srcJs( 'jquery.floatThead.js' ),    dest: 'jquery.floatThead.min.js',    minify: true },
		{ src: srcJs( 'jquery.jscrollpane.js' ),   dest: 'jquery.jscrollpane.min.js',   minify: true },
		{ src: srcJs( 'jquery.easytree.js' ),      dest: 'jquery.easytree.min.js',      minify: true },
		{ src: srcJs( 'dragscroll.js' ),           dest: 'dragscroll.min.js',           minify: true },

		/*
		 * --- CSS: PHP-enqueued vendor stylesheets ---
		 * Copied as-is to dist/css/vendor/.
		 */
		{ src: srcCss( 'sweetalert2.min.css' ),       dest: 'sweetalert2.min.css',       kind: 'css' },
		{ src: srcCss( 'owl.carousel.min.css' ),      dest: 'owl.carousel.min.css',      kind: 'css' },
		{ src: srcCss( 'owl.theme.default.min.css' ), dest: 'owl.theme.default.min.css', kind: 'css' },
	];
}
