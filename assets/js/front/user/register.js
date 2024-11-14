require('./pre_fill_organization_name.js');
require('./toggle_acquisition_channel_related_field.js');
require('./toggle_beneficiary_related_fields.js');
require('./toggle_intercommunality_type_field.js');

$(function() {
    $(document).on({
        change: function (e) {
            completeOrganizationName();
        }
    }, '#register_organizationType, #organization_edit_organizationType');

    $(document).on({
        change: function (e) {
            completeOrganizationName();
        }
    }, '#register_perimeter, #organization_edit_perimeter');

    $(document).on({
        submit: function (e) {
            $(this).find('button[type="submit"]').prop('disabled', true);
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> En cours...');
        }
    }, 'form[name="register"]');
    
});

function completeOrganizationName()
{
    if ($('#register_perimeter').length) {
        var perimeterTxt = $('#register_perimeter').parents().find('div.item:first').text();
        var organizationType = $('option:selected', '#register_organizationType').text();
    } else {
        var perimeterTxt = $('#organization_edit_perimeter').parents().find('div.item:first').text();
        var organizationType = $('option:selected', '#organization_edit_organizationType').text();
    }


    if (organizationType == 'Commune' && perimeterTxt != '') {
        var organizationName = 'Mairie de ' + perimeterTxt.replace(/ *\([^)]*\) */g, " ");;
    } else {
        organizationName = '';
    }

    if ($('#register_organizationName').length) {
        $('#register_organizationName').val(organizationName);
    } else if ($('#organization_edit_name').length && $('#organization_edit_name').val() == '') {
        $('#organization_edit_name').val(organizationName);
    }
    
}