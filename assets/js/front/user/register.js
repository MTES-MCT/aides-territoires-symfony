require('./pre_fill_organization_name.js');
require('./toggle_acquisition_channel_related_field.js');
require('./toggle_beneficiary_related_fields.js');
require('./toggle_intercommunality_type_field.js');

$(document).ready(function () {
    $(document).on({
        change: function (e) {
            completeOrganizationName();
        }
    }, '#register_organizationType');

    $(document).on({
        change: function (e) {
            completeOrganizationName();
        }
    }, '#register_perimeter');
    
    
});

function completeOrganizationName()
{
    var perimeterTxt = $('#register_perimeter').parents().find('div.item:first').text();
    var organizationType = $('option:selected', '#register_organizationType').text();

    if (organizationType == 'Commune' && perimeterTxt != '') {
        var organizationName = 'Mairie de ' + perimeterTxt.replace(/ *\([^)]*\) */g, " ");;
    } else {
        organizationName = '';
    }
    $('#register_organizationName').val(organizationName);
}