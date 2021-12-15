require('./bootstrap');
require('es6-promise').polyfill();

import { createApp } from 'vue';

// 弹幕需要? 没开始做
// window.io = require("socket.io-client");

import VideoPlayer from './components/player/VideoPlayer.vue';
import Pagination from './components/comment/Pagination.vue';
import CommentSend from './components/comment/CommentSend.vue';
import CommentItem from './components/comment/CommentItem.vue';
import CommentModule from './components/comment/CommentModule.vue';
// 电影播放器
import MoviePlayer from './components/player/MoviePlayer.vue';
import MovieEditing from './components/player/MovieEditing.vue';
import PlayLines from './components/player/PlayLines.vue';

//邀请海报弹层
import InviteModal from './components/InviteModal.vue';

// 基本页面
require('./pages/home');
require('./pages/show');

const app = createApp({
  components: {
    VideoPlayer,
    Pagination,
    CommentSend,
    CommentItem,
    CommentModule,
    MoviePlayer,
    MovieEditing,
    PlayLines,
    InviteModal
  }
});
// prototype
import axios from 'axios';
import { EVENT_BUS } from './config';
app.config.globalProperties.$bus = EVENT_BUS;
app.config.globalProperties.$http = axios;
app.config.globalProperties.$user = (window as any).user || {};

import ElementPlus from 'element-plus';
app.use(ElementPlus);

app.mount('#app');
