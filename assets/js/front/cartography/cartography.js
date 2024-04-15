require ('../perimeter/map.js');

$(function(){
    $('form[name="county_select"]').on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select');
});