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
            if (typeof _paq !== 'undefined') {
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
            if (typeof _paq !== 'undefined') {
                _paq.push(['trackEvent', 'Fiche aide', 'Clic lien candidater', (typeof aidSlug !== 'undefined') ? aidSlug : '']);
            }

            // if application_url is a link to a prepopulate Démarches-Simplifiées folder
            // create a AidCreateDSFolderEvent
            if (PREPOPULATE_APPLICATION_URL) {
                var params = {
                    aidSlug: (typeof aidSlug !== 'undefined') ? aidSlug : '',
                    organization: (typeof ORGANIZATION !== 'undefined') ? ORGANIZATION : '',
                    user: (typeof USER !== 'undefined') ? USER : '',
                    ds_folder_url: (typeof DS_FOLDER_URL !== 'undefined') ? DS_FOLDER_URL : '',
                    ds_folder_id: (typeof DS_FOLDER_ID !== 'undefined') ? DS_FOLDER_ID : '',
                    ds_folder_number: (typeof DS_FOLDER_NUMBER !== 'undefined') ? DS_FOLDER_NUMBER : '',
                };

                $.ajax({
                    type: 'POST',
                    url: Routing.generate('app_log_ajax'),
                    dataType: 'json',
                    data: {
                        type: 'createDsFolder',
                        params: params
                    }
                })
                if (typeof _paq !== 'undefined') {
                    _paq.push(['trackEvent', 'Fiche aide', 'Clic lien vers le dossier Démarches-Simplifiées prérempli', aid_slug]);
                }
            }
        }
    }, '.at-application-url-btn');

    $('div#contact').on({
        click: function() {
            // Send an event to Matomo
            if (typeof _paq !== 'undefined') {
                _paq.push(['trackEvent', 'Fiche aide', 'Voir lien du porteur', (typeof aidSlug !== 'undefined') ? aidSlug : '', this.href]);
            }
        }
    }, 'a');

    
});