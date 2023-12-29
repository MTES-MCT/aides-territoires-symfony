import '../../log/log-register-from-next-page-warning.js';
import '../../log/log-promotion-blog-post-click.js';

$(function(){
    $(document).on({
        change: function(e) {
            $(this).parents('form').submit();
        }
    }, 'select[name="orderBy"]');

    $('.entity-checkbox-absolute-type-wrapper').entity_checkbox_absolute_type();

    $(document).on({
        click: function(e) {
            e.preventDefault();
            if ($('#search-form-extra-fields').is(':visible')) {
                hideFromExtended();
            } else {
                showFormExtended();
            }
        }
    }, 'button#search-form-more-options');

    let display_mode = sessionStorage.getItem('display_mode');

    let displayAsList = function () {
        $('#btn-results-card').addClass('fr-btn--secondary')
        $('#btn-results-list').removeClass('fr-btn--secondary')
        $('#aids-as-list').removeClass('at-display__none')
        $('#aids-as-card').addClass('at-display__none')
        $('#btn-results-list').attr("aria-pressed", true)
        $('#btn-results-card').removeAttr("aria-pressed")
        $('#display-type').text("Affichage en liste")
    };

    let displayAsCard = function () {
        $('#btn-results-card').removeClass('fr-btn--secondary')
        $('#btn-results-list').addClass('fr-btn--secondary')
        $('#aids-as-list').addClass('at-display__none')
        $('#aids-as-card').removeClass('at-display__none')
        $('#btn-results-list').removeAttr("aria-pressed")
        $('#btn-results-card').attr("aria-pressed", true)
        $('#display-type').text("Affichage en cartes")
    };

    if (display_mode == 'list') {
        displayAsList();
    } else if (display_mode == 'card') {
        displayAsCard();
    }

    $('#btn-results-list').on('click', function () {
        displayAsList();
        sessionStorage.setItem('display_mode', 'list');
    })

    $('#btn-results-card').on('click', function () {
        displayAsCard();
        sessionStorage.setItem('display_mode', 'card');
    })

});

function hideFromExtended()
{
    $('#search-form-extra-fields').slideUp('fast');

    var newBtnText = '<span class="fr-icon-add-line" aria-hidden="true"></span> Afficher les critères avancés';
    $('button#search-form-more-options').html(newBtnText);                      
                            
}

function showFormExtended()
{
    $('#search-form-extra-fields').slideDown('fast');

    var newBtnText = '<span class="fr-icon-subtract-line" aria-hidden="true"></span> Masquer les critères avancés';
    $('button#search-form-more-options').html(newBtnText);

}