require('../../form/trumbowyg.js');

import Routing from 'fos-router';

$(function() {
    /**
     * Supprimer collection item
     */

    $(document).on({
        click: function (e) {
            e.preventDefault();
            $(this).parents('.collection-item-wrapper-generic:first').remove();
        }
    }, '.btn-delete-collection-generic');

    /**
     * Rajouter collection item
     */
    jQuery('.add-another-collection-widget').click(function (e) {
        e.preventDefault();
        var list = jQuery(jQuery(this).attr('data-list-selector'));
        // Try to find the counter of the list or use the length of the list
        var counter = list.data('widget-counter') || list.children().length;

        // grab the prototype template
        var newWidget = list.attr('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);

        // create a new list element and add it to the list
        var newElem = jQuery(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);

        launchTrumbowyg(newElem.find('textarea'));
    });

    // quand on arrive sur la page prete, on lock
    lock();
});

// quand on quitte la page
$(window).on('beforeunload', function() {
    unlock();
});

function lock()
{
    if (typeof idSearchPage !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_user_portal_ajax_lock'),
            type: 'POST',
            data: {
                'id': idSearchPage
            }
        });
    }
}

function unlock()
{
    alert('unlock '+idSearchPage);
    if (typeof idSearchPage !== 'undefined') {
        $.ajax({
            url: Routing.generate('app_user_portal_ajax_unlock'),
            type: 'POST',
            data: {
                'id': idSearchPage
            }
        });
    }
}