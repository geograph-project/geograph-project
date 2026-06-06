import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `
            <div class="controls" style="position:sticky;top:-20px;left:0;padding:0px">
                <button class=btn id="btn-open" type=button>Open All</button>
                <button class=btn id="btn-collapse" type=button>Collapse All</button>
            </div>

        <div class="view faq-view">

            <h2>App Questions</h2>
            <p class="tap-prompt"><i>tap to expand...</i></p>

            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
        			Why does the app <b>not see the GPS location</b> of my photos?
                </button>
                <div class="faq-content hidden">
        		    <p>Many mobile devices and browsers automatically strip <strong>GPS metadata</strong> (EXIF data) from photos during the upload process. While this is a privacy feature designed to prevent accidental location sharing on social media, it can be inconvenient for Geograph contributors who want to document exact coordinates.</p>

        		    <p>If you find your location data is missing, here are three ways to ensure your coordinates stay attached to your images:</p>

        		    <section>
        		        <h4>1. Use the App's Built-in Camera</h4>
        		        <p>Taking photos directly through the app is the most reliable way to preserve location data.</p>
        		        <ul>
        		            <li><strong>Direct Encoding:</strong> We attempt to encode the GPS location into the filename, making it less likely for browsers to strip the data during upload.</li>
        		            <li><strong>Immediate Upload:</strong> The standalone "Take Photo" page allows you to upload images to the server immediately. This bypasses the local camera roll, guaranteeing the location isn't lost.</li>
        		            <li><strong>Map Integration &amp; Tracking:</strong> Using the camera via <strong>Map Mode</strong> allows you to snap photos without leaving the map. The map also records the location of each photo as it is taken and color-codes them (taken, uploaded, submitted), making it easy to track your progress during a day out.</li>
        		        </ul>
        		    </section>

        		    <section>
        		        <h4>2. Switch from "Photo Chooser" to "File Selector"</h4>
        		        <p>On many devices, the standard "Photo Picker" or Gallery view is responsible for stripping of metadata.</p>
        		        <ul>
        		            <li>On the Upload page, try selecting the <strong>File Selector</strong> Mode.</li>
        		            <li>While navigating folders manually can take more effort, this method is often more reliable for preserving original coordinates.</li>
        		        </ul>
        		    </section>

        		    <section>
        		        <h4>3. Use the "Advanced File Browser"</h4>
        			<p>(Enabled this experimental feature via the Settings page)</p>
        		        <p>You can grant the app access to specific photo folders to create a customized local gallery optimized for contributors.</p>
        		        <ul>
        		            <li><strong>Bypass Privacy Filters:</strong> This method reads coordinates directly from the source folder, sidestepping browser-based stripping.</li>
        		            <li><strong>Smarter Sorting:</strong> Group your local images by <strong>Date</strong> and <strong>Grid Square</strong>.
        			    <li><strong>Duplicate Detection:</strong> The app will automatically flag images you've already submitted, even those uploaded before you started using the app.</li>
        		            <li><strong>One-Tap Sync:</strong> Once configured, you only need to click <strong>"Rescan Folders"</strong> to pick up new images without having to browse for the folders again.</li>
        		        </ul>
        		    </section>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
        			Why does the app separate <b>taking, uploading, and submitting</b> photos?
                </button>
                <div class="faq-content hidden">
                    <p>The app treats taking, uploading, and submitting as three distinct steps to give you maximum flexibility.</p>
                    <p>By separating them, you can capture and upload photos "as you go" throughout your day. Uploading immediately means you won't have to spend time digging through your phone's gallery to upload everything later.</p>

                    <h4>What are my options for taking and uploading photos?</h4>
                    <p>You can choose the workflow that best fits your style:</p>
                    <ul>
                      <li><strong>The Traditional Way:</strong> Take photos using your phone’s standard camera app, then open the Geograph app later to upload them.</li>
                      <li><strong>The "As You Go" Way:</strong> Take and upload photos directly through the app's Capture page.</li>
                      <li><strong>The Map Method:</strong> Tap the camera icon directly on the map to take and upload photos without ever leaving the map view.</li>
                    </ul>

                    <h4>Why is submission a separate step?</h4>
                    <p>Submission is kept separate because adding descriptions, coordinates, or extra data often requires a bit more time and focus. By separating this step, you can upload your photos on the move, then sit down later to finish the submissions—either in the app or on a larger screen using the main website.</p>

                    <h4>Can I upload and submit a photo at the same time?</h4>
                    <p>Yes! If you prefer a faster, combined workflow:</p>
                    <ol>
                      <li>Go to the Upload page.</li>
                      <li>Select your photo.</li>
                      <li>Tick the box to immediately submit the photo once the upload finishes.</li>
                    </ol>

                    <h4>Background</h4>
                    <p>Originally, the app wasn't intended to have its own camera function. However, we discovered that many devices strip location data during standard uploads. 
                      By adding a built-in "Capture" feature, we can embed the coordinates directly into the filename to bypass this restriction.</p>

                    <p>We realized this also created a much faster workflow: you can now upload images instantly as you take them, rather than waiting until later. Plus, by 
                      using the camera icon on the map, you can document your surroundings while keeping the map open for live reference.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
		        	I've uploaded photos in the app, how do I submit them <b>on another device</b>?
                </button>
                <div class="faq-content hidden">
                    You can easily finish your submissions on a larger screen like a tablet, laptop, or desktop computer.
                    Images uploaded via the website or the app, end up in the same folder for submission. Choose the method that works best for you:

                    <ul>
                      <li>
                        <strong>Via the Web App:</strong> 
                        Open <code>geograph.org.uk/app</code> in the browser of your other device and use the standard <strong>Submit</strong> button (this works on desktop screens too).
                      </li>
                      <li>
                        <strong>Via the Main Website (original submission):</strong> 
                        Click <strong>Multi</strong> in the top corner of the page, then select <strong>Submit Images v1</strong>. Your app uploads will be waiting for you in the same folder.
                      </li>
                      <li>
                        <strong>Via the Main Website (using v2):</strong> 
                        Click <strong>Select an uploaded image</strong> to view your pending files, or click <strong>Submit an uploaded image</strong> to immediately begin working on a photo from your uploaded folder.
                      </li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
                    <b>Which camera should I use</b>: my phone's native app or the Geograph app?
                </button>
                <div class="faq-content hidden">
                    <p>Both methods work, but we've found several advantages to using the built-in <strong>Capture</strong> tool within the Geograph app:</p>
                    <ul>
                      <li> <strong>Visual Location Check:</strong> Standard camera apps often use a "stale" location if the GPS hasn't fully locked on yet. With the Geograph app, you 
                        can see your "blue dot" on the map to visually confirm the location is accurate before or just after you take the shot.</li>
                      <li> <strong>Reliable Metadata:</strong> Some devices automatically strip GPS data (EXIF) during uploads for privacy. Our app bypasses this by encoding the 
                        coordinates directly into the filename, ensuring your data stays attached to the image.</li>
                      <li> <strong>Live Reference:</strong> Taking photos via the map allows you to upload quickly while keeping your current grid square and map markers in view for 
                      easier navigation.</li>
                    </ul>
                    <p>If you prefer the advanced features of your phone's native camera (or other dedicated camera app), you can still use it and upload your gallery images later - 
                    but you may need to manually verify the coordinates later if the device's GPS was slow to update.</p>
                </div>
            </div>

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
    "title": "Can I use the app [without installing] it?",
    "content": "Yes! The Geograph app is fully functional directly within your mobile browser. You don't need to download anything from an app store to start capturing and uploading photos; simply open the link and go."
  },
  {
    "title": "How do I [install] the app on my device?",
    "content": "As a Progressive Web App (PWA), there is no need to visit an App Store. You can install it directly through your mobile browser while connected to the internet:\n\n* **Android (Chrome):** Open the app link, tap the three-dot menu (⋮), and select 'Install app' or 'Add to home screen.'\n* **iOS/iPhone (Safari):** Open the app link, tap the 'Share' icon (the square with an up arrow), and scroll down to select 'Add to Home Screen.'\n\nOnce added, the Geograph icon will appear on your home screen and function like a regular native app."
  },

  {
    "title": "Are there specific [permissions] or settings required?",
    "content": "The app requires access to your device's location, camera, and filestore to be fully functional. You must grant these permissions when prompted or via your device settings. Additionally, ensure \"Desktop site\" is disabled in your browser settings so the app scales correctly to your screen."
  },

  {
    "title": "Can I change where [photos taken via the app are saved]?",
    "content": "No, because the app runs in your browser, images are saved to your device's default 'Downloads' folder. This is standard behavior for web downloads.\n\nHowever, this is mostly meant as a backup! Normally, you can tap the upload button immediately after capturing the photo to submit it directly, without needing to browse your files. You only need to look in your Downloads folder if you want to edit the image first or upload it later. If you want these files in your main gallery, you can set apps like Google Photos to back up your Downloads folder.\n\nIf being able to choose a different save location is important to you, please let us know via the **Feedback** option in the main menu."
  },

/*
  {
    "title": "What should I know about [capturing and uploading] images?",
    "content": "For the best experience, we recommend using your device's native camera app; it is generally faster and more reliable at embedding GPS data. If you find your browser strips location data during upload, you can use our in-app 'Take Photo' tool - this acts as a workaround by encoding the location directly into the filename. Once captured, you must manually select and upload your best images from your gallery. After uploading, you'll complete the 'Submission' by adding a title and tags; the app will attempt to pull the date and location automatically from the image EXIF data. You can upload multiple images at once and even finish the final submission later on a different device, such as a tablet (on the app) or desktop/laptop via main site."
  },
*/
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

