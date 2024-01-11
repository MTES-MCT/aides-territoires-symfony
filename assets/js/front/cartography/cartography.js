require ('../perimeter/map.js');

// require ('./perimeter/map_projects.js');
// require ('./perimeter/department_filter?js');
// require ('./perimeter/department_projects_filter.js');

$(function(){
    $('form[name="county_select"]').on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select');
});