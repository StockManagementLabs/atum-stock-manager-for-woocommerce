/**
 * Discover Vite entry points from ATUM asset folders (glob-like, like gulp).
 */

import fs from 'fs';
import path from 'path';

const DEFAULT_JS_RENAME = {
	'post-type-list-tables': 'post-type-list',
};

function listFiles( dir, predicate ) {
	if ( !fs.existsSync( dir ) ) {
		return [];
	}

	return fs.readdirSync( dir ).filter( predicate );
}

/**
 * @param {string} pluginRoot Absolute path to the plugin directory.
 * @param {object} [options]
 * @param {string} [options.jsFilePrefix] e.g. 'atum-' or 'atum-mi-'
 * @param {Record<string, string>} [options.jsRename] Map TS basename → output middle name.
 * @returns {Record<string, string>} Vite rollup input map (key → absolute path).
 */
export function discoverAtumEntries( pluginRoot, options = {} ) {
	const {
		jsFilePrefix = 'atum-',
		jsRename = DEFAULT_JS_RENAME,
	} = options;

	const entries = {};
	const jsSrcDir = path.join( pluginRoot, 'assets/js/src' );
	const scssDir = path.join( pluginRoot, 'assets/scss' );
	const scssRtlDir = path.join( pluginRoot, 'assets/scss/rtl' );

	for ( const file of listFiles( jsSrcDir, ( f ) => f.endsWith( '.ts' ) ) ) {
		const base = path.basename( file, '.ts' );
		const outputName = jsRename[ base ] ?? base;
		const entryKey = `js/${ jsFilePrefix }${ outputName }`;

		entries[ entryKey ] = path.join( jsSrcDir, file );
	}

	for ( const file of listFiles( scssDir, ( f ) => f.startsWith( 'atum-' ) && f.endsWith( '.scss' ) ) ) {
		const base = path.basename( file, '.scss' );

		entries[ `css/${ base }` ] = path.join( scssDir, file );
	}

	for ( const file of listFiles( scssRtlDir, ( f ) => f.endsWith( '.scss' ) ) ) {
		const base = path.basename( file, '.scss' );

		entries[ `css/${ base }` ] = path.join( scssRtlDir, file );
	}

	return entries;
}

/**
 * JS-only entries for vite-wordpress.json.
 *
 * @param {Record<string, string>} entries
 * @returns {Record<string, string>}
 */
export function getJsEntriesForViteWordPress( entries ) {
	const jsEntries = {};

	for ( const [ key, src ] of Object.entries( entries ) ) {
		if ( key.startsWith( 'js/' ) ) {
			jsEntries[ key ] = src;
		}
	}

	return jsEntries;
}
