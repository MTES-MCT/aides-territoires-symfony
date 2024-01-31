import '../../bootstrap.js';
// wysiwyg
require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/upload/trumbowyg.upload.min.js');
require('trumbowyg/dist/ui/trumbowyg.min.css');
require('clipboard/dist/clipboard.min.js');
require('../jQueryAccordion/jquery.accordion.js')

// import le fichier router dans ce fichier
import ClipboardJS from 'clipboard';
import Routing from 'fos-router';

// plugin chartJS
// import { Chart, registerables } from 'chart.js';
import annotationPlugin from 'chartjs-plugin-annotation';
document.addEventListener('chartjs:init', function (event) {
    const Chart = event.detail.Chart;
    Chart.register(annotationPlugin);
});

$(function(){

    // register globally for all charts
// register globally for all charts


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

    // wysiwyg
    $('.trumbowyg').trumbowyg({
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
            // Add imagur parameters to upload plugin for demo purposes
            upload: {
                serverPath: Routing.generate('app_admin_upload_image'),
                fileFieldName: 'image',
                // headers: {
                //     'Authorization': 'Client-ID xxxxxxxxxxxx'
                // },
                urlPropertyName: 'data.link'
            }
        }
    });

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