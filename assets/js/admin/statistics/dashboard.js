require('datatables');

$(function(){

    if ($('#table-top-aids').length) {
        $('#table-top-aids').DataTable({
            info: false,
            "order": [[1, 'desc']],
            "language": datatables_fr_strings,
        });
    }

});