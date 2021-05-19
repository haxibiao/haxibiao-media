import Vue from 'vue';
import axios from 'axios';
import { optional } from './plugins/vue-properties';
require('es6-promise').polyfill();
require('./initial');
require('./bootstrap');
require('./element');

window.$bus = new Vue();
Vue.prototype.$http = axios;
Vue.prototype.$user = window.user || {};
Vue.prototype.$optional = optional;

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
