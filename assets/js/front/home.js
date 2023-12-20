require ('./perimeter/map.js');

$(function(){
    $('form[name="county_select"]').on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select');

    $('.entity-checkbox-absolute-type-wrapper').entity_checkbox_absolute_type();

    $('#top-tabs').on({
        click: function(e) {
            $('#top-tabs').addClass('classic');
            setTimeout(function(){
                $('#top-tabs').removeClass('classic');
            }, 500);
        }
    }, 'button');
});