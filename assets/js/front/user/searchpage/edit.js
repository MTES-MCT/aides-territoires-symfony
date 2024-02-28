require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');

$(function() {
    // wysiwyg
    launchTrumbowyg('.trumbowyg');
    
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

})

function launchTrumbowyg(elt)
{
    $(elt).trumbowyg({
        svgPath: '/build/trumbowyg/icons.svg',
        lang: 'fr',
        // Redefine the button pane
        btns: [
            ['formatting'],
            ['strong', 'em'],
            ['link'],
            ['unorderedList', 'orderedList'],
            ['removeformat'],            
            ['fullscreen']
        ]
    });
}