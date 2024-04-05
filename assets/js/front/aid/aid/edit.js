require('trumbowyg/dist/trumbowyg.min.js');
require('trumbowyg/dist/langs/fr.min.js');
require('trumbowyg/dist/plugins/cleanpaste/trumbowyg.cleanpaste.min.js');
require('./enable_page_exit_confirmation.js');
require('./stepper.js');
require ('../../../form/input-maxlength.js');


$(function(){
    $('.widget-autocomplete-multiple-wrapper').checkbox_multiple_search();
    $('.entity-checkbox-absolute-type-wrapper').entity_checkbox_absolute_type();

    // checkbox toggler
    $(document).on({
        change: function(e) {
            toggleElements($(this).attr('name'), $(this).val(), $(this).prop('checked'));
        }
    }, 'input[name="aid_edit[aidTypes][]"]');
    $('input[name="aid_edit[aidTypes][]"]').each(function() {
        toggleElements($(this).attr('name'), $(this).val(), $(this).prop('checked'));
    });

    // select toggler
    $(document).on({
        change: function(e) {
            toggleElementsFromSelect($(this).attr('name'), $(this).val());
        }
    }, 'select[name="aid_edit[aidRecurrence]"]');
    $('select[name="aid_edit[aidRecurrence]"]').each(function() {
        toggleElementsFromSelect($(this).attr('name'), $(this).val());
    });

    // change status
    $(document).on({
        click: function(e) {
            $('#aid_edit_status').val($(this).attr('data-status'));
            $('.form-aid').trigger('submit');
        }
    }, '.btn-change-status');

    // submit with change status
    $(document).on({
        click: function(e) {
            $('#aid_edit_status').val($(this).attr('data-status'));
        }
    }, '.submit-change-status');



    // wysiwyg
    $('.trumbowyg').trumbowyg({
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
        ],
        plugins: {
            // nettoyage texte word
            cleanpaste: true
        }
    });
});

function toggleElements(parent, value, checked)
{
    if ($('*[data-parent="'+parent+'"][data-value="'+value+'"]').length) {
        if (checked) {
            $('*[data-parent="'+parent+'"][data-value="'+value+'"]').addClass('fr-collapse--expanded');
        } else {
            $('*[data-parent="'+parent+'"][data-value="'+value+'"]').removeClass('fr-collapse--expanded');
        }
    }
}

function toggleElementsFromSelect(parent, value)
{
    if ($('*[data-parent="'+parent+'"]').length) {
        $('*[data-parent="'+parent+'"]').each(function() {
            if (typeof $(this).attr('data-value') !== 'undefined') {
                var values = $(this).attr('data-value').split('|');
                if (values.includes(value)) {
                    $(this).addClass('fr-collapse--expanded');
                } else {
                    $(this).removeClass('fr-collapse--expanded');
                }
            }
        });
    }
}