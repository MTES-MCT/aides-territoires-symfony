require('datatables');

$(function(){
    $(document).on({
        click: function(e) {
            $('#project_delete_idProject', 'form[name="project_delete"]').val($(this).attr('data-project-id'));
        }
    }, '.fr-icon-delete-line');

    $('#table-projects').DataTable({
        info: false,
        "language": datatables_fr_strings,
        "pageLength": 50
    });
});