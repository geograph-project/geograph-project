import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
    return `

<style>
.app-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap; /* Stacks vertically on very narrow screens */
    gap: 10px;
}
.app-header h2 {
    margin: 0;
}
.app-header sup {
    color:gray;
}

.faq-link {
    margin: 0;
}


    section ul {
        margin-left:26px;
    }
    section li {
        margin-bottom:6px;
    }

    section li li {
        font-style: italic;
    }
    section dt {
        font-weight:bold;
    }
    section dd, section p {
        margin-left:18px;
        margin-bottom:8px;
    }
    section b {
        font-weight:500;
    }
    hr {
        margin-top:20px;
        margin-bottom:20px;
    }
</style>

    <div class="app-header">
        <h2>Welcome to the Geograph App <sup>V0.99</sup></h2>
        <p class="faq-link">View <a href="/app/faq">Contributor Questions</a></p>
    </div>

    <hr>

    <main>
    	<section>
            <p>Geograph is a free-to-use photographic archive of Britain and Ireland.</p>
            <p>This is a test version of the Geograph app which is still in development.</p>
            <p>Some app functions are not yet fully implemented and some link to the main website.</p>
            <p>All feedback is welcome &ndash; there is a link to a feedback form on the app menu.</p>
            <p>The app allows you to take, upload and submit photos to Geograph, review your recent submissions, view maps and search for images.</p>
            <p>It does not include all the functions found on the main website at http://www.geograph.org.uk</p>
        </section>

        <section>
            <h3>Login</h3>
            <p>You will be prompted to login to Geograph to use the app.</p>
            <p>You must be registered on the Geograph website first, as registration is not yet enabled within the app.</p>
            <p>For the best app experience, select the option for the site to remember you.</p>
        </section>

        <section>
            <h3>Installation</h3>
            <p>This is a &ldquo;progressive web app&rdquo; (a website that can be downloaded and installed like an app or opened in a browser).</p>
            <p>It does require an internet connection to be fully functional: you will not be able to upload images or browse maps if your device has no data signal.</p>
            <p>On Android &ndash; open the App link in the Chrome browser, tap the three-dot menu icon in the top right, and select &quot;Add to home screen&quot; and choose &ldquo;Install&rdquo;</p>
            <p>On Apple IoS &ndash; open the app link in Safari browser and tap the &ldquo;Share&rdquo; button.</p>
            <p>Choose the &ldquo;Add to home screen&rdquo; option.</p>
            <p>In both cases, the Geograph app icon will be added to your home screen.</p>
            <p>You do need to use the specified browsers to install the app.</p>
        </section>

        <section>
            <h3>Location, camera, files settings</h3>
            <p>To be fully functional, the app will need to access your device&rsquo;s location, camera and filestore.</p>
            <p>In most cases you will be asked for permission the first time you use the app and these will be remembered.</p>
            <p>Permissions can also be set (or revoked) via your device&rsquo;s settings menu.</p>
            <p>If you use the main Geograph website in your browser on the same device, the same settings will apply &ndash; e.g.</p>
            <p>if you denied location access to the website, it will not be available in the app until you allow it.</p>
            <p>In Chrome this is found under &ldquo;Site settings&rdquo; within &ldquo;Settings&rdquo;.</p>
            <p>If you have chosen &ldquo;Desktop site&rdquo; in your browser for the main website, the app will not be correctly scaled: again, you can change it in the browser settings.</p>
            <p>Once enabled, device location enhances various map functions. You can take photos with your native camera app or within the Geograph app.</p>
            <p>If you would like to save location data on your image files, you need to allow your camera to access location, via your device settings.</p>
            <p>In some cases e.g. Android on Samsung, privacy rules deliberately prevent image location data from being uploaded.</p>
            <p>You can take photos from within the app, and the location will be saved into the filename and available to assist with submission.</p>
        </section>

        <section>
            <h3>Home screen</h3>
            <p>The home screen of the app includes a header, a page with buttons for the main functions and a footer.</p>

            <article>
                <h4>Upload Image</h4>
                <p>Allows one or more images to be chosen from your device and uploaded to the server.</p>
                <p>Photos taken with the app will be in your &ldquo;Downloads&rdquo; folder.</p>
                <p>Preview images before uploading and use your device tools as necessary to straighten, crop, etc. There is a checkbox which will proceed directly to image submission after a single upload.</p>
                <p>Images may be tagged as e.g. needing rotation or missing geolocation data. You can still proceed.</p>
            </article>

            <article>
                <h4>Submit Image</h4>
                <p>Shows a list of uploaded images.</p>
                <p>There is an option to select and delete images from this list. Tap an image to begin submission.</p>
                <p>The submission form allows you to rotate an image if necessary: <b>pay attention to any rotation warnings</b>.</p>
                <p>You need to enter <b>location the photo subject</b>, and please provide the camera location.</p>
                <p>Camera location will be pre-loaded if it is available from the image file, otherwise locate the image by another means, then repeat for the photo subject.</p>
                <p>Depending on your settings, you can use location from the <b>image file itself</b>, <b>your current location</b> (pin symbol on map), re-use the <b>last confirmed location</b> (history symbol on map), a <b>saved location note</b> (drop-down list), by <b>typing an Ordnance Survey grid reference</b> (or decimal latitude/longitude) or by <b>dragging a map</b>.</p>
                <p>If camera location is available from the image, it will be displayed on the map.</p>
                <p>When using the map, the markers for the camera and photo subject are fixed at the centre of the screen and the map can be dragged beneath them.</p>
                <p>When they are correct, just move on to the next section.</p>
                <p><b>Tapping on the subject/camera boxes</b> allows you to switch between them.</p>
                <p>Images require a <b>title, date and one or more Geographical contexts</b> from the list displayed.</p>
                <p>Descriptions, a primary subject and free-form tags are <b>optional</b>, but these all make your images more useful.</p>
                <p>For subjects and tags, just start typing and choose from the listed options.</p>
                <p>The title and description editor will suggest nearby placenames. There are checkboxes for drone and panorama images (which will request additional information).</p>
                <p>You should always <b>pay attention to the photographer attribution and licensing information</b> and don&rsquo;t submit unless you are happy to accept the Creative Commons licence terms &ndash; these mean you retain the copyright but others can use the images for any purpose (including commercial) and the licence is irrevocable (but applies only to the image size you have chosen to upload &ndash; see Settings, in the Menu at bottom right).</p>
                <p>If you wish to abandon the current submission select &ldquo;Return to list without submitting&rdquo; at the bottom of the screen.</p>
                <p>You can either proceed with a different image or resume this one later. On successful submission you will see the imageID that has been assigned to your photo and may proceed to submit another image.</p>
            </article>

            <article>
                <h4>Resume submission</h4>
                <p>If you have navigated away from an unfinished submission, you can resume it here.</p>
                <p><i>This button only appears when you have an unfinished submission.</i></p>
            </article>

            <article>
                <h4>Your profile</h4>
                <p>Summarizes your Geograph submissions and shows images you submitted in the last 3 days, or the last 100 images (switchable).</p>
                <p>You can search your submissions or tap an image will enlarge it, with the option to view or edit it (currently links to the main website).</p>
            </article>

            <article>
                <h4>Take Photo/Save Location</h4>
                <p>This screen contains two functions.</p>
                <p>Take Photo allows you to take a photo from within the app.</p>
                <p>You will be asked to confirm that you wish to use the photo (&ldquo;OK&rdquo;/&ldquo;Use Photo&rdquo;) or be offered the chance to take it again.</p>
                <p>Photos taken with this function are saved to the &ldquo;Downloads&rdquo; folder on your device.</p>
                <p>Android users may find this particularly useful as device location is written into the image filename so that it can be accessed during image submission.</p>
                <p>Create Location Note allows you to save an annotated location (on this device only). This may be useful e.g.</p>
                <p>to record a waypoint on a walk or the location of an image subject such as a summit.</p>
                <p>These locations can be accessed during image submission.</p>
            </article>

            <article>
                <h4>View Map</h4>
                <p>Displays an interactive Geograph map showing photo coverage and a choice of backgrounds.</p>
                <p>Device location can be used. Note that due to Ordnance Survey licensing restrictions, current large scale (1:50,000 and more detailed) Ordnance Survey mapping cannot be accessed here: it is still available for image submission.</p>
            </article>
        </section>

        <section>
            <h3>App Header</h3>
            <ul>
                <li><b>Home button</b></li>
                <li><b>User icon</b>, header top right - shows an abbreviated personal profile and recent images.<ul>
                    <li>If you have pending images, they will also be listed here.</li></ul></li>
                <li><b>Search icon</b>, header top right &ndash; uses a new search interface based on keywords and locations.<ul>
                    <li>You can restrict the search to just your own images, or can search around your device&rsquo;s current location.</li></ul></li>
            </ul>
        </section>

        <section>
            <h3>App Footer</h3>
            <p>Menu, footer bottom right &ndash; opens a menu containing</p>
            <ul>
                <li><b>Open main site</b> &ndash; opens main site in web browser</li>
                <li><b>Settings</b> &ndash; access to app settings, presently dark mode and maximum image size to be released, you can also choose to display navigation buttons on the footer and change the size of the header/footer.</li>
                <li><b>App Help</b> &ndash; an abbreviated version of Geograph help FAQ</li>
                <li><b>Contact Us</b> &ndash; a link that will send a message to the Geograph helpdesk system</li>
                <li><b>Report a Concern</b> &ndash; a link to be used if you spot anything potentially illegal or harmful on the website.<ul>
	                <li>Please do not use this for general contact enquiries &ndash; use Contact Us!</li></ul></li>
                <li><b>Terms of Service</b> &ndash; Geograph&rsquo;s Terms of Service and Privacy Policy.<ul>
	                <li>These apply to both the app and the website</li></li></ul>
                <li><b>Open Discussion Forum</b> &ndash; a link to the discussion forums on the website</li>
                <li><b>Reload App (during dev)</b> &ndash; a link to reload the app pages during development</li>
                <li><b>App feedback</b> &ndash; a link to a Google feedback form asking about your experience of using the app today.<ul>
        	        <li>This will not record your identity so do not use it to send messages for which you expect a personal reply and do not include any personal information.</li></ul></li>
            </ul>
        </section>

    </main>

	<hr>
        <p>App version number: v0.99</p>
        <p><i>Please quote this if contacting us with feedback about a specific app issue, for example if reporting something not working.</i></p>

    `;
}

export async function onMount() {
}
