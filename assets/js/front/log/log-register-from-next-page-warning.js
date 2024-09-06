import Routing from 'fos-router';

$(function(){
    $(document).on({
        click: function(e){
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());

            var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    params: params,
                    type: 'register-from-next-page-warning',
                    _token: csrfToken
                },
                dataType: 'json',
                success: function(data){
                }
            });
            
            // Send an event to Matomo
            if (_paq) {
                _paq.push(['trackEvent', 'Compte Utilisateur', 'Clic sur le bouton Register-from-Next-Page-Warning']);
            }
        }
    }, 'a#register-from-next-page-warning');
});