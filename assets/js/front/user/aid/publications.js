require('datatables');

$(function(){
    $('#table-aids').DataTable({
        info: false,
        "language": datatables_fr_strings,
        "pageLength": 50
    });
});