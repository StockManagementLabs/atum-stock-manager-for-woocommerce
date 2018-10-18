// Load all the modules from package.json
var gulp         = require('gulp'),
    plumber      = require('gulp-plumber'),
    gulpif       = require('gulp-if'),
    watch        = require('gulp-watch'),
    livereload   = require('gulp-livereload'),
    notify       = require('gulp-notify'),
    wrap         = require('gulp-wrap'),
    sass         = require('gulp-sass'),
    sourcemaps   = require('gulp-sourcemaps'),
	composer     = require('gulp-composer'),
	filter       = require('gulp-filter');

// Plugin version
var version = '1.4.16';

// Global config
var config = {
	
	assetsDir : './assets',

	devUrl    : 'http://atum.loc',
	production: false, // NOTE: the production tag was causing problems with CSS compression when adding the @charset "UTF-8"

	// decorate
	decorate: {

		templateCSS: [
			'/** \n',
			' * ATUM Inventory Management for WooCommerce CSS \n',
			' * @version ' + version + ' \n',
			' * @author Be Rebel  \n',
			' *\n',
			' * Author URI: https://berebel.io \n',
			' * License : Copyright 2018 Stock Management Labs \n',
			' */\n',
			'\n <%= contents %>'
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
	failStyleTask: config.production
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

	gulp.watch(config.assetsDir + '/scss/**/*.scss', ['sass::atum']);

	gulp.watch([

		// PHP files
		'./**/*.php',

		// JS files
		config.assetsDir + '/js/**/*.js',

		// Images
		config.assetsDir + '/images/**/*',

		// Excludes
		'!' + config.assetsDir + '/js/**/*.min.js',
		'!node_modules',

	]).on('change', function (file) {
		// reload browser whenever any PHP, SCSS, JS or image file changes
		livereload.changed(file);
	});
});

// Default task
gulp.task('default', ['sass::atum'], function () {
	
});