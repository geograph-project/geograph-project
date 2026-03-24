import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `
        <div class="view faq-view">

            <div class="controls">
                <button class=btn id="btn-open" type=button>Open All</button>
                <button class=btn id="btn-collapse" type=button>Collapse All</button>
            </div>

            <h2>App Questions</h2>
            <p class="tap-prompt"><i>tap to expand...</i></p>
            <div id="app-questions" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>

            <br><br>
            <h2>Contributor Questions</h2>
            <p class="tap-prompt"><i>tap to expand...</i></p>
            <div id="contributor-questions" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>
            <p><i>There are more questions and answers on a <a href="/faq3.php?l=0" target="_blank">full Website FAQ page</a> (opens in new window)
        </div>
    `;
}

function toggleAll(shouldHide) {
    document.querySelectorAll('.faq-content').forEach(content => {
        content.classList.toggle('hidden', shouldHide);
    });
    //might as well hide the 'tap to expand' prompt if open all!
    document.querySelectorAll('.tap-prompt').forEach(prompt => {
        prompt.classList.toggle('hidden', !shouldHide);
    });
}

export async function onMount() {
    document.getElementById('btn-open').onclick = () => toggleAll(false);
    document.getElementById('btn-collapse').onclick = () => toggleAll(true);

    const contain1 = document.getElementById('app-questions');

const staticlist = [
/* might be useful if expand to allow acecss before login
  {
    "title": "What is the [Geograph App]?",
    "content": "The Geograph app is currently a test version. It allows users to take, upload, and submit photos, view maps, search for images, and review recent submissions."
  },
  {
    "title": "How do I [login] to the app?",
    "content": "You must be a registered member of the Geograph website to use the app, as internal registration is not yet available. When logging in, selecting the \"remember me\" option is required."
  },
*/

  {
    "title": "Can I use the app without installing it?",
    "content": "Yes! The Geograph app is fully functional directly within your mobile browser. You don't need to download anything from an app store to start capturing and uploading photos; simply open the link and go."
  },
  {
    "title": "How do I install the app on my device?",
    "content": "As a Progressive Web App (PWA), there is no need to visit an App Store. You can install it directly through your mobile browser while connected to the internet:\n\n* **Android (Chrome):** Open the app link, tap the three-dot menu (⋮), and select 'Install app' or 'Add to home screen.'\n* **iOS/iPhone (Safari):** Open the app link, tap the 'Share' icon (the square with an up arrow), and scroll down to select 'Add to Home Screen.'\n\nOnce added, the Geograph icon will appear on your home screen and function like a regular native app."
  },

  {
    "title": "Are there specific [permissions] or settings required?",
    "content": "The app requires access to your device's location, camera, and filestore to be fully functional. You must grant these permissions when prompted or via your device settings. Additionally, ensure \"Desktop site\" is disabled in your browser settings so the app scales correctly to your screen."
  },

  {
    "title": "What should I know about [capturing and uploading] images?",
    "content": "For the best experience, we recommend using your device's native camera app; it is generally faster and more reliable at embedding GPS data. If you find your browser strips location data during upload, you can use our in-app 'Take Photo' tool - this acts as a workaround by encoding the location directly into the filename. Once captured, you must manually select and upload your best images from your gallery. After uploading, you'll complete the 'Submission' by adding a title and tags; the app will attempt to pull the date and location automatically from the image EXIF data. You can upload multiple images at once and even finish the final submission later on a different device, such as a tablet (on the app) or desktop/laptop via main site."
  },

  {
    "title": "Can I use the [app offline] or in remote areas?",
    "content": "A data connection is required for full functionality, including uploading images and searching the map. However, the map is designed to handle patchy coverage: once a map area has loaded, it is temporarily cached and should remain visible even if you momentarily lose signal. For best results in remote areas, we recommend viewing your target map area while you still have a strong connection."
  },
  {
    "title": "Where can I find [help] or provide [feedback]?",
    "content": "The app menu, located in the footer at the bottom right, contains links for App Help, a contact form for the helpdesk, and a Google feedback form to report your experience. If reporting a specific technical issue, please include the app version number found in the footer."
  },

  {
    "title": "Does the [Geograph App] record my location?",
    "content": "No. As a web app, it can only access your location while it is actively being used. We only use your position for explicit, visible features, such as centering the map or taking a photo via the app. Location data is not used outside of these specific features."
  },
  {
    "title": "Can I record a [location tracklog] for later use?",
    "content": "If you wish to specifically record a location track-log (e.g., for correlation with photos later, or a GeoTrip), you will need to install a native app on your device with that capability. The Geograph app only supports saving individual annotated locations as notes or taking photos with location data embedded in the filename."
  },

  {
    "title": "Why don't I see my [previously used tags] in the list?",
    "content": "This app stores your recently used Context, Subject, and Tags locally on your current device. This means the list only populates with tags created during sessions on this specific browser and will not sync tags used in submissions from other devices."
  }
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

