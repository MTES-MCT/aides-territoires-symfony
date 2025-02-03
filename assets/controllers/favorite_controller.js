import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        authenticated: Boolean,
    }

    async toggle(event) {
        // Empêcher la propagation vers la carte parent
        event.preventDefault();
        event.stopPropagation();
        
        if (!this.authenticatedValue) {
            const modal = document.getElementById('modal-auth');
            if (modal) {
                modal.showModal();
            }
            return;
        }

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
            if (html && html.includes('<template')) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const template = doc.querySelector('template');
                if (template) {
                    this.element.closest('[id^="favorite-button-"]').outerHTML = template.innerHTML;
                }
            }

        } catch (error) {
            console.error('Error:', error);
        }
    }
} 