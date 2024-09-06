import Routing from 'fos-router';

$(function(){
    /*
    Log affichage des blogs post promotionnels
    */
    $('.btn-blog-promotion-post').each(function(){
        var params = {
            blogPromotionPostId: $(this).attr('id'),
            host: window.location.href,
            querystring: window.location.search,
        };

        var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

        $.ajax({
            url: Routing.generate('app_log_ajax'),
            method: 'POST',
            data: {
                type: 'blogPromotionPostDisplay',
                params: params,
                _token: csrfToken
            },
            dataType: 'json',
            success: function(data){
            }
        });
    });

    /*
    Log click des blogs post promotionnels
    */
    $(document).on({
        click: function(e){
            e.preventDefault();
            var params = {
                blogPromotionPostId: $(this).attr('id'),
                host: window.location.href,
                querystring: window.location.search,
            };

            var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    type: 'blogPromotionPostClick',
                    params: params,
                    _token: csrfToken
                },
                dataType: 'json',
                success: function(data){
                    window.location.href = $(e.target).attr('href');
                }
            });
        }
    }, '.btn-blog-promotion-post');
});