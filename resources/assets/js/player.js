import Vue from 'vue';
import Echo from 'laravel-echo';

// 弹幕需要
window.io = require('socket.io-client');

// movie
Vue.component('video-player', require('./components/player/VideoPlayer.vue').default);
Vue.component('movie-player', require('./components/player/MoviePlayer.vue').default);
Vue.component('movie-editing', require('./components/player/MovieEditing.vue').default);
