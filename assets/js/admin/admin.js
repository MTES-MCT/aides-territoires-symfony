import '../../bootstrap.js';
// wysiwyg
require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/upload/trumbowyg.upload.min.js');
require('trumbowyg/dist/plugins/cleanpaste/trumbowyg.cleanpaste.min.js');
require('trumbowyg/dist/ui/trumbowyg.min.css');
require('../front/user/aid/_import_manual_core.js');
require('../jQueryAccordion/jquery.accordion.js')

// import le fichier router dans ce fichier
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

    $('.accordion').accordion({
        "transitionSpeed": 400
    });

    // pour ouvrir un tab
    $(document).on({
        click: function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            if (typeof $(target) != 'undefined') {
                var parent = $(target).parents('.tab-pane');
                $('.tab-pane').removeClass('active').removeClass('show');
                $(parent).addClass('active').addClass('show');

                var idTab = $(parent).attr('id');
                $('.nav-link').removeClass('active');
                $('.nav-link[href="#'+idTab+'"]').addClass('active');
            }
        }
    }, '.ea-tab-opener');

    // fonction copie dans clipboard
    $(document).on('click', '.btn-copy-clipboard', function(e) {
        e.preventDefault();
        // Recupère le bouton cliqué
        var thisElt = $(this);
        // Récupère le sélecteur de l'élément cible depuis l'attribut data-clipboard-target
        var targetSelector = $(this).data('clipboard-target');
        var targetElement = $(targetSelector)[0]; // Obtient l'élément DOM natif

        // Vérifie si la sélection est possible
        if (document.body.createTextRange) { // Pour IE
            const range = document.body.createTextRange();
            range.moveToElementText(targetElement);
            range.select();
        } else if (window.getSelection) {
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(targetElement);
            selection.removeAllRanges(); // Supprime toutes les sélections existantes
            selection.addRange(range); // Ajoute la nouvelle sélection
        }

        // reset la classe des autres boutons
        $('.fa-clipboard-check').removeClass('fa-clipboard-check').addClass('fa-clipboard');
        // change la classe de l'icone bouton cliqué
        thisElt.find('i').removeClass('fa-clipboard').addClass('fa-clipboard-check');
        
        // Sélectionne l'élément cible et récupère son contenu HTML
        var htmlContent = $(targetSelector).html().trim();
        // Copie le contenu dans le presse-papiers
        navigator.clipboard.writeText(htmlContent).then(function() {
        }).catch(function(error) {
        });
    });

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
    removeformatPasted: true,
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

