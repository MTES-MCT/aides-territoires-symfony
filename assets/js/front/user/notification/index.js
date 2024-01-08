$(function(){
    $(document).on({
        click: function(e) {
            console.log($(this).attr('data-id-notification'));
            $('#notification_delete_idNotification', 'form[name="notification_delete"]').val($(this).attr('data-id-notification'));
        }
    }, '.fr-icon-delete-line');
});