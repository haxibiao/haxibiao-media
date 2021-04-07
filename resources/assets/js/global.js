import Vue from 'vue';
import axios from 'axios';
import { optionalChaining } from './plugins/vue-properties';

// window.property
Object.defineProperty(window, 'GLOBAL', {
    value: {},
    writable: false,
    enumerable: true,
    configurable: false,
});
// $bus
window.$bus = GLOBAL.vueBus = new Vue();
window.$bus.state = {
    answer: {
        answerIds: [],
    },
};
//vue plugin/prototype
Vue.prototype.$http = axios;
Vue.prototype.$user = window.user || {};
Vue.prototype.$optional = optionalChaining;
