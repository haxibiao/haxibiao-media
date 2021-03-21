/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
require('es6-promise').polyfill();
import Vue from 'vue';

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

// bootstraps component
require('bootstrap').Modal;

Vue.prototype.$optional = require('./common/optionalChaining').optionalChaining;
// 为每个vue component注入user
Vue.mixin({
    created: function() {
        this.$user = window.appUser || {};
    },
});

// 基本组件
Vue.component('video-player', require('./components/player/VideoPlayer.vue').default);
// comment
Vue.component('pagination', require('./components/comment/Pagination.vue').default);
Vue.component('comment-send', require('./components/comment/CommentSend.vue').default);
Vue.component('comment-item', require('./components/comment/CommentItem.vue').default);
Vue.component('comment-module', require('./components/comment/CommentModule.vue').default);

// 基本页面
require('./pages/home');
require('./pages/show');

// 电影播放器
// 已重构为单独的 player.js
require('./player');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
