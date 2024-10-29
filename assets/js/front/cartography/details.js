require('datatables');

var autocompleteChoicesClicked = false;

$(function(){
    $('form[name="cartography_search"]').on({
        change: function(e) {
            if ($(this).attr('name') == 'cartography_search[departement]') {
                var formAction = $(this).parents('form').attr('action');
                formAction = '/cartographie/'+convertToSlug($(this).find(":selected").text())+'/porteurs/';
                $(this).parents('form').attr('action', formAction);
            }
            $(this).parents('form').submit();
        }
    }, 'select');

    $('.autocomplete-choices').on({
        click: function(e) {
            autocompleteChoicesClicked = true;
        }
    }, 'input[type="checkbox"]');

    $('.widget-autocomplete-multiple-wrapper').checkbox_multiple_search({
        callbackCloseAutocompleteList: function(e) {
            if (autocompleteChoicesClicked) {
                $('form[name="cartography_search"]').submit();
            }
        }
    });


    $('#backers-by-departement table').DataTable({
        info: false,
        "language": datatables_fr_strings,
        order: [[ 4, 'desc' ]]
    });
});