$(function(){
    $(document).on({
        click: function(e) {
            $('#project_delete_idProject', 'form[name="project_delete"]').val($(this).attr('data-project-id'));
        }
    }, '.fr-icon-delete-line');
});