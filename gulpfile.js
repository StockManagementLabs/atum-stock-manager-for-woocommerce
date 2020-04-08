// Load all the modules from package.json
var gulp          = require('gulp'),
    plumber       = require('gulp-plumber'),
    gulpif        = require('gulp-if'),
    watch         = require('gulp-watch'),
    livereload    = require('gulp-livereload'),
    notify        = require('gulp-notify'),
    wrap          = require('gulp-wrap'),
    autoprefix    = require('gulp-autoprefixer'),
    sass          = require('gulp-sass'),
    sourcemaps    = require('gulp-sourcemaps'),
    composer      = require('gulp-composer'),
    filter        = require('gulp-filter'),
    webpack       = require('webpack'),
    webpackStream = require('webpack-stream'),
	path          = require('path');

// Plugin version
var version = '1.7.1',
    curDate = new Date();

// Global config
var config = {
	
	assetsDir : './assets',

	devUrl    : 'http://atum.loc',
	production: false,

	// decorate
	decorate: {

		templateCSS: [
			'/** \n',
			' * ATUM Inventory Management for WooCommerce CSS \n',
			' * @version ' + version + ' \n',
			' * @author Be Rebel  \n',
			' *\n',
			' * Author URI: https://berebel.io \n',
			' * License : ©' + curDate.getFullYear() + ' Stock Management Labs \n',
			' */\n',
			'\n <%= contents %>'
		].join('')

	}
};

// CLI options
var enabled = {
	// Disable source maps when `--production`
	maps: !config.production,
};


// Default error handler
var onError = function (err) {
	console.log('An error occured:', err.message);
	this.emit('end');
}

// As with javascripts this task creates two files, the regular and
// the minified one. It automatically reloads browser as well.
var options = {

	sass: {
		errLogToConsole: !config.production,
		outputStyle    : config.production ? 'compressed' : 'expanded',
		//precision      : 10,
		includePaths   : [
			'.',
			config.assetsDir + '/scss'
		]
		//imagePath: 'assets/img'
	}

};

//
// SASS task
//-----------

gulp.task('sass::atum', function () {

	var destDir = config.assetsDir + '/css';
	
	return gulp.src([
			config.assetsDir + '/scss/*.scss',
		])
		.pipe(plumber({errorHandler: onError}))
		.pipe(gulpif(enabled.maps, sourcemaps.init()))
		.pipe(sass(options.sass))
		.pipe(autoprefix('last 2 version'))
		.pipe(wrap(config.decorate.templateCSS))
		.pipe(gulpif(enabled.maps, sourcemaps.write('.', {
			sourceRoot: 'assets/scss/',
		})))
		.pipe(gulp.dest(destDir))
		//.pipe(notify({message: 'sass task complete'}))
		.pipe(filter("**/*.css"))
		.pipe(livereload());

});

//
// JS task
//----------

gulp.task('js::atum', function () {
	return gulp.src(config.assetsDir + '/js/**/*.js')
		// .pipe(webpackStream({
		//   config: require('./webpack.config.js')
		// }, webpack))
		.pipe(webpackStream({
			devtool: 'source-map',
			
			entry: {
				'list-tables'    : path.join(__dirname, config.assetsDir + '/js/src/') + 'list-tables.ts',
				'post-type-list' : path.join(__dirname, config.assetsDir + '/js/src/') + 'post-type-list-tables.ts',
				'product-data'   : path.join(__dirname, config.assetsDir + '/js/src/') + 'product-data.ts',
				'settings'       : path.join(__dirname, config.assetsDir + '/js/src/') + 'settings.ts',
				'orders'         : path.join(__dirname, config.assetsDir + '/js/src/') + 'orders.ts',
				'data-export'    : path.join(__dirname, config.assetsDir + '/js/src/') + 'data-export.ts',
				'addons'         : path.join(__dirname, config.assetsDir + '/js/src/') + 'addons.ts',
				'marketing-popup': path.join(__dirname, config.assetsDir + '/js/src/') + 'marketing-popup.ts',
				'dashboard'      : path.join(__dirname, config.assetsDir + '/js/src/') + 'dashboard.ts',
			},
			
			output: {
				filename: 'atum-[name].js'
			},
			
			resolve: {
				extensions: ['.js', '.ts']
			},
			
			module: {
				rules: [
					/* {
						enforce: 'pre',
						test   : /\.js$/,
						exclude: /node_modules/,
						use    : 'eslint-loader',
					}, */
					{
						test: /\.ts$/,
						exclude: /node_modules/,
						use: {
							loader: 'ts-loader'
						}
					},
				],
				noParse: /switchery/
			},
			
			plugins: [
				
				// Compress JS with UglifyJS
				new webpack.optimize.UglifyJsPlugin({
					compress : {
						warnings: false,
					},
					output   : {
						comments: false,
					},
					sourceMap: enabled.maps
				}),
				
				// Fixes warning in moment-with-locales.min.js
				// Module not found: Error: Can't resolve './locale' in ...
				new webpack.IgnorePlugin(/\.\/locale$/),
			
			],
			
		}, webpack))
		.pipe(gulp.dest(config.assetsDir + '/js/build/'));
});

//
// Composer packages installation
// ------------------------------

gulp.task('composer::install', function () {
	// Installation + optimization
	composer({
		cwd: '.',
		o  : true,
		bin: '/usr/local/bin/composer',
	});
});

gulp.task('composer::update', function () {
	// Update + optinmization
	composer('update', {
		cwd: '.',
		o  : true,
		bin: '/usr/local/bin/composer',
	});
});

gulp.task('composer::optimize', function () {
	// Just optimization (classmap autoloader array generation)
	composer('dumpautoload', {
		cwd     : '.',
		optimize: true,
		bin     : '/usr/local/bin/composer',
	});
});


//
// Start the livereload server and watch files for changes
// -------------------------------------------------------

gulp.task('watch::atum', function () {

	livereload.listen();

	gulp.watch(config.assetsDir + '/scss/**/*.scss', gulp.series(['sass::atum']));
	gulp.watch(config.assetsDir + '/js/src/**/*.ts', gulp.series(['js::atum']));

	gulp.watch([

		// PHP files
		'./**/*.php',

		// Images
		config.assetsDir + '/images/**/*',

		// Excludes
		'!' + config.assetsDir + '/js/build/**/*.js',
		'!node_modules',

	]).on('change', function (file) {
		// reload browser whenever any PHP, SCSS, JS or image file changes
		livereload.changed(file);
	});
});

// Default task
gulp.task('default', gulp.series(['sass::atum', 'js::atum']), function () {
	
});