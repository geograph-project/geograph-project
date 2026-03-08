import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `
        <div class="view" style="max-width: 600px; margin: 0 auto; padding: 10px;">
            <h2>App Help</h2>
            <p>Find documentation and guides.</p>

            <h2 style="margin-top: 20px;">Contributor Questions</h2>
            <div id="faq-container" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>
        </div>
    `;
}

export async function onMount() {
    const container = document.getElementById('faq-container');

    try {
        const response = await fetch('/faq.json.php?contributors=true');
        const faqs = await response.json();

        faqs.unshift({
		title: 'Why is there no [Take Photo] button in the [app]?',
		content: 'Current web technology make this difficult, let us know if really interested in this function. Take your photos as normal, then Upload by selecting from Gallery'
	});

        container.innerHTML = faqs.map((item, index) => `
            <div class="faq-item" style="border-bottom: 1px solid white; padding: 15px 0;">
                <button 
                    onclick="this.nextElementSibling.classList.toggle('hidden')"
                    style="background: none; border: none; width: 100%; text-align: left; font-size: 1rem; cursor: pointer; padding: 0; color: #333;"
                >
                    ${formatTitle(escapeHTML(item.title))}
                </button>
                <div class="faq-content hidden" style="margin-top: 10px; color: #555; line-height: 1.5; font-size: 0.95rem; white-space: pre-line;">
                    ${escapeHTML(item.content)}
                </div>
            </div>
        `).join('');

    } catch (error) {
        container.innerHTML = `<p style="color: red;">Failed to load FAQs. Please try again later.</p>`;
    }
}
