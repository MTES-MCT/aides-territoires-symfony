require('../../form/trumbowyg.js');

import Routing from 'fos-router';

$(function() {
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
$(window).on('unload', function() {
    unlock();
});
$(window).on('pagehide', function() {
    unlock();
});

function lock()
{
    if (typeof idProject !== 'undefined') {
        var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

        $.ajax({
            url: Routing.generate('app_user_project_ajax_lock'),
            type: 'POST',
            data: {
                'id': idProject,
                '_token': csrfToken
            }
        });
    }
}

function unlock()
{
    if (typeof idProject !== 'undefined') {
        var csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

        $.ajax({
            url: Routing.generate('app_user_project_ajax_unlock'),
            type: 'POST',
            data: {
                'id': idProject,
                '_token': csrfToken
            }
        });
    }
}
