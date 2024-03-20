import Routing from 'fos-router';

var currentRoute = 0;
const ajaxRoutes = [
    {
        route: 'app_portal_portal_stats_ajax_top_aids',
        target: '#top-aids-container'
    },
    {
        route: 'app_portal_portal_stats_ajax_aids_view_by_month',
        target: '#chart-views-by-month-container'
    },
    {
        route: 'app_portal_portal_stats_ajax_aids_view_by_organization_type',
        target: '#chart-views-by-organization-container'
    },
    {
        route: 'app_portal_portal_stats_ajax_visits_by_month',
        target: '#chart-visits-by-month-container'
    }    
];


$(function() {
    ajaxStats();
});

function ajaxStats()
{
    $.ajax({
        url: Routing.generate(ajaxRoutes[currentRoute].route),
        type: 'GET',
        success: function (response) {
            $(ajaxRoutes[currentRoute].target).html(response);
            currentRoute++;
            if (typeof ajaxRoutes[currentRoute] !== 'undefined') {
                ajaxStats();
            }
        }
    });
}
