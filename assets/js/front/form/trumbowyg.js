require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/ui/trumbowyg.min.css');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/cleanpaste/trumbowyg.cleanpaste.min.js');
require('trumbowyg/dist/plugins/upload/trumbowyg.upload.min.js');


import Routing from 'fos-router';

$(function() {
    // wysiwyg
    launchTrumbowyg('.trumbowyg');
});


global.launchTrumbowyg = function(elt)
{
    $(elt).trumbowyg({
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
            ['image'],
            ['unorderedList', 'orderedList'],
            ['removeformat'],           
            ['justifyLeft', 'justifyCenter', 'justifyRight'], 
            ['fullscreen']
        ],
        plugins: {
            // Image upload
            upload: {
                serverPath: Routing.generate('app_upload_image'),
                fileFieldName: 'image',
                urlPropertyName: 'data.link'
            },
            // nettoyage texte word
            cleanpaste: true
        }
    });
}