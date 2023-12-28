import Routing from 'fos-router';

$(function(){
    $(document).on({
        click: function(e){
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    params: params,
                    type: 'register-from-next-page-warning',
                },
                success: function(data){
                    console.log(data);
                }
            });
            
        }
    }, 'a#register-from-next-page-warning');
});