import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String
    }

    async toggle(event) {
        // Empêcher la propagation vers la carte parent
        event.preventDefault();
        event.stopPropagation();
        
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            // Récupérer la réponse et mettre à jour le bouton
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('template').innerHTML;
            this.element.closest('[id^="favorite-button-"]').outerHTML = content;

        } catch (error) {
            console.error('Error:', error);
        }
    }
} 