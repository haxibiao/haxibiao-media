const mix = require("laravel-mix");

//修复 https://github.com/element-plus/element-plus/issues/4132
mix.webpackConfig({
    module: {
        rules: [
            {
                test: /\.mjs$/,
                resolve: { fullySpecified: false },
                include: /node_modules/,
                type: "javascript/auto",
            },
        ],
    },
});

mix.setPublicPath("public");

// media.css
mix.sass("resources/assets/sass/media.scss", "css/media.css");

// media.js
mix.ts("resources/assets/js/media.ts", "public/js/_media.js").vue({ version: 3 });
mix.scripts(["public/js/_media.js", "node_modules/hls.js/dist/hls.js"], "public/js/media.js");

mix.version();

mix.browserSync("localhost:8000");
