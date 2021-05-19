import Vue from 'vue';
import axios from 'axios';
import jquery from 'jquery';

window.$axios = axios;
window.$ = window.jQuery = jquery;
Object.defineProperty(window, 'GLOBAL', {
    value: {},
    writable: false,
    enumerable: true,
    configurable: false
});
