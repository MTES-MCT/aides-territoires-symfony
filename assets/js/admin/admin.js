import '../../bootstrap.js';
// wysiwyg
require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/upload/trumbowyg.upload.min.js');
require('trumbowyg/dist/plugins/cleanpaste/trumbowyg.cleanpaste.min.js');
require('trumbowyg/dist/ui/trumbowyg.min.css');
require('clipboard/dist/clipboard.min.js');
require('../jQueryAccordion/jquery.accordion.js')

// import le fichier router dans ce fichier
import ClipboardJS from 'clipboard';
import Routing from 'fos-router';

// plugin chartJS
// import { Chart, registerables } from 'chart.js';
import annotationPlugin from 'chartjs-plugin-annotation';
import { trumbowyg } from 'jquery';
document.addEventListener('chartjs:init', function (event) {
    const Chart = event.detail.Chart;
    Chart.register(annotationPlugin);
});

$(function(){

    /***************************
     * Pour ne pas trigger plusieurs fois un event
     *
     * exemple :
    waitForFinalEvent(function () {

    }, 500, 'id-unique');
    */
    global.waitForFinalEvent = (function () {
        var timers = {};
        return function (callback, ms, uniqueId) {
            if (!uniqueId) {
                uniqueId = "Don't call this twice without a uniqueId";
            }
            if (timers[uniqueId]) {
                clearTimeout (timers[uniqueId]);
            }
            timers[uniqueId] = setTimeout(callback, ms);
        };
    })();


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

    $('.accordion').accordion({
        "transitionSpeed": 400
    });

    new ClipboardJS('.btn-copy-clipboard');

    /**
     * Champs avec un maxlength
     */
    $('*[maxlength]').each(function(){
        var thisElt = $(this);
        var counter = thisElt.parents('.input-group').find('.input-group-text');
        var textToCheck = thisElt.val().replace(/(\r\n|\n|\r)/gm,"");
        $('.current-count', counter).text(textToCheck.length);

        $(document).on({
            keyup: function(){
                var textToCheck = thisElt.val().replace(/(\r\n|\n|\r)/gm,"");
                $('.current-count', counter).text(textToCheck.length);
            }
        },this);
    });

    /**
     * Pour empêcher le click sur des selects readonly
     */
    $('select[readonly="readonly"]').each(function() {
        $('option', this).each(function() {
            if (typeof $(this).attr('selected') === 'undefined') {
                $(this).prop('disabled', true);
            }
        });
    });

    $(document).on({
        click: function(e) {
            launchTrumbowyg();
        }
    }, '.field-collection-add-button');

    launchTrumbowyg();
    

    global.datatables_fr_strings = {
        search: "Filtrer :",
        zeroRecords: "Aucun résultat trouvé",
        lengthMenu: "Afficher _MENU_ éléments",
        paginate: {
            first: "Premier",
            previous: "Pr&eacute;c&eacute;dent",
            next: "Suivant",
            last: "Dernier"
        },
    
    }
})

function launchTrumbowyg()
{
// wysiwyg
$('textarea:not(.trumbowyg-textarea):not(.not-trumbowyg)').trumbowyg({
    svgPath: '/build/trumbowyg/icons.svg',
    lang: 'fr',
    btnsDef: {
        // Create a new dropdown
        image: {
            dropdown: ['insertImage', 'upload'],
            ico: 'insertImage'
        }
    },
    // Redefine the button pane
    btns: [
        ['viewHTML'],
        ['formatting'],
        ['strong', 'em'],
        ['link'],
        ['unorderedList', 'orderedList'],
        ['removeformat'],            
        ['fullscreen'],
        ['image'],
        ['justifyLeft', 'justifyCenter', 'justifyRight'],
    ],
    plugins: {
        // Image upload
        upload: {
            serverPath: Routing.generate('app_admin_upload_image'),
            fileFieldName: 'image',
            // headers: {
            //     'Authorization': 'Client-ID xxxxxxxxxxxx'
            // },
            urlPropertyName: 'data.link'
        },
        // nettoyage texte word
        cleanpaste: true
    }
});
}

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