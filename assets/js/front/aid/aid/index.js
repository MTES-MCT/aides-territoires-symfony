import Routing from 'fos-router';
import '../../log/log-register-from-next-page-warning.js';
import '../../log/log-promotion-blog-post-click.js';
import '../../log/log-aid-search.js';
import 'jquery-highlight/jquery.highlight.js';

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
       '>': '&gt;',
       '"': '&quot;',
       "'": '&#039;'
   };
   return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

$(function(){
    callVapp();

    $(document).on({
        click: function (e) {
            var thisElt = $(this);
            var thisOriginalHtml = thisElt.html();
            var thisOriginalText = thisElt.text();

            thisElt.prop('disabled', true);
            thisElt.html('<i class="fas fa-spinner fa-spin fr-mr-1w""></i> ' + escapeHtml(thisOriginalText));

            // Réactive le bouton après 2 secondes si le formulaire échoue
            setTimeout(function () {
                thisElt.prop('disabled', false);
                thisElt.html(thisOriginalHtml);
            }, 2000); // 2 secondes
        }
    }, 'a#btn-download-results');

    if (typeof highlightedWords !== 'undefined') {
        $('.highlightable').highlight(highlightedWords);
    }

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

function callVapp()
{
    let csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

    $.ajax({
        url: Routing.generate('app_aid_ajax_call_vapp'),
        type: 'POST',
        data: {
            _token: csrfToken
        },
        success: function(data) {
            if (data.status === 'success') {
                if (typeof data.aidsChunksToScore !== 'undefined') {
                    Object.entries(data.aidsChunksToScore).forEach(([id, aid]) => {
                        renderAidCard(id, aid.score_vapp);
                    });

                    let nbTreated = parseInt($('#vapp-nb-treated').text()) + Object.keys(data.aidsChunksToScore).length;
                    $('#vapp-nb-treated').text(nbTreated);
                    callVapp();
                }
            } else if (data.status === 'done') {
                $('.fa-spinner').remove();
            }
        },
        error: function() {
            $('#new-feature-alert').after('<div class="alert alert-danger" role="alert">Une erreur est survenue lors de l\'analyse.</div>');
        }
    })
}

function renderAidCard(aidId, scoreVapp)
{
    let csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';

    $.ajax({
        url: Routing.generate('app_aid_ajax_render_aid_card'),
        type: 'POST',
        data: {
            aidId: aidId,
            scoreVapp: scoreVapp,
            _token: csrfToken
        },
        success: function(data) {
            const $wrapper = $('#aids-as-card');
            // Enveloppe le HTML de la carte dans un div avec les classes col
            const $newCard = $('<div class="fr-col-xs-12 fr-col-md-4 fr-p-3w"></div>').html(data.cardHtml);
            const newScore = parseFloat($newCard.find('.fr-card').data('score-vapp'));
            
            
            // Trouve la position d'insertion
            let inserted = false;
            $wrapper.children('.fr-col-xs-12').each(function() {
                const currentScore = parseFloat($(this).find('.fr-card').data('score-vapp'));
                if (newScore > currentScore) {
                    $(this).before($newCard);
                    inserted = true;
                    return false; // Sort de la boucle each
                }
            });
            
            // Si aucune insertion n'a été faite (score le plus bas), ajoute à la fin
            if (!inserted) {
                $wrapper.append($newCard);
            }

            // effet de highlight
            $newCard.find('.fr-card').addClass('card-highlight');
        }
    })
}