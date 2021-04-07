require('es6-promise').polyfill();
require('./bootstrap');
require('./global');
require('./element');
const Vue = require('vue');

// basic comment
Vue.component('video-player', require('./components/player/VideoPlayer.vue').default);
Vue.component('pagination', require('./components/comment/Pagination.vue').default);
Vue.component('comment-send', require('./components/comment/CommentSend.vue').default);
Vue.component('comment-item', require('./components/comment/CommentItem.vue').default);
Vue.component('comment-module', require('./components/comment/CommentModule.vue').default);

// 电影播放器
require('./player');

// 基本页面
require('./pages/home');
require('./pages/show');

const app = new Vue({
    el: '#app',
});
