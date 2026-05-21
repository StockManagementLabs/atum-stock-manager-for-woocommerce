/**
 * Vendor assets pipeline for the ATUM base plugin.
 *
 * Each entry is shipped to `dist/vendor/<dest>` at build time:
 *   - `src`     Absolute path to the source (node_modules or assets/js/vendor).
 *   - `dest`    File name under `dist/vendor/`.
 *   - `minify`  Optional. Run through esbuild minifier (use for non-`.min` sources).
 *   - `isolate` Optional. Wrap the script so its global is captured under a
 *               private namespace and the original `window[capture]` is
 *               restored after load — same pattern as the chart.js pilot:
 *                  { capture: 'Chart', expose: 'atumChart' }
 *               Use ONLY for libraries that own a top-level global. Never for
 *               scripts that patch jQuery / jQuery UI (they need to mutate the
 *               real global to work).
 *
 * @param {string} pluginRoot Absolute plugin root.
 * @returns {Array<{src:string, dest:string, minify?:boolean, isolate?:{capture:string, expose:string}}>}
 */
import path from 'path';

export function getVendorAssets( pluginRoot ) {
	const nm = ( p ) => path.join( pluginRoot, 'node_modules', p );
	const src = ( p ) => path.join( pluginRoot, 'assets/js/vendor', p );

	return [
		/*
		 * Source-imported npm libraries — externalized via the globals shim
		 * (see STATIC_EXTERNALS in build/vite.shared.mjs) and isolated so they
		 * never collide with another plugin shipping its own version.
		 */
		{
			src    : nm( 'chart.js/dist/Chart.bundle.min.js' ),
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
			dest   : 'intro.min.js',
			isolate: { capture: 'introJs', expose: 'atumIntroJs' },
		},
		{
			src    : nm( 'hammerjs/hammer.min.js' ),
			dest   : 'hammer.min.js',
			isolate: { capture: 'Hammer', expose: 'atumHammer' },
		},

		/*
		 * PHP-enqueued static vendor files migrated from `assets/js/vendor`.
		 * Not isolated: most of these patch jQuery / jQuery UI and need to
		 * mutate the real global. Lodash and GridStack expose top-level
		 * globals but ATUM downstream scripts consume them by name — leaving
		 * them as-is for now (isolation would require touching every consumer).
		 */
		{
			src   : src( 'wp-color-picker-alpha.js' ),
			dest  : 'wp-color-picker-alpha.min.js',
			minify: true,
		},
		{ src: src( 'sweetalert2.min.js' ),            dest: 'sweetalert2.min.js' },
		{ src: src( 'lodash.min.js' ),                 dest: 'lodash.min.js' },
		{ src: src( 'jquery.ui.touch-punch.min.js' ),  dest: 'jquery.ui.touch-punch.min.js' },
		{ src: src( 'gridstack.min.js' ),              dest: 'gridstack.min.js' },
		{ src: src( 'gridstack.jqueryui.min.js' ),     dest: 'gridstack.jqueryui.min.js' },
		{ src: src( 'jquery.nicescroll.min.js' ),      dest: 'jquery.nicescroll.min.js' },
	];
}
