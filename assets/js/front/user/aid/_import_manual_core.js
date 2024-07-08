import Routing from 'fos-router';

$(function(){
    $(document).on({
        keyup: function (e) {
            var thisElt = $(this);
            waitForFinalEvent(function () {
                searchPerimeter(thisElt.val());
            }, 500, 'perimeterSearch');
        }
    }, '#perimeterSearch');



    $(document).on({
        keyup: function (e) {
            var thisElt = $(this);
            waitForFinalEvent(function () {
                searchBacker(thisElt.val());
            }, 500, 'backerSearch');
        }
    }, '#backerSearch');
});

function searchPerimeter(search)
{
    var url = Routing.generate('app_perimeter_ajax_search', {search: search});
    $.get(url, function(data){
        $('#perimeterList').html('');
        if (typeof data.results === 'undefined') {
            return;
        }
        for (var i = 0; i < data.results.length; i++) {
            var trItem =    '<tr>' +
                                '<td>'+parseInt(data.results[i].id)+'</td>' +
                                '<td>'+data.results[i].name+'</td>' +
                                '<td>'+data.results[i].scale+'</td>' +
                                '<td>'+data.results[i].zipcodes.join(', ')+'</td>' + 
                            '</tr>';
            $('#perimeterList').append(trItem);
        }
    });
}

function searchBacker(search)
{
    var url = Routing.generate('app_backer_ajax_search', {search: search});
    $.get(url, function(data){
        $('#backerList').html('');
        if (typeof data.results === 'undefined') {
            return;
        }
        for (var i = 0; i < data.results.length; i++) {
            var trItem =    '<tr>' +
                                '<td>'+parseInt(data.results[i].id)+'</td>' +
                                '<td>'+data.results[i].text+'</td>' +
                                '<td>'+data.results[i].perimeter+'</td>' +
                            '</tr>';
            $('#backerList').append(trItem);
        }
    });
}