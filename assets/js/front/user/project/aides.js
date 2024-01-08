$(function(){
    $(document).on({
        click: function(e) {
            $('#aid_project_delete_idAidProject', 'form[name="aid_project_delete"]').val($(this).attr('data-id_aid_project'));
        }
    }, '.fr-icon-delete-line');
});