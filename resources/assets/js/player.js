require('./bootstrap');
require('es6-promise').polyfill();
import Vue from 'vue';

import Echo from 'laravel-echo';
import { TimePicker, Input, Select, Option, Button, Popover, Message, Loading } from 'element-ui';

window.io = require('socket.io-client');
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: 'https://neihandianying.com' + ':6001',
});

//element
Vue.component('el-time-select', TimePicker);
Vue.component('el-input', Input);
Vue.component('el-select', Select);
Vue.component('el-option', Option);
Vue.component('el-button', Button);
Vue.component('el-popover', Popover);
Vue.use(Loading.directive);
Vue.prototype.$loading = Loading.service;
Vue.prototype.$message = Message;

// movie
Vue.component('video-player', require('./components/player/VideoPlayer.vue').default);
Vue.component('movie-player', require('./components/player/MoviePlayer.vue').default);
Vue.component('movie-editing', require('./components/player/MovieEditing.vue').default);
