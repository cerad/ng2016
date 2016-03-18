const path   = require('path');
const gulp   = require('gulp');
const concat = require('gulp-concat');
const concatCss = require('gulp-concat-css');

const appPublicDir   = path.join(__dirname, 'src/AppBundle/Resources/public');
const nodeModulesDir = path.join(__dirname,'node_modules');

const stylesTask = function() {
    // Control the order
    gulp.src([
            appPublicDir + '/common.css',
            appPublicDir + '/schedule.css',
            appPublicDir + '/app.css',
            appPublicDir + '/cssmenu.css'
        ])
        .pipe(concat("zayso.css"))
        .pipe(gulp.dest('web/css'));
};
gulp.task('styles',stylesTask);

const nodeModulesTask = function() {

    gulp.src([
            path.join(nodeModulesDir,'normalize.css/normalize.css'),
            path.join(nodeModulesDir,'bootstrap/dist/css/bootstrap.min.css'),
            path.join(nodeModulesDir,'bootstrap/dist/css/bootstrap.min.css.map')
        ])
        .pipe(gulp.dest('web/css'));

    gulp.src([
            path.join(nodeModulesDir,'jquery/dist/jquery.min.js'),
            path.join(nodeModulesDir,'jquery/dist/jquery.min.map')
        ])
        .pipe(gulp.dest('web/js'));
};
gulp.task('node_modules',nodeModulesTask);

const buildTask = function()
{
    stylesTask();
    nodeModulesTask();
};
gulp.task('build',buildTask);

const watchTask = function()
{
    buildTask();

    // Why the warnings, seems to work fine
    gulp.watch([appPublicDir + '/*.css'],  ['styles']);
};
gulp.task('watch',watchTask);