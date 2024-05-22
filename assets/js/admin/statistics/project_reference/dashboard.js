require('datatables');

$(function(){
    $('.dataTable').DataTable({
        info: false,
        'language': datatables_fr_strings,
        "pageLength": 100
    });
});