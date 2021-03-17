let mix = require('laravel-mix');
let { env } = require('minimist')(process.argv.slice(2));

//电影模块 css
mix.sass('resources/assets/sass/movie.scss', 'public/css/movie/_movie.css');
mix.sass('resources/assets/sass/movie/home.scss', 'public/css/movie');
mix.sass('resources/assets/sass/movie/play.scss', 'public/css/movie');
mix.sass('resources/assets/sass/movie/search.scss', 'public/css/movie');
mix.sass('resources/assets/sass/movie/category.scss', 'public/css/movie');
mix.sass('resources/assets/sass/movie/favorites.scss', 'public/css/movie');

mix.styles(
    [
        'public/css/movie/_movie.scss',
        'public/css/movie/home.scss',
        'public/css/movie/play.scss',
        'public/css/movie/search.scss',
        'public/css/movie/category.scss',
        'public/css/movie/favorites.scss',
    ],
    'public/vendor/media/css/movie.css',
).version();

//电影模块 js
mix.js('resources/assets/js/movie.js', 'public/js/movie/_movie.js');
mix.js('resources/assets/js/play.js', 'public/js/movie');
mix.js('resources/assets/js/home.js', 'public/js/movie');
mix.scripts(
    [
        'public/js/movie/_movie.js',
        'public/js/movie/play.js',
        'public/js/movie/home.js',
        'node_modules/hls.js/dist/hls.js',
    ],
    'public/vendor/media/js/movie.js',
).version();
