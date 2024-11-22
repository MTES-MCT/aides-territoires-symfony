// assets/controllers/custom-autocomplete_controller.js
import { Controller } from '@hotwired/stimulus';
import Routing from 'fos-router';

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

        // Récupérer dynamiquement la valeur de #searchPerimeter à chaque appel
        options.load = (query, callback) => {
            const id_perimeter = document.querySelector('#perimeter_id').value || ''; // Prendre une valeur vide si le champ est vide
            const urlFromRoute = Routing.generate('app_backer_autocomplete');
            const url = new URL(urlFromRoute, window.location.origin);

            // Ajoutez la query et la valeur du champ supplémentaire comme paramètres
            url.searchParams.set('q', query);
            url.searchParams.set('id_perimeter', id_perimeter);

            fetch(url)
                .then(response => response.json())
                .then(results => {
                    const formattedResults = results.map(item => ({
                        value: item.value,
                        text: item.text
                    }));

                    // Vider les options existantes avant de charger les nouvelles
                    this.element.tomselect.clearOptions();
                    callback(formattedResults);
                })
                .catch(() => {
                    callback(); // Aucun résultat en cas d'échec
                });
        };
    }
    
    _onConnect(event) {
        // TomSelect has just been intialized and you can access details from the event
    }
}