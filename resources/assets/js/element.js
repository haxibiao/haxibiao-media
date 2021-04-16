import Vue from 'vue';
import { TimePicker, Input, Select, Option, Button, Popover, Message, Loading, Alert } from 'element-ui';
import 'element-ui/lib/theme-chalk/base.css';
import 'element-ui/lib/theme-chalk/time-picker.css';
import 'element-ui/lib/theme-chalk/input.css';
import 'element-ui/lib/theme-chalk/select.css';
import 'element-ui/lib/theme-chalk/button.css';
import 'element-ui/lib/theme-chalk/popover.css';
import 'element-ui/lib/theme-chalk/alert.css';
import 'element-ui/lib/theme-chalk/loading.css';
import 'element-ui/lib/theme-chalk/message.css';

Vue.component('el-time-select', TimePicker);
Vue.component('el-input', Input);
Vue.component('el-select', Select);
Vue.component('el-option', Option);
Vue.component('el-button', Button);
Vue.component('el-popover', Popover);
Vue.component('el-alert', Alert);
Vue.use(Loading.directive);
Vue.prototype.$loading = Loading.service;
Vue.prototype.$message = Message;