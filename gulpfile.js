var gulp  = require('gulp');
var chmod = require('gulp-chmod');

gulp.task('dev', function(){
    return gulp.src(['*.php', '*.ini', '*.txt', 'css/*.css', 'js/*.js', '!gulpfile.js'], {"base":"."})
        .pipe(chmod(0644))
        .pipe(gulp.dest('/mnt/sdb2/ClassicPress/wp-content/plugins/classic-shortcode-tester'))
});

gulp.task('prod', function(){
    return gulp.src(['*.php', '*.ini', '*.txt', 'css/*.css', 'js/*.js', '!gulpfile.js', '!debug_utilities.php'], {"base":"."})
        .pipe(chmod(0644))
        .pipe(gulp.dest('/var/www/html/wp-content/plugins/shortcode-tester'))
});

