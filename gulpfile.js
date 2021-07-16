// Load all the modules from package.json
var gulp          = require('gulp'),
    plumber       = require('gulp-plumber'),
    gulpif        = require('gulp-if'),
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
var version = '1.9.2',
    curDate = new Date();

// Global config
var config = {
	
	assetsDir : './assets',
	jsSrcDir  : path.join(__dirname, './assets/js/src/'),

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
			config.assetsDir + '/scss/rtl/*.scss',
		])
		.pipe(plumber({errorHandler: onError}))
		.pipe(gulpif(enabled.maps, sourcemaps.init()))
		.pipe(sass(options.sass))
		.pipe(autoprefix('last 2 version'))
		.pipe(wrap(config.decorate.templateCSS))
		.pipe(gulpif(enabled.maps, sourcemaps.write('.', {
			sourceRoot: 'assets/scss/',
			sourceRoot: 'assets/scss/rtl/',
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
			devtool: config.production ? 'no' : 'source-map',
			
			entry: {
				'list-tables'    : config.jsSrcDir + 'list-tables.ts',
				'post-type-list' : config.jsSrcDir + 'post-type-list-tables.ts',
				'product-data'   : config.jsSrcDir + 'product-data.ts',
				'settings'       : config.jsSrcDir + 'settings.ts',
				'orders'         : config.jsSrcDir + 'orders.ts',
				'data-export'    : config.jsSrcDir + 'data-export.ts',
				'addons'         : config.jsSrcDir + 'addons.ts',
				'marketing-popup': config.jsSrcDir + 'marketing-popup.ts',
				'dashboard'      : config.jsSrcDir + 'dashboard.ts',
				'check-orders'   : config.jsSrcDir + 'check-orders.ts',
			},
			
			output: {
				filename: 'atum-[name].js'
			},
			
			resolve: {
				extensions: ['.js', '.ts']
			},
			
			externals: {
				'jquery'       : 'jQuery',
				'$'            : 'window.$',
				'sweetalert2'  : 'Swal'
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
			},
			
			optimization: {
				minimize: config.production
			},
			mode: config.production ? 'production' : 'development',
			cache: !config.production,
			bail: false,
			watch: false,
			
			plugins: [
				
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
	gulp.watch(config.jsSrcDir + '**/*.ts', gulp.series(['js::atum']));

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