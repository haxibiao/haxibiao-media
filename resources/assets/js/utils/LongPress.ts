//参数为: 需长按元素的id、长按之后的逻辑函数func
export default function LongPress(id, func) {
  var timeOutEvent;

  document.querySelector('#' + id).addEventListener('touchstart', function (e) {
    // 开启定时器前先清除定时器，防止重复触发
    clearTimeout(timeOutEvent);
    // 开启延时定时器
    timeOutEvent = setTimeout(function () {
      // 调用长按之后的逻辑函数func
      func();
    }, 300); // 长按时间为300ms，可以自己设置
  });

  document.querySelector('#' + id).addEventListener('touchmove', function (e) {
    // 长按过程中，手指是不能移动的，若移动则清除定时器，中断长按逻辑
    clearTimeout(timeOutEvent);
    /* e.preventDefault() --> 若阻止默认事件，则在长按元素上滑动时，页面是不滚动的，按需求设置吧 */
  });

  document.querySelector('#' + id).addEventListener('touchend', function (e) {
    // 若手指离开屏幕时，时间小于我们设置的长按时间，则为点击事件，清除定时器，结束长按逻辑
    clearTimeout(timeOutEvent);
  });
}
