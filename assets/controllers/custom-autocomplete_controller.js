// assets/controllers/custom-autocomplete_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    initialize() {
        this._onPreConnect = this._onPreConnect.bind(this);
        this._onConnect = this._onConnect.bind(this);
    }

    connect() {
        this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect);
        this.element.addEventListener('autocomplete:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side-effects
        this.element.removeEventListener('autocomplete:connect', this._onConnect);
        this.element.removeEventListener('autocomplete:pre-connect', this._onPreConnect);
    }

    _onPreConnect(event) {
        // TomSelect has not been initialized - options can be changed
        let options = event.detail.options;
        options.render = {
            option_create: function( data, escape ){
                return '<div class="create">Ajouter <strong>' + escape(data.input) + '</strong>&hellip;</div>';
            },
            no_results: function( data, escape ){
                return '<div class="no-results">Aucun résultat trouvé</div>';
            },
            no_more_results: function(data, escape) {
                return `<div class="no-more-results">Aucun résultat trouvé</div>`;
            },
        };
    }

    _onConnect(event) {
        // TomSelect has just been intialized and you can access details from the event
    }
}