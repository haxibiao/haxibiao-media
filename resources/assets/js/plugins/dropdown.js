// 用于匹配backdrop类的字符串
var backdrop = '.dropdown-backdrop';
// 用于匹配含有data-toggle触发器的元素的字符串
var toggle = '[data-toggle="dropdown"]';
// dropdown-target触发器的元素的字符串
var DROPDOWN_BOX = 'dropdown-target';
// 获取触发事件类型
var TOGGLE_EVENT = 'dropdown-toggle';

// 绑定触发元素的点击事件
var Dropdown = function(element) {
    $(element).on('click.app.dropdown', this.toggle);
};

// 先调用clearMenu隐藏所有下拉框，在展示当前的下拉框
Dropdown.prototype.toggle = function(e) {
    var $this = $(this);
    if ($this.is('.disabled, :disabled')) return;
    // 包含下拉框的容器
    var $parent = getParent($this);
    // 通过容器切换open类，来控制下拉框的显示与隐藏
    var isActive = $parent.hasClass('open');
    // 先隐藏页面的所有下拉框
    clearMenus();
    // 如果点击元素的容器没有.open（即下拉框隐藏）
    if (!isActive) {
        // 用ontouchstart来判断是否移动端
        if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
            // if mobile we use a backdrop because click events don't delegate
            // 移动端的click事件不会冒泡到document？实验证明，没有这一段，也能实现点击下拉框外关闭的效果
            // 移动端插入一个dropdown-backdrop元素（全屏但z-index比下拉框小），做点击下拉框外时隐藏的效果
            $(document.createElement('div'))
                .addClass('dropdown-backdrop')
                .insertAfter($(this))
                .on('click', clearMenus);
        }

        var relatedTarget = { relatedTarget: this };
        $parent.trigger((e = $.Event('show.bs.dropdown', relatedTarget)));

        if (e.isDefaultPrevented()) return;

        $this.trigger('focus').attr('aria-expanded', 'true');

        $parent.toggleClass('open').trigger('shown.bs.dropdown', relatedTarget);
    }
    // 在这里return false阻止冒泡到document上，导致下拉框隐藏
    return false;
};

// 先需找在触发器通过data-target或href指定的容器，若没有则默认是其父容器
function getParent($this) {
    var selector = $this.attr('data-target');
    if (!selector) {
        selector = $this.attr('href');
        selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, ''); // strip for ie7
    }
    var $parent = selector && $(selector);
    // 可以在触发器上用data-target或href来自定义父容器
    // 如果没有自定义容器，则默认是触发器的父元素
    return $parent && $parent.length ? $parent : $this.parent();
}

// each遍历，把所有容器的open类去除，以此保证只能有一个出现
function clearMenus(e) {
    if (e && e.which === 3) return;
    $(backdrop).remove();
    $(toggle).each(function() {
        var $this = $(this);
        var $parent = getParent($this);
        var relatedTarget = { relatedTarget: this };
        // 如果容器没有open，就不继续
        if (!$parent.hasClass('open')) return;
        // 例外条件
        if (e && e.type == 'click' && /input|textarea/i.test(e.target.tagName) && $.contains($parent[0], e.target))
            return;

        $parent.trigger((e = $.Event('hide.bs.dropdown', relatedTarget)));

        if (e.isDefaultPrevented()) return;
        // 不明白干什么的
        $this.attr('aria-expanded', 'false');
        // 隐藏下拉框
        $parent.removeClass('open').trigger('hidden.bs.dropdown', relatedTarget);
    });
}

export default Dropdown;
