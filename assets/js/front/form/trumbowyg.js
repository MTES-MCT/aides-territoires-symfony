require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/ui/trumbowyg.min.css');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/cleanpaste/trumbowyg.cleanpaste.min.js');
require('trumbowyg/dist/plugins/allowtagsfrompaste/trumbowyg.allowtagsfrompaste.min.js');
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
                serverPath: Routing.generate('app_upload_image', {'_token': typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : ''}),
                fileFieldName: 'image',
                urlPropertyName: 'data.link'
            },
            // nettoyage texte word
            cleanpaste: true,
            allowTagsFromPaste: {
                allowedTags: [
                    'p', 'br', 'a', 'strong', 'em', 'u', 's', 'sub', 'sup', // Texte de base et styles
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', // Titres
                    'ul', 'ol', 'li', // Listes
                    'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', // Tableaux
                    'blockquote', 'pre', 'code', // Citations, code
                    'img', // Images
                    'figure', 'figcaption' // Figures
                ],
                allowedAttributes: {
                    'a': ['href', 'title', 'target'], // Liens
                    'img': ['src', 'alt', 'title', 'width', 'height'], // Images
                    'table': ['border', 'cellpadding', 'cellspacing'], // Tableaux
                }
            }
        }
    });
}