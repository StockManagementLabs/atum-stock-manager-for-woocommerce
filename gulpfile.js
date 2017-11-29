// Load all the modules from package.json
var gulp         = require('gulp'),
    plumber      = require('gulp-plumber'),
    gulpif       = require('gulp-if'),
    watch        = require('gulp-watch'),
    livereload   = require('gulp-livereload'),
    //jshint       = require('gulp-jshint'),
    //stylish      = require('jshint-stylish'),
    //uglify       = require('gulp-uglify'),
    //rename       = require('gulp-rename'),
    notify       = require('gulp-notify'),
    wrap         = require('gulp-wrap'),
    //include      = require('gulp-include'),
    sass         = require('gulp-sass'),
    sourcemaps   = require('gulp-sourcemaps'),
    imagemin     = require('gulp-imagemin'),
	composer     = require('gulp-composer'),
	filter       = require('gulp-filter');

// Plugin version
var version = '1.3.2';

// Global config
var config = {
	
	assetsDir : './assets',

	devUrl    : 'http://atum.dev',
	production: false,

	// decorate
	decorate: {

		templateJS: [
			'/** \n',
			' * ATUM Stock Manager for WooCommerce JS \n',
			' * @version ' + version + ' \n',
			' * @authors Salva Machí and Jose Piera \n',
			' *\n',
			' * Author URI: https://sispixels.com/ \n',
			' * License : ©2017 Stock Management Labs \n',
			' */ \n',
			'\n;(function($) { \n \t\'use strict\';\n\n',
			'<%= contents %>\n\n',
			'})(jQuery);\njQuery.noConflict();'
		].join(''),

		templateCSS: [
			'/** \n',
			' * ATUM Stock Manager for WooCommerce CSS \n',
			' * @version ' + version + ' \n',
			' * @authors Salva Machí and Jose Piera \n',
			' *\n',
			' * Author URI: https://sispixels.com/ \n',
			' * License : ©2017 Stock Management Labs \n',
			' */ \n',
			'\n<%= contents %>\n'
		].join('')

	}
};

// CLI options
var enabled = {
	// Enable static asset revisioning when `--production`
	rev: config.production,
	// Disable source maps when `--production`
	maps: !config.production,
	// Fail styles task on error when `--production`
	failStyleTask: config.production,
	// Fail due to JSHint warnings only when `--production`
	failJSHint: config.production,
	// Strip debug statments from javascript when `--production`
	stripJSDebug: config.production
};


// Default error handler
var onError = function (err) {
	console.log('An error occured:', err.message);
	this.emit('end');
}


// Jshint outputs any kind of javascript problems you might have
// Only checks javascript files inside /src directory
/*gulp.task('jshint', function () {
	return gulp.src('./js/src/*.js')
		.pipe(jshint())
		.pipe(jshint.reporter(stylish))
		.pipe(jshint.reporter('fail'));
});*/


// Concatenates all JS files and creates two versions: normal and minified.
// It's dependent on the jshint task to succeed.
/*gulp.task('scripts', ['jshint'], function () {
	return gulp.src([
			'./js/src/scripts.js'
		])
		.pipe(include())
		.pipe(rename({basename: 'scripts'}))
		.pipe(wrap(config.decorate.templateJS))
		.pipe(gulp.dest('./js/dist'))
		// Normal done, time to create the minified javascript (scripts.min.js)
		// remove the following 3 lines if you don't want it
		.pipe(uglify())
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('./js/dist'))
		.pipe(notify({message: 'scripts task complete'}))
		.pipe(livereload());
});*/

// As with javascripts this task creates two files, the regular and
// the minified one. It automatically reloads browser as well.
var options = {

	sass: {
		errLogToConsole: !enabled.failStyleTask,
		outputStyle    : !config.production ? 'expanded' : 'compressed',
		//precision      : 10,
		includePaths   : [
			'.',
			config.assetsDir + '/scss'
		]
		//imagePath: 'assets/img'
	}

};

//
// sass tasks
//---------------

gulp.task('sass::atum', function () {

	var destDir = config.assetsDir + '/css';

	return gulp.src([
			config.assetsDir + '/scss/*.scss'
		])
		.pipe(plumber({errorHandler: onError}))
		.pipe( gulpif(enabled.maps, sourcemaps.init()) )
		.pipe(sass(options.sass))
		.pipe(wrap(config.decorate.templateCSS))
		.pipe( gulpif(enabled.maps, sourcemaps.write('.', {
				sourceRoot: 'assets/scss/'
			}))
		)
		.pipe(gulp.dest(destDir))
		.pipe(notify({message: 'sass task complete'}))
		.pipe(filter("**/*.css"))
		.pipe(livereload());

});

//
// Optimize Images
// ---------------

gulp.task('images::atum', function () {
	return gulp.src('./images/**/*')
		.pipe(imagemin({progressive: true, svgoPlugins: [{removeViewBox: false}]}))
		.pipe(gulp.dest('./images'))
		.pipe(notify({message: 'Images task complete'}));
});

//
// Composer packages installation
// ------------------------------

gulp.task('composer::install', function () {
	// Installation + optimization
	composer({ cwd: '.', o: true });
});

gulp.task('composer::update', function () {
	// Update + optinmization
	composer('update', {cwd: '.', o: true});
});

gulp.task('composer::optimize', function () {
	// Just optimization (classmap autoloader array generation)
	composer('dumpautoload', {cwd: '.', optimize: true});
});


//
// Start the livereload server and watch files for changes
// -------------------------------------------------------

gulp.task('watch::atum', function () {

	livereload.listen();

	// don't listen to whole js folder, it'll create an infinite loop
	//gulp.watch(['./assets/js/**/*.js', '!./assets/js/dist/*.min.js'], ['scripts'])

	gulp.watch(config.assetsDir + '/scss/**/*.scss', ['sass::atum']);
	
	gulp.watch('./assets/images/**/*', ['images::atum']);

	gulp.watch([

		// PHP files
		'./**/*.php',

		// JS files
		config.assetsDir + '/js/**/*.js',

		// Images
		config.assetsDir + '/images/**/*',

		// Excludes
		'!' + config.assetsDir + '/js/**/*.min.js',
		'!bower_components',
		'!node_modules',

	]).on('change', function (file) {
		// reload browser whenever any PHP, SCSS, JS or image file changes
		livereload.changed(file);
	});
});

// Do nothing in this task, just triggers the dependent 'watch'
gulp.task('default', ['watch::atum'], function () {
	
});