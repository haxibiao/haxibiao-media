const mix = require('laravel-mix');

//修复 https://github.com/element-plus/element-plus/issues/4132
mix.webpackConfig({
  module: {
    rules: [
      {
        test: /\.mjs$/,
        resolve: { fullySpecified: false },
        include: /node_modules/,
        type: 'javascript/auto'
      }
    ]
  }
});

mix.setPublicPath('public');

// media.css
mix.sass('resources/assets/sass/media.scss', 'css/media.css');

// media.js
mix.ts('resources/assets/js/media.ts', 'public/js/media.js').vue({ version: 3 });

// hls.js 没必要强制合并进入 media.js 前端模板记得引入即可
// <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>;
mix.version();

mix.browserSync('localhost:8000');
