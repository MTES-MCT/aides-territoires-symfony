import './bootstrap.js';
import './js/front/log/log.js';

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

require('@fortawesome/fontawesome-free/css/all.min.css');
require('@gouvfr/dsfr/dist/dsfr.module.min.js');
// require('@gouvfr/dsfr/dist/dsfr.nomodule.min.js');
require('@gouvfr/dsfr/dist/scheme/scheme.module.min.js');
// any CSS you import will output into a single css file (app.scss in this case)
import './styles/app.scss';

// Need jQuery? Install it with "yarn add jquery", then uncomment to require it.
const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;


$(function(){
    global.convertToSlug = function(str) {
        str = str.replace(/^\s+|\s+$/g, ''); // trim
        str = str.toLowerCase();

        // remove accents, swap ñ for n, etc
        var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
        var to   = "aaaaaeeeeeiiiiooooouuuunc------";
        for (var i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
                 .replace(/\s+/g, '-') // collapse whitespace and replace by -
                 .replace(/-+/g, '-'); // collapse dashes

        return str;
    }
})

global.datatables_fr_strings = {
    search: "Filtrer :",
    zeroRecords: "Aucun résultat trouvé",
    lengthMenu: "Afficher _MENU_ éléments",
    paginate: {
        first: "Premier",
        previous: "Pr&eacute;c&eacute;dent",
        next: "Suivant",
        last: "Dernier"
    },

}