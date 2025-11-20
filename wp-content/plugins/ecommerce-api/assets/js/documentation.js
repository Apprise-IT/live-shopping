jQuery(document).ready(function($) {
    $('.endpoint-header').on('click', function() {
        $(this).next('.endpoint-content').slideToggle(300);
        $(this).find('.toggle-icon').text(function(_, text) {
            return text === '▼' ? '▶' : '▼';
        });
    });

    $('#collapse-all').on('click', function() {
        $('.endpoint-content').slideUp(300);
        $('.toggle-icon').text('▶');
    });

    $('#expand-all').on('click', function() {
        $('.endpoint-content').slideDown(300);
        $('.toggle-icon').text('▼');
    });

    $('.docs-navigation a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('html, body').animate({
            scrollTop: $(target).offset().top - 100
        }, 500);
    });
});
