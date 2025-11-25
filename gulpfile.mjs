// Load all the modules from package.json
import gulp from 'gulp';
const { task, src, dest, watch, series, emit } = gulp;

import plumber from 'gulp-plumber';
import livereload from 'gulp-livereload';
import wrap from 'gulp-wrap';
import autoprefix from 'gulp-autoprefixer';
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
const version = '1.9.52',
      curDate = new Date();

// Global config
const config = {
	
    assetsDir: './assets',
    jsSrcDir : './assets/js/src',

    devUrl    : 'https://atum.loc',
    production: false,

    // Decorate
    decorate: {

        templateCSS: [
            '/** \n',
            ' * ATUM Inventory Management for WooCommerce - CSS \n',
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
 * Compilation options
 */
const options = {

    sass: {
        errLogToConsole: !config.production,
        style          : config.production ? 'compressed' : 'expanded',
        includePaths   : [
            '.',
            config.assetsDir + '/scss',
        ],
    },

};

/*
 *  SASS task
 * -----------
 */

task( 'sass::atum', () => {
	
    const destDir = config.assetsDir + '/css';
	
    return src( [
        config.assetsDir + '/scss/*.scss',
        config.assetsDir + '/scss/rtl/*.scss',
    ], { sourcemaps: enabled.maps } )
        .pipe( plumber( { errorHandler: onError } ) )
        .pipe( sass( options.sass ) )
        .pipe( autoprefix( 'last 2 version' ) )
        .pipe( wrap( config.decorate.templateCSS ) )
        .pipe( cleanDir( destDir ) )
        .pipe( dest( destDir, { sourcemaps: '.' } ) )
        .pipe( filter( '**/*.css' ) )
        .pipe( livereload() );
} );

/*
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
                'jquery'             : 'jQuery',
                '$'                  : 'jQuery',
                'sweetalert2'        : 'Swal',
                'sweetalert2-neutral': 'Swal',
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

// Task( 'scss::webpack::atum', () => {
//     Return src( config.assetsDir + '/scss/**/*.scss' )
// 		/*
// 		 // .pipe(webpackStream({
// 		 //  config: require('./webpack.config.js')
// 		 // }, webpack))
// 		 */
//         .pipe( webpackStream( {
//             Devtool: config.production ? false : 'source-map',
//
//             Entry: {
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
//             Output: {
//                 Filename: '[name].css',
//             },
//
//             Resolve: {
//                 Extensions: [ '.scss', '.css' ],
//             },
//
//             Module: {
//                 Rules: [
// 					{
// 						Test: /\.s[ac]ss$/i,
// 						Use: [
// 							"sass-loader",
// 							{
// 								Loader: "sass-loader",
// 								Options: {
// 									Api: "modern-compiler",
// 									SassOptions: {
// 										SourceMap: !config.production
// 									},
// 								},
// 							}
// 						],
// 					},
//                 ],
//             },
//
//             Optimization: {
//                 Minimize : config.production,
//                 Minimizer: [ new TerserPlugin( {
//                     TerserOptions: {
//                         Format: {
//                             Comments: false,
//                         },
//                     },
//                     ExtractComments: false,
//                 } ) ],
//             },
//             Mode : config.production ? 'production' : 'development',
//             Cache: !config.production,
//             Bail : false,
//             Watch: false,
//
//             Plugins: [
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
