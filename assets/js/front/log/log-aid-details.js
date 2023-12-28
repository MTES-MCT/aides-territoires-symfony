import Routing from 'fos-router';

$(function(){
    $(document).on({
        click: function(e){
            var params = {
                aidSlug: (typeof aidSlug !== 'undefined') ? aidSlug : '',
                host: window.location.href,
                querystring: window.location.search,
            };

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    type: 'originUrl',
                    params: params,
                },
                dataType: 'json',
                success: function(data){
                }
            });

            // Send an event to Matomo
            if (_paq) {
                _paq.push(['trackEvent', 'Fiche aide', 'Clic lien vers le descriptif complet', (typeof aidSlug !== 'undefined') ? aidSlug : '']);
            }
            
        }
    }, 'a#origin_url_btn');

    $(document).on({
        click: function(e){
            var params = {
                aidSlug: (typeof aidSlug !== 'undefined') ? aidSlug : '',
                host: window.location.href,
                querystring: window.location.search,
            };

            $.ajax({
                url: Routing.generate('app_log_ajax'),
                method: 'POST',
                data: {
                    type: 'applicationUrl',
                    params: params,
                },
                dataType: 'json',
                success: function(data){
                }
            });
            
            // Send an event to Matomo
            if (_paq) {
                _paq.push(['trackEvent', 'Fiche aide', 'Clic lien candidater', (typeof aidSlug !== 'undefined') ? aidSlug : '']);
            }
        }
    }, '.at-application-url-btn');

    
});