// 顶部组件
require('./_header');

// 顶部滚动轮播图
var CarouselMovies = require('./_hotMovies');

$(document).ready(function() {
    // $('#hot-movies').on('transitionend MSTransitionEnd webkitTransitionEnd oTransitionEnd');
    new CarouselMovies({
        container: '#hot-movies',
        items: $('#hot-movies .movie-pic'),
        itemsInfo: $('.hot-movies-intro .movie-info'),
    });
});
