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

        $.ajax({
            url: Routing.generate('app_log_ajax'),
            method: 'POST',
            data: {
                type: 'blogPromotionPostDisplay',
                params: params,
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
            var params = {
                blogPromotionPostId: $(this).attr('id'),
                host: window.location.href,
                querystring: window.location.search,
            };

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    type: 'blogPromotionPostClick',
                    params: params,
                },
                dataType: 'json',
                success: function(data){
                }
            });
        }
    }, '.btn-blog-promotion-post');
});