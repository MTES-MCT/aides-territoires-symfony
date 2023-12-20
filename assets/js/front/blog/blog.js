$(function() {
    $(document).on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select[name="blog_post_category_filter[blogPostCategory]"]');
});