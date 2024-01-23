
$(function(){
    $(document).on({
        click: function (e) {
            $('#btn-confirm-exclude').attr('href', $(this).attr('data-url'));
        }
    }, '.exclude-collaborators-modal-btn');
});