// Load all the modules from package.json
import gulp from 'gulp';
const { task, src, dest, watch, series, emit } = gulp;

import plumber from 'gulp-plumber';
import gulpif from 'gulp-if';
import livereload from 'gulp-livereload';
import wrap from 'gulp-wrap';
import autoprefix from 'gulp-autoprefixer';
import sourcemaps from 'gulp-sourcemaps';
import composer from 'gulp-composer';
import filter from 'gulp-filter';
import cleanDir from 'gulp-clean-dir';
import webpack from 'webpack';
import webpackStream from 'webpack-stream';
import TerserPlugin from 'terser-webpack-plugin';

import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass( dartSass );

// Plugin version
const version = '1.9.41',
      curDate = new Date();

// Global config
const config = {
	
    assetsDir: './assets',
    jsSrcDir : './assets/js/src',

    devUrl    : 'http://atum.loc',
    production: false,

    // Decorate
    decorate: {

        templateCSS: [
            '/** \n',
            ' * ATUM Inventory Management for WooCommerce CSS \n',
            ' * @version ' + version + ' \n',
            ' * @author BE REBEL  \n',
            ' *\n',
            ' * Author URI: https://berebel.studio \n',
            ' * License : ©' + curDate.getFullYear() + ' Stock Management Labs \n',
            ' */\n',
            '\n <%= contents %>',
        ].join( '' ),

    },
};

// CLI options
const enabled = {
    // Disable source maps when `--production`
    maps: !config.production,
};

// Default error handler
const onError = ( err ) => {
    console.log( 'An error occurred:', err.message );
    emit( 'end' );
};

/*
 * As with javascripts this task creates two files, the regular and
 * the minified one. It automatically reloads browser as well.
 */
const options = {

    sass: {
        errLogToConsole: !config.production,
        outputStyle    : config.production ? 'compressed' : 'expanded',
        // Precision      : 10,
        includePaths   : [
            '.',
            config.assetsDir + '/scss',
        ],
		silenceDeprecations: ['legacy-js-api'], // TODO: MIGRATE TO THE MODERN API (https://sass-lang.com/documentation/breaking-changes/legacy-js-api/).
        // ImagePath: 'assets/img'
    },

};

/*
 *
 *  SASS task
 * -----------
 */

task( 'sass::atum', () => {
	
    const destDir = config.assetsDir + '/css';
	
    return src( [
        config.assetsDir + '/scss/*.scss',
        config.assetsDir + '/scss/rtl/*.scss',
    ] )
        .pipe( plumber( { errorHandler: onError } ) )
        .pipe( gulpif( enabled.maps, sourcemaps.init() ) )
        .pipe( sass( options.sass ) )
        .pipe( autoprefix( 'last 2 version' ) )
        .pipe( wrap( config.decorate.templateCSS ) )
        .pipe( gulpif( enabled.maps, sourcemaps.write( '.', {
            sourceRoot: [ 'assets/scss/', 'assets/scss/rtl/' ],
        } ) ) )
        .pipe( cleanDir( destDir ) )
        .pipe( dest( destDir ) )
    	// .pipe(notify({message: 'sass task complete'}))
        .pipe( filter( '**/*.css' ) )
        .pipe( livereload() );
	
} );

/*
 *
 *  JS task
 * ----------
 */

task( 'js::atum', () => {
    return src( config.assetsDir + '/js/**/*.js' )
		/*
		 * .pipe(webpackStream({
		 *   config: require('./webpack.config.js')
		 * }, webpack))
		 */
        .pipe( webpackStream( {
            devtool: config.production ? false : 'source-map',
			
            entry: {
                'addons'              : config.jsSrcDir + '/addons.ts',
                'admin-modals'        : config.jsSrcDir + '/admin-modals.ts',
                'check-orders'        : config.jsSrcDir + '/check-orders.ts',
                'dashboard'           : config.jsSrcDir + '/dashboard.ts',
                'data-export'         : config.jsSrcDir + '/data-export.ts',
                'list-tables'         : config.jsSrcDir + '/list-tables.ts',
                'marketing-popup'     : config.jsSrcDir + '/marketing-popup.ts',
                'orders'              : config.jsSrcDir + '/orders.ts',
                'post-type-list'      : config.jsSrcDir + '/post-type-list-tables.ts',
                'product-data'        : config.jsSrcDir + '/product-data.ts',
                'search-orders'       : config.jsSrcDir + '/search-orders.ts',
                'settings'            : config.jsSrcDir + '/settings.ts',
                'suppliers'           : config.jsSrcDir + '/suppliers.ts',
                'trials-modal'        : config.jsSrcDir + '/trials-modal.ts',
                'product-editor-modal': config.jsSrcDir + '/product-editor-modal.ts',
            },
			
            output: {
                filename: 'atum-[name].js',
            },
			
            resolve: {
                extensions: [ '.js', '.ts' ],
            },
			
            externals: {
                jquery     : 'jQuery',
                $          : 'jQuery',
                sweetalert2: 'Swal',
            },
			
            module: {
                rules: [
                    /*
                     * {
                     * enforce: 'pre',
                     * test   : /\.js$/,
                     * exclude: /node_modules/,
                     * use    : 'eslint-loader',
                     * },
                     */
                    {
                        test   : /\.ts$/,
                        exclude: /node_modules/,
                        use    : {
                            loader: 'ts-loader',
                        },
                    },
                ],
            },
			
            optimization: {
                minimize : config.production,
                minimizer: [ new TerserPlugin( {
                    terserOptions: {
                        format: {
                            comments: false,
                        },
                    },
                    extractComments: false,
                } ) ],
            },
            mode : config.production ? 'production' : 'development',
            cache: !config.production,
            bail : false,
            watch: false,
			
            plugins: [
				
                /*
                 * Fixes warning in moment-with-locales.min.js
                 * Module not found: Error: Can't resolve './locale' in ...
                 */
                new webpack.IgnorePlugin( {
                    resourceRegExp: /^\.\/locale$/,
                    contextRegExp : /moment/,
                } ),
				
                // Provide jQuery globally instead of having to import it everywhere.
                new webpack.ProvidePlugin( {
                    $     : 'jquery',
                    jQuery: 'jquery',
                } ),
			
            ],
			
        }, webpack ) )
        .pipe( cleanDir( config.assetsDir + '/js/build/' ) )
        .pipe( dest( config.assetsDir + '/js/build/' ) );
} );

// task( 'scss::webpack::atum', () => {
//     return src( config.assetsDir + '/scss/**/*.scss' )
// 		/*
// 		 // .pipe(webpackStream({
// 		 //  config: require('./webpack.config.js')
// 		 // }, webpack))
// 		 */
//         .pipe( webpackStream( {
//             devtool: config.production ? false : 'source-map',
//
//             entry: {
//                 'addons'              : config.assetsDir + '/scss/atum-addons.scss',
//                 'admin-modals'        : config.assetsDir + '/scss/atum-admin-modals.scss',
//                 'check-orders'        : config.assetsDir + '/scss/atum-check-orders.scss',
//                 'dashboard'           : config.assetsDir + '/scss/atum-dashboard.scss',
//                 'icons'		          : config.assetsDir + '/scss/atum-icons.scss',
//                 'list'		          : config.assetsDir + '/scss/atum-list.scss',
//                 'marketing-popup'     : config.assetsDir + '/scss/atum-marketing-popup.scss',
//                 'orders'              : config.assetsDir + '/scss/atum-orders.scss',
//                 'orders-list'         : config.assetsDir + '/scss/atum-orders-list.scss',
//                 'po-export'		      : config.assetsDir + '/scss/atum-po-export.scss',
//                 'post-type-list'      : config.assetsDir + '/scss/atum-post-type-list.scss',
//                 'product-data'        : config.assetsDir + '/scss/atum-product-data.scss',
//                 'search-orders'       : config.assetsDir + '/scss/atum-search-orders.scss',
//                 'settings'            : config.assetsDir + '/scss/atum-settings.scss',
//                 'suppliers'           : config.assetsDir + '/scss/atum-suppliers.scss',
//             },
//
//             output: {
//                 filename: '[name].css',
//             },
//
//             resolve: {
//                 extensions: [ '.scss', '.css' ],
//             },
//
//             module: {
//                 rules: [
// 					{
// 						test: /\.s[ac]ss$/i,
// 						use: [
// 							"sass-loader",
// 							{
// 								loader: "sass-loader",
// 								options: {
// 									api: "modern-compiler",
// 									sassOptions: {
// 										sourceMap: !config.production
// 									},
// 								},
// 							}
// 						],
// 					},
//                 ],
//             },
//
//             optimization: {
//                 minimize : config.production,
//                 minimizer: [ new TerserPlugin( {
//                     terserOptions: {
//                         format: {
//                             comments: false,
//                         },
//                     },
//                     extractComments: false,
//                 } ) ],
//             },
//             mode : config.production ? 'production' : 'development',
//             cache: !config.production,
//             bail : false,
//             watch: false,
//
//             plugins: [
//
//             ],
//
//         }, webpack ) )
//         .pipe( cleanDir( config.assetsDir + '/css/' ) )
//         .pipe( dest( config.assetsDir + '/css/' ) );
// } );

/*
 *
 * Composer packages installation
 * ------------------------------
 */

task( 'composer::install', ( done ) => {
    // Installation + optimization
    composer( {
        cwd: '.',
        o  : true,
        bin: '/usr/local/bin/composer',
    } );
    done();
} );

task( 'composer::update', ( done ) => {
    // Update + optinmization
    composer( 'update', {
        cwd: '.',
        o  : true,
        bin: '/usr/local/bin/composer',
    } );
    done();
} );

task( 'composer::optimize', ( done ) => {
    // Just optimization (classmap autoloader array generation)
    composer( 'dumpautoload', {
        cwd     : '.',
        optimize: true,
        bin     : '/usr/local/bin/composer',
    } );
    done();
} );

/*
 *
 * Start the livereload server and watch files for changes
 * -------------------------------------------------------
 */

task( 'watch::atum', () => {

    livereload.listen();

    watch( config.assetsDir + '/scss/**/*.scss', series( [ 'sass::atum' ] ) );
    watch( config.jsSrcDir + '**/*.ts', series( [ 'js::atum' ] ) );

    watch( [

        // PHP files
        './**/*.php',

        // Images
        config.assetsDir + '/images/**/*',

        // Excludes
        '!' + config.assetsDir + '/js/build/**/*.js',
        '!node_modules',

    ] ).on( 'change', ( file ) => {
        // Reload browser whenever any PHP, SCSS, JS or image file changes
        livereload.changed( file );
    } );
} );

// Default task
task( 'default', series( [ 'sass::atum', 'js::atum' ] ), () => {
	
} );
