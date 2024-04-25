require('../../form/trumbowyg.js');

import Routing from 'fos-router';

$(function() {
    // Gestionnaire d'événement 'submit'
    $(document).on('submit', 'form[name="form"]', function(e) {
        e.preventDefault();
        $("#btn_modal_waiting").attr("data-fr-opened", "true");
        setTimeout(function() {
            $(e.target).off('submit');
            e.target.submit();
        }, 250);
    }); 
    
    // quand on arrive sur la page prete, on lock
    lock();
}); 

// quand on quitte la page
$(window).on('unload', function() {
    unlock();
});
$(window).on('pagehide', function() {
    unlock();
});

function lock()
{
    if (typeof idProject !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_user_project_ajax_lock'),
            type: 'POST',
            data: {
                'id': idProject
            }
        });
    }
}

function unlock()
{
    if (typeof idProject !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_user_project_ajax_unlock'),
            type: 'POST',
            data: {
                'id': idProject
            }
        });
    }
}