var gulp        = require('gulp');
    browserSync = require('browser-sync').create();
    sass        = require('gulp-sass');
    gutil       = require( 'gulp-util' );
    ftp         = require( 'vinyl-ftp' );

var reload = browserSync.reload;
const config = require('./ftp-config.json');

var globs = [
    'css/*.css',
    '!compass/**',
    '!node_modules/**',
    '!gulpfile.js',
    '!package.json',
    '!package-lock.json',
    '!ftp-config.js'
];

function getFtpConnection() {
    return ftp.create({
        host: config.host,
        port: config.port,
        user: config.username,
        password: config.password,
        remotePath: config.remotePath,
        protocol: config.protocol,
        parallel: 5,
        log: gutil.log
    });
}

// gulp.task('serve', gulp.series('sass', function() {
//     browserSync.init({
//             proxy: config.proxy,
//             ghostMode: false
//         });
//
//     gulp.watch("css/**/*.scss", ['inject']);
// }));

function serve (done) {
    browserSync.init({
        proxy: config.proxy,
        ghostMode: false
    });

    gulp.watch("css/**/*.scss", gulp.series('sass'));
    gulp.watch("*.html").on('change', () => {
        browserSync.reload();
    });

    done();
}

gulp.task(serve);

gulp.task('sass', function(done) {
     gulp.src("css/**/*.scss")
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest("css"))
         .pipe(browserSync.stream());
    done();
});

gulp.task('inject',  gulp.parallel(ftp_deploy, 'sass', function(done) {
    gulp.src("css/*.css")
    .pipe(browserSync.stream());
    done();
}));

function ftp_deploy () {
    var conn = getFtpConnection();
    return gulp.src( globs, { base: '.', buffer: false } )
        .pipe( conn.newer( config.remotePath ) ) // only upload newer files
        .pipe( conn.dest( config.remotePath ) );

};
gulp.task(ftp_deploy);


gulp.task('default', gulp.parallel('serve'));
