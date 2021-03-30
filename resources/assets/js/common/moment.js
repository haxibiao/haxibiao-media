function format(second) {
    let h = 0,
        m = 0,
        s = parseInt(second, 10);
    if (s > 60) {
        h = parseInt(String(second / 3600), 10);
        m = parseInt(String((second % 3600) / 60), 10);
        s = parseInt(String(second % 60), 10);
    }
    // 补零
    const zero = function(v) {
        return v >> 0 < 10 ? '0' + v : v;
    };
    const arr = [zero(h), zero(m), zero(s)];
    return arr.join(':');
}

function toSecond(second) {
    var date = new Date(second);
    var h = date.getHours();
    var m = date.getMinutes();
    var s = date.getSeconds();

    return h * 3600 + m * 60 + s;
}

export default {
    format,
    toSecond,
};
