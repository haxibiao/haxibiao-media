const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('public');

mix.js('resources/assets/js/app.js', 'public/js/_media.js')
    .sass('resources/assets/sass/app.scss', 'css/media.css')
    .version();

// media.js
mix.scripts(['public/js/_media.js', 'node_modules/hls.js/dist/hls.js'], 'public/js/media.js').version();

mix.browserSync('l.neihandianying.com');
