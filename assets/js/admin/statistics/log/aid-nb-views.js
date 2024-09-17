import Routing from 'fos-router';

$(function(){
    var dateCreateMin = $('#date_range_dateMin').val();
    var dateCreateMax = $('#date_range_dateMax').val();

    $.ajax({
        url: Routing.generate('admin_statistics_consultation_ajax_aid_nb_views'),
        type: 'POST',
        data: {
            'dateCreateMin': dateCreateMin,
            'dateCreateMax': dateCreateMax
        },
        success: function(data) {
            if (typeof data.nbAidViews !== undefined) {
                $('#nb-aid-views').text(data.nbAidViews);
            }
        },
    });

    $.ajax({
        url: Routing.generate('admin_statistics_consultation_ajax_aid_nb_views_distinct'),
        type: 'POST',
        data: {
            'dateCreateMin': dateCreateMin,
            'dateCreateMax': dateCreateMax
        },
        success: function(data) {
            if (typeof data.nbAidViewsDistinct !== undefined) {
                $('#nb-aid-views-distinct').text(data.nbAidViewsDistinct);
            }
        },
    });
});