import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `
        <div class="view" style="max-width: 600px; margin: 0 auto; padding: 10px;">


<style>
    section ul {
        margin-left:26px;
    }
    section dt {
        font-weight:bold;
    }
    section dd, article p {
        margin-left:18px;
        margin-bottom:8px;
    }
    hr {
        margin-top:20px;
    }
</style>

    <section>
        <h1>Geograph App (V0.99)</h1>
        <p>A free photographic archive of Britain and Ireland. This test version is currently in development; some features link to the main website.</p>
    </section>

    <section id="getting-started">
        <h2>Getting Started</h2>
        
        <article id="login">
            <h3>Login</h3>
            <p>Registration is not yet enabled within the app. Please register on the <a href="http://www.geograph.org.uk">Geograph website</a> first. The app will remember your session once logged in.</p>
        </article>

        <article id="installation">
            <h3>Installation</h3>
            <p>This Progressive Web App (PWA) can be used in a browser or installed to your home screen. An internet connection is required for map and upload functionality.</p>
            <ul>
                <li><strong>Android:</strong> Chrome &gt; Three-dot menu &gt; Add to home screen.</li>
                <li><strong>iOS:</strong> Safari &gt; Share button &gt; Add to home screen.</li>
            </ul>
        </article>

        <article id="location">
            <h3>Location Services</h3>
            <p>Enable device location when prompted to use maps or tag photos. If your device (e.g., Samsung) strips metadata for privacy, use the <strong>Save/Capture Location</strong> tool to save coordinates directly into the filename.</p>
        </article>
    </section>

    <section id="home-screen">
        <h2>Home Screen Functions</h2>
        
        <dl>
            <dt>Upload Image</dt>
            <dd>Select and preview images. Large JPEGs are resized automatically. HEIC files are supported but may not show a preview.</dd>

            <dt>Submit Image</dt>
            <dd>Position your camera and subject by dragging the map. Markers stay centered; use <strong>Reset</strong> to revert to original EXIF data if needed. Add a title, date, and geographical context before submitting.</dd>

            <dt>Licensing</dt>
            <dd>Submissions use an irrevocable Creative Commons license. You retain copyright while allowing others to use the images.</dd>

            <dt>Your Profile</dt>
            <dd>View your last 100 submissions or the most recent 3 days of activity.</dd>

            <dt>Save/Capture Location</dt>
            <dd>A utility to take photos that save location data in the filename (stored in your <strong>Downloads</strong> folder) to bypass privacy-stripping settings.</dd>

            <dt>View Map</dt>
            <dd>Interactive map showing photo coverage. Toggle between grid squares, individual pins, and global or personalized layers.</dd>
        </dl>
    </section>


    <section id="submit-image-detail">
        <h2>Submit Image: Detailed Guide</h2>
        <p>The submission screen is where you finalize your photo data before it joins the archive. Tap any uploaded image from your list to begin.</p>

        <article id="positioning">
            <h3>1. Positioning (Camera &amp; Subject)</h3>
            <p>Geograph requires two locations: where you stood (Camera) and what you photographed (Subject). Tapping the respective boxes allows you to switch between these two markers.</p>
            <ul>
                <li><strong>Automatic Loading:</strong> If your photo contains EXIF GPS data, the Camera location will pre-load automatically.</li>
                <li><strong>Using the Map:</strong> The map markers for camera and subject are fixed at the <strong>center of the screen</strong>. To position them, drag the map <em>beneath</em> the marker until the crosshair is over the correct spot.</li>
                <li><strong>Alternative Inputs:</strong> You can also set locations by typing an Ordnance Survey grid reference, decimal latitude/longitude, or by using your device's current live GPS.</li>
                <li><strong>Recovery:</strong> If you accidentally drag the map and lose your place, the <strong>Reset</strong> button will revert the markers to the original location found in the image file.</li>
                <li><strong>Notes:</strong> If saved any local notes on the Take Photo page, they are listed here and can be used to center the map.</li>
            </ul>
        </article>

        <article id="metadata">
            <h3>2. Image Information</h3>
            <p>Every submission requires a <strong>Title</strong>, <strong>Date</strong>, and at least one <strong>Geographical Context</strong> from the provided list. These ensure your photo can be found by researchers and enthusiasts.</p>
            <ul>
                <li><strong>Optional Details:</strong> Descriptions, a primary subject, and free-form tags are optional but highly recommended to make your images more useful.</li>
                <li><strong>Smart Suggestions:</strong> For subjects and tags, simply begin typing; the app will suggest standard options to help keep the archive organized.</li>
                <li><strong>Special Formats:</strong> Use the dedicated checkboxes if your image is a <strong>Drone</strong> photo or a <strong>Panorama</strong>, as these may require additional technical details.</li>
            </ul>
        </article>

        <article id="legal">
            <h3>3. Licensing and Attribution</h3>
            <p>Before submitting, verify the photographer attribution. By tapping submit, you agree to the <strong>Creative Commons</strong> license terms.</p>
            <blockquote>
                <strong>Important:</strong> You retain your copyright, but you grant an irrevocable right for others to use the image for any purpose, including commercial use. This applies only to the image size you have selected in your settings.
            </blockquote>
        </article>
    </section>




    <section id="navigation">
        <h2>Navigation</h2>
        <nav>
            <h3>Header</h3>
            <ul>
                <li><strong>Home:</strong> Return to main screen.</li>
                <li><strong>User Icon:</strong> Profile summary and recent images.</li>
                <li><strong>Search:</strong> Basic image search via the main website.</li>
            </ul>

            <h3>Footer Menu</h3>
            <ul>
                <li><strong>Settings:</strong> Dark mode and image size limits.</li>
                <li><strong>Help &amp; Contact:</strong> Links to helpdesk, forums, and reporting tools.</li>
                <li><strong>Reload App:</strong> Refresh for the latest development updates.</li>
            </ul>
        </nav>
    </section>

    <hr>
    <p>Please quote <strong>Version 0.99</strong> when providing feedback.</p>




            <h2 style="margin-top: 20px;">Contributor Questions</h2>
            <p>tap to expand...</p>
            <div id="faq-container" class="faq-list">
                <p class="loading">Loading frequently asked questions...</p>
            </div>
            <p>There are more questions and answers on a <a href="/faq3.php?l=0" target="_blank">full FAQ page</a> (opens in new window)
        </div>
    `;
}

export async function onMount() {
    const container = document.getElementById('faq-container');

    try {
        const response = await fetch('/faq.json.php?contributors=true');
        const faqs = await response.json();

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
