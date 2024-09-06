$(function(){
    $(document).on({
        click: function(e) {
            $('#notification_delete_idNotification', 'form[name="notification_delete"]').val($(this).attr('data-id-notification'));
        }
    }, '.fr-icon-delete-line');
});