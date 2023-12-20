require('datatables');

$(function(){
    $(document).ready( function () {

        if(commune_search){
            var order = [[ 0, 'asc' ], [ 2, 'asc' ], [ 1, 'asc' ]];
        }else{
            var order = [[ 1, 'asc' ], [ 0, 'asc' ]];
        }

        $('#validated_projects_table').DataTable({
            info: false,
            "order": order,
            "language": datatables_fr_strings,
        });
    } );
});