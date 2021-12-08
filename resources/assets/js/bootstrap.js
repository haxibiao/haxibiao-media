//兼容以前前端框架的一些经典js组件
window._ = require("lodash");
try {
    window.Popper = require("popper.js").default;
    window.$ = window.jQuery = require("jquery");
    require("bootstrap");
} catch (e) {}

window.axios = require("axios");
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
