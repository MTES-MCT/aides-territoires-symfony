import Routing from 'fos-router';

$(function() {
    $(document).on({
        change: function(e) {
            var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

            $.ajax({
                url: Routing.generate('app_perimeter_ajax_datas'),
                method: 'POST',
                data: {
                    perimeter_id: $('#register_commune_perimeter').val(),
                    _token: csrfToken
                },
                dataType: 'json',
                success: function(data){
                    if (typeof data.results !== 'undefined') {
                        $.each(data.results, function (i) {
                            let entry = data.results[i];
                            if (entry.prop === "mairie_email") {
                                $("#register_commune_email").val(entry.value);
                            }
            
                            if ($("#register_commune_beneficiaryFunction").val() == "mayor") {
                                if (entry.prop === "mayor_first_name") {
                                    $("#register_commune_firstname").val(entry.value);
                                }
            
                                if (entry.prop === "mayor_last_name") {
                                    $("#register_commune_lastname").val(entry.value);
                                }
                            } else {
                                // Reinit these fields if "Mayor" had previously been selected
                                $("#register_commune_firstname").val("");
                                $("#register_commune_lastname").val("");
                            }
                        });
                    }
                },
                error: function () {
                    $("#register_commune_firstname").val("");
                    $("#register_commune_lastname").val("");
                }
            });
        }
    }, '#register_commune_perimeter, #register_commune_beneficiaryFunction');
});