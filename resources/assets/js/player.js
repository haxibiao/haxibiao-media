import Vue from 'vue';
import Echo from 'laravel-echo';

window.io = require('socket.io-client');
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: 'https://neihandianying.com' + ':6001',
});

// movie
Vue.component('video-player', require('./components/player/VideoPlayer.vue').default);
Vue.component('movie-player', require('./components/player/MoviePlayer.vue').default);
Vue.component('movie-editing', require('./components/player/MovieEditing.vue').default);
