require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');

$(function() {
    // wysiwyg
    $('.trumbowyg').trumbowyg({
        svgPath: '/build/trumbowyg/icons.svg',
        lang: 'fr',
        btns: [
            ['formatting'],
            ['strong', 'em'],
            ['link'],
            ['unorderedList', 'orderedList'],
            ['removeformat'],            
            ['fullscreen']
        ]
    });
    
}); 
