import Routing from 'fos-router';

$(function(){
    var dateCreateMin = $('#date_range_dateMin').val();
    var dateCreateMax = $('#date_range_dateMax').val();

    $.ajax({
        url: Routing.generate('admin_statistics_ajax_get_matomo_stats_aid_views'),
        type: 'POST',
        data: {
            'dateCreateMin': dateCreateMin,
            'dateCreateMax': dateCreateMax
        },
        success: function(data) {
            if (typeof data.nbAidViews !== undefined) {
                $('#nb-aid-views').text(data.nbAidViews.toLocaleString('fr-FR'));
            }
            if (typeof data.nbAidVisits !== undefined) {
                $('#nb-aid-visits').text(data.nbAidVisits.toLocaleString('fr-FR'));
            }
            if (typeof data.nbAids !== undefined) {
                $('#nb-aids').text(data.nbAids.toLocaleString('fr-FR'));
            }
        },
    });
});