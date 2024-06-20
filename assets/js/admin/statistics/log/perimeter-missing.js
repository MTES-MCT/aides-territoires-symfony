require('datatables');

$(function(){
    for (const property in logAidSearchsByDept) {
        $('path[data-num="' + logAidSearchsByDept[property].dept + '"]').addClass(logAidSearchsByDept[property].class);
        var tooltip = new bootstrap.Tooltip($('path[data-num="' + logAidSearchsByDept[property].dept + '"]'), {
            title: logAidSearchsByDept[property].count + ' recherches sur des périmètres manquants',
        });
    }

    $('.dataTable').DataTable({
        info: false,
        'language': datatables_fr_strings,
    });
});
