import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `
        <div class="view" style="max-width: 600px; margin: 0 auto; padding: 10px;">

            <h2>App Questions</h2>
            <p style="text-align:center"><i>tap to expand...</i></p>
            <div id="app-questions" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>

            <br><br>
            <h2>Contributor Questions</h2>
            <p style="text-align:center"><i>tap to expand...</i></p>
            <div id="contributor-questions" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>
            <p><i>There are more questions and answers on a <a href="/faq3.php?l=0" target="_blank">full Website FAQ page</a> (opens in new window)
        </div>
    `;
}

export async function onMount() {
    const contain1 = document.getElementById('app-questions');

const staticlist = [
/* might be useful if expand to allow acecss before login
  {
    "title": "What is the [Geograph App]?",
    "content": "The Geograph app is a photographic archive of Britain and Ireland currently in its test version. It allows users to take, upload, and submit photos, view maps, search for images, and review recent submissions."
  },
  {
    "title": "How do I [login] to the app?",
    "content": "You must be a registered member of the Geograph website to use the app, as internal registration is not yet available. When logging in, selecting the \"remember me\" option is required."
  },
*/
  {
    "title": "How do I [install] the app on my device?",
    "content": "As a Progressive Web App (PWA), it is installed through your mobile browser while connected to the internet. \n\n* Android users: Open the app link in Chrome, tap the three-dot menu, select \"Add to home screen,\" and choose \"Install\". \n* Apple iOS users: Open the link in Safari, tap the \"Share\" button, and select \"Add to home screen\"."
  },
  {
    "title": "Are there specific [permissions] or settings required?",
    "content": "The app requires access to your device's location, camera, and filestore to be fully functional. You must grant these permissions when prompted or via your device settings. Additionally, ensure \"Desktop site\" is disabled in your browser settings so the app scales correctly to your screen."
  },
  {
    "title": "What should I know about [uploading and submitting] images?",
    "content": "You can upload images from your device or take them directly within the app; app-captured photos are saved to your \"Downloads\" folder. Submissions require a title, date, camera location, subject location, and at least one geographical context. \n\nNote that all submissions are made under irrevocable Creative Commons license terms, allowing others to use your images for various purposes."
  },
  {
    "title": "Can I use the app [offline]?",
    "content": "No, a data signal is required for full functionality. Without an internet connection, you will be unable to upload images or browse maps."
  },
  {
    "title": "Where can I find [help] or provide [feedback]?",
    "content": "The app menu, located in the footer at the bottom right, contains links for App Help, a contact form for the helpdesk, and a Google feedback form to report your experience. If reporting a specific technical issue, please include the app version number found in the footer."
  }

/*
[
  {
    "title": "Does the [Geograph App] record my location?",
    "content": "No. As a web app, it can only access your location while it is actively being used. We only use your position for explicit, visible features, such as centering the map or taking a photo via the app. Location data is not used outside of these specific features."
  },
  {
    "title": "Can I record a [location tracklog] for later use?",
    "content": "If you wish to specifically record a location tracklog (e.g., for correlation with photos later), you will need to install a native app on your device with that capability. The Geograph app only supports saving individual annotated locations as notes or taking photos with location data embedded in the filename."
  }
]
*/

];

    renderHTML(contain1, staticlist);

    const contain2 = document.getElementById('contributor-questions');

    try {
        const response = await fetch('/faq.json.php?contributors=true');
        const faqs = await response.json();

        renderHTML(contain2, faqs);

    } catch (error) {
        containe2.innerHTML = `<p class="error-text">Failed to load FAQs. Please try again later.</p>`;
    }
}

function renderHTML(container, faqs) {
        container.innerHTML = faqs.map((item, index) => `
            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
                    ${formatTitle(escapeHTML(item.title))}
                </button>
                <div class="faq-content hidden">
                    ${escapeHTML(item.content)}
                </div>
            </div>
        `).join('');
}

