require('datatables');
require ('../project/map.js');

$(function(){
    $('form[name="county_select"]').on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select');

    if(typeof commune_search !== 'undefined' && commune_search){
        var order = [[ 0, 'asc' ]];
    }else{
        var order = [[ 1, 'asc' ]];
    }

    $('#validated_projects_table').DataTable({
        info: false,
        "order": order,
        "language": datatables_fr_strings,
    });
});