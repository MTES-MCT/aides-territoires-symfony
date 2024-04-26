require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');

import Routing from 'fos-router';

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
    
    // quand on arrive sur la page prete, on lock
    lock();
    // toutes les 2 minutes, on update le lock en le relancant
    setInterval(function() {
        lock();
    }, 2 * 60 * 1000);
}); 


// quand on quitte la page
$(window).on('beforeunload', function() {
    unlock();
});
$(window).on('buload', function() {
    unlock();
});
$(window).on('pagehide', function() {
    unlock();
});


function lock()
{
    if (typeof idBacker !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_organization_backer_ajax_lock'),
            type: 'POST',
            data: {
                'id': idBacker
            }
        });
    }
}

function unlock()
{
    if (typeof idBacker !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_organization_backer_ajax_unlock'),
            type: 'POST',
            data: {
                'id': idBacker
            }
        });
    }
}