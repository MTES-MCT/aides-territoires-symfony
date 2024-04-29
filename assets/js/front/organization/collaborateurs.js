
$(function(){
    $(document).on({
        click: function (e) {
            $('#btn-confirm-exclude').prop('href', $(this).data('url'));
        }
    }, '.exclude-collaborators-modal-btn');
});