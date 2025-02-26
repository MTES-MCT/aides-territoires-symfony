import Routing from 'fos-router';
import '../../log/log-aid-details.js';
import 'jquery-highlight/jquery.highlight.js';

$(function(){
    if (typeof highlightedWords !== 'undefined') {
        $('.highlightable').highlight(highlightedWords);
    }

    $(document).on({
        click: function(e) {
            e.stopPropagation();
            e.preventDefault();

            let thisElt = $(this);
            let vote = parseInt($(this).data('vote'));
            let thisAidId = typeof aidId !== 'undefined' ? aidId : 0;
            let csrfToken = typeof csrfTokenInternal !== 'undefined' ? csrfTokenInternal : '';
            
            $.ajax({
                url: Routing.generate('app_abtest_ajax_vote'),
                type: 'POST',
                data: {
                    vote: vote,
                    aidId: thisAidId,
                    _token: csrfToken
                },
                success: function(data) {
                    if (data.success) {
                        $('.btn-vote').removeClass('active');
                        thisElt.addClass('active');
                    }
                }
            });
        }
    }, '.btn-vote');

    $("#clipboard-btn").on("click", function () {
        navigator.clipboard.writeText($("#currentUrl").val());
    });


    let facebook_btn = document.getElementById("fr-btn--facebook");

    facebook_btn.addEventListener(
        "click", function (event) {
            window.open(
                this.href,
                'Partager sur Facebook - ouvre une nouvelle fenêtre',
                'toolbar=no,location=yes,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=450'
            );
            event.preventDefault();
        }
    );

    let twitter_btn = document.getElementById("fr-btn--twitter");

    twitter_btn.addEventListener(
        "click", function (event) {
            window.open(
                this.href,
                'Partager sur Twitter - ouvre une nouvelle fenêtre',
                'toolbar=no,location=yes,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=420'
            );
            event.preventDefault();
        }
    );

    let linkedin_btn = document.getElementById("fr-btn--linkedin");

    linkedin_btn.addEventListener(
        "click", function (event) {
            window.open(
                this.href,
                'Partager sur LinkedIn - ouvre une nouvelle fenêtre',
                'toolbar=no,location=yes,status=no,menubar=no,scrollbars=yes,resizable=yes,width=550,height=550'
            );
            event.preventDefault();
        }
    );

    // Make sure all links contained in aid description open in a new tab.
    $('article#aid div.aid-details a').attr('target', '_blank');
});