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
  h > 0 && arr.unshift(zero(h));
  return arr.join(':');
}

function toSecond(second) {
  var date = new Date(second);
  var h = date.getHours();
  var m = date.getMinutes();
  var s = date.getSeconds();

  return h * 3600 + m * 60 + s;
}

// 消息日期（与前一条消息日期相同则不展示）
function messageDateStr(currentDate, prevDate) {
  var current_date_str = new Date(currentDate).toLocaleDateString();
  var prev_date_str = new Date(prevDate).toLocaleDateString();
  if (currentDate && current_date_str !== prev_date_str) {
    return current_date_str;
  }
  return '';
}

function transNumber(number) {
  if (number < 100) {
    return number;
  } else if (number > 99 && number < 1000) {
    return '99+';
  } else if (number > 999 && number < 10000) {
    return '999+';
  } else {
    return '1w+';
  }
}

export default {
  format,
  toSecond,
  transNumber,
  messageDateStr
};
