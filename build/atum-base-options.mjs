/**
 * Single source of truth for the ATUM **base plugin** build options.
 *
 * Imported by both the dev/serve config (`vite.config.mjs` →
 * `createAtumViteConfig`) and the production builder (`build/build.mjs` →
 * `runBuild`) so the slug, port, prefix, banner and display name never drift
 * between `bun run dev` and `bun run build`.
 *
 * Addons do NOT use this file — each addon ships its own options object.
 */

const curYear = new Date().getFullYear();

export const atumBaseOptions = {
	pluginSlug  : 'atum-stock-manager-for-woocommerce',
	port        : 5173,
	jsFilePrefix: 'atum-',
	displayName : 'ATUM Stock Manager',
	cssBanner   : [
		'/** \n',
		' * ATUM Inventory Management for WooCommerce - CSS \n',
		' * @author BE REBEL \n',
		' *\n',
		' * Author URI: https://berebel.studio \n',
		` * License : ©${ curYear } Stock Management Labs \n`,
		' */\n',
	].join( '' ),
};
