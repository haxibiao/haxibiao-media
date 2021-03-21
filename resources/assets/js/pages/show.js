$(document).ready(function() {
    const element = $('.video-info .video-right .video-desc');
    $(element)
        .children('i')
        .on('click', function toggleDesc() {
            if ($(this).text() === '展开') {
                $(this).text('收起');
                $(element).removeClass('webkit-ellipsis');
            } else {
                $(this).text('展开');
                $(element).addClass('webkit-ellipsis');
            }
        });
});
