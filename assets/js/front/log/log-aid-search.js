

$(function(){
    let SEARCH_STEP = 'Étape 5 – Résultats';
    trackSearchEvent(PERIMETER_NAME, CATEGORIES_NAME, NB_RESULTS, SEARCH_STEP);
});

function trackSearchEvent(perimeter, categories, nb_results, step) {
    try {
        let allCategories = categories.reduce(function (acc, value) {
            let accumulated;
            if (acc == '') {
                accumulated = value;
            } else {
                accumulated = acc + "|" + value;
            }
            return accumulated;
        }, '');

        let eventName;
        if (perimeter) {
            eventName = perimeter + ' > ' + allCategories;
        } else {
            eventName = allCategories;
        }

        if (typeof _paq !== 'undefined') {
            _paq.push(['trackEvent', 'Recherche', step, eventName, nb_results]);
        }
    } catch (e) {
        console.log(e);
    }
}