
$(function(){
    $(document).on({
        click: function (e) {
            $('#btn-confirm-exclude').attr('href', $(this).attr('data-url'));
        }
    }, '.exclude-collaborators-modal-btn');

    $(document).on({
        click: function (e) {
            $(this).parents('tr:first').remove();
        }
    }, '.btn-delete-collaborator');
});