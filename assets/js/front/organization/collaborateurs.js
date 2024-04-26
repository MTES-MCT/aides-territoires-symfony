
$(function(){
    $(document).on({
        click: function (e) {
            $(this).parents('tr:first').remove();
        }
    }, '.btn-delete-collaborator');
});