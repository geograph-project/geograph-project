import { escapeHTML } from '/app/js/utils.js';

// Helper to format the title
const formatTitle = (text) => text.replace(/\[(.*?)\]/g, '<b>$1</b>');

export function render() {
	const versionNumber = document.getElementById('versionNumber').textContent;

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

/* Switch to side-by-side layout on screens wider than 600px */
@media (min-width: 800px) {
    .app-header .btn {
        width: auto;      /* Override the 100% width */
        max-width: 400px; /* Cap the width as requested */
        flex: 0 1 auto;   /* Don't grow, but can shrink if needed */
        text-align: right;
    }
    
    .app-header {
        flex-wrap: nowrap; /* Keep them on one line on desktop */
    }
}


    section h3 {
	border-bottom:1px solid gray;
    }
    section h4 {
	border-bottom:1px solid silver;
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
	section.intro p {
		margin-left:0;
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
        <h2>Welcome to the Geograph App <sup>${versionNumber}</sup></h2>
        <button class="btn btn-primary" data-route="/app/faq">App and Contributor Questions &gt;</button>
    </div>

    <hr>

    <main class="view">
        <section class="intro">
            <p>Geograph is a free-to-use photographic archive of Britain and Ireland.</p>
            <p>This is a beta release of the Geograph app and is still in development.</p>
            <p>Some app functions are not yet fully implemented and some link to the main website.</p>
            <p>All feedback is welcome &ndash; there is a link to a feedback form on the app menu.</p>
            <p>The app allows you to take, upload and submit photos to Geograph, review your recent submissions, view maps and search for images.</p>
            <p>It does not include all the functions found on the main website at http://www.geograph.org.uk</p>
	</section>

        <section>
            <h3>Login</h3>
            <p>You will be prompted to login to Geograph to use the app.</p>
            <p>You must be registered on the Geograph website first, as registration is not yet enabled within the app itself.</p>
            <p>To avoid being asked every time, select the option for the site to remember you.</p>
        </section>

        <section>
            <h3>Installation</h3>
            <p>The Geograph App is not found in the Google Play or Apple App Stores.</p>
            <p>Instead, you access it directly via your web browser, but can also install it to function like a native app, with a quick access icon you can add to your home screen.</p>
            <p>It does require an internet connection to open and be fully functional: you will not be able to upload images or load new map areas if your device has no data signal.</p>
            <p>To get installation guidance specific to your device, visit https://www.geograph.org.uk/help/app In particular, note that you must use Chrome to install the app on Android and Safari to install on IoS.</p>
        </section>

        <section>
            <h3>Location, camera, files settings</h3>
            <p>To be fully functional, the app will need to access your device&rsquo;s location, camera and filestore.</p>
            <p>In most cases you will be asked for permission the <b>first time</b> you use the app and these will be remembered.</p>
            <p><b>Permissions</b> can also be set (or revoked) via your device&rsquo;s settings menu.</p>
            <p>If you use the main Geograph website in your browser on the same device, the same settings will apply e.g.</p>
		<ul><ul>
			<li>if you deny location access to the website, it will not be available in the app until you allow it.</li>
		        <li>In Chrome this is found under &ldquo;Site settings&rdquo; within &ldquo;Settings&rdquo;.</li>
		</ul></ul>
            <p>If you choose &ldquo;Desktop site&rdquo; in your browser for the main website, the app will not be correctly scaled for mobile: again, you can change it in the browser settings.</p>
            <p>Once enabled, <b>device location</b> enhances various map functions.</p>
            <p>You can take photos with your native camera app or within the Geograph app.</p>
            <p>If you would like to save location data on your image files, you need to allow your camera to access location, via your device settings.</p>
            <p>On some devices e.g. Android on Samsung, privacy rules deliberately prevent image location data from being uploaded via the native camera and image gallery.</p>
		<ul><ul>
	            <li>To get around this, you can take photos from within the app, and follow the guidance on selecting images for upload.</li>
		</ul></ul>
        </section>

        <section>
            <h3>Home screen</h3>
            <p>The home screen of the app includes a header, a page with buttons for the main functions and a footer.</p>

            <article>
                <h4>Take Photo/Save Location</h4>
                <p>This screen contains two functions.</p>
                <p><b>Take Photo</b> allows you to take a photo from within the app using your device&rsquo;s camera.</p>
                <p>You will be asked to confirm that you wish to use the photo (&ldquo;OK&rdquo;/&ldquo;Use Photo&rdquo;) or be offered the chance to take it again.</p>
                <p>Photos taken with this function are saved to the &ldquo;Downloads&rdquo; folder on your device.</p>
                <p>Device location is written into the image filename so that it can be accessed during image submission, which should retain locational information that is otherwise stripped by some Android devices.</p>
                <p>After taking a photo you have the option to proceed directly to upload it.</p>
                <p><b>Create Location Note</b> allows you to save an annotated location (on this device only). This may be useful e.g. 
                   to record a waypoint on a walk or the location of an image subject such as a summit.</p>
                <p>These locations can be accessed during image submission.</p>
            </article>

            <article>
                <h4>Upload Image</h4>
                <p>Allows one or more images to be chosen from your device and uploaded to the server.</p>
                <p>Photos taken with the app will be in your &ldquo;Downloads&rdquo; folder.</p>
                <p>You can use other apps on your device to preview images before uploading and use other tools as necessary to straighten, crop, etc.</p>
                <p>Images may be tagged as e.g. needing rotation or missing geolocation data. You can still proceed.</p>
                <p>There is a checkbox which will proceed directly to image submission after a single upload.</p>
                <p>After uploading photos you have the option to proceed directly to submit it.</p>
            </article>

            <article>
                <h4>Submit Image</h4>
                <p>Shows a list of uploaded images.</p>
                <p>There is an option to select and delete images from this list. Tap an image to begin submission.</p>
                <p>The submission form allows you to rotate an image if necessary: <b>pay attention to any rotation warnings</b>.</p>
                <p>You need to enter a <b>location for the photo subject</b>.</p>
                <p>Camera location will be displayed if it is available from the image file, otherwise locate the image by another means, then repeat for the photo subject.</p>
                <p>Depending on your settings, you can use location from <b>the image file itself</b>, <b>your current location</b> (pin symbol on map), re-use the <b>last confirmed location</b> (history symbol on map), a <b>saved location note</b> (drop-down list), by <b>typing an Ordnance Survey grid reference</b> (or decimal latitude/longitude) or by <b>dragging a map</b>.</p>
                <p>When using the map, the markers for the camera and photo subject are fixed at the centre of the screen and the map is dragged beneath them.</p>
                <p>When they are correct, just move on to the next box.</p>
                <p><b>Tapping on the subject/camera boxes</b> allows you to switch between them.</p>
                <p>If you tap away from them both, the map position will be fixed.</p>
                <p>Images require a <b>title, date and one or more Geographical contexts</b> from the list displayed.</p>
                <p>Descriptions, a primary subject, free-form tags and shared descriptions are <b>optional</b>, but these all make your images more useful.</p>
                <p>For subjects, tags and shared descriptions just start typing and choose from the listed options.</p>
                <p>The title and description editor will suggest nearby placenames. There are checkboxes for drone and panorama images (which will request additional information).</p>
                <p>You should always <b>pay attention to the photographer attribution and licensing information</b> and don&rsquo;t submit unless you are happy to accept the Creative Commons licence terms &ndash; these mean you retain the copyright but others can use the images for any purpose (including commercial) and the licence is irrevocable (but applies only to the image size you have chosen to upload &ndash; see Settings, in the Menu at bottom right).</p>
                <p>If you wish to abandon the current submission select &ldquo;Return to list without submitting&rdquo; at the bottom of the screen.</p>
                <p>You can either proceed with a different image or resume this one later.</p>
                <p>On successful submission you will see the imageID that has been assigned to your photo and may proceed to submit another image.</p>
            </article>

            <article>
                <h4>Resume submission</h4>
                <p>If you have navigated away from an unfinished submission, you can resume it here.</p>
                <p><i>(This button only appears when you have an unfinished submission.)</i></p>
            </article>

            <article>
                <h4>Your profile</h4>
                <p>Summarizes your Geograph submissions and shows images you submitted in the last 3 days, or the last 100 images (switchable), including their status.</p>
                <p>You can search your submissions or tap an image to enlarge it.</p>
                <p>Tapping an image will offer the option to view or edit it.</p>
                <p>Titles and descriptions can be amended and image IDs easily copied within the app (&ldquo;Quick Edit Image&rdquo;).</p>
                <p>For more complex amendments, there is a link to the edit page on the main site.</p>
            </article>

            <article>
                <h4>View Map</h4>
                <p>Displays an interactive Geograph map showing photo coverage and a choice of map layers.</p>
                <p>Device location can be used. Note that due to Ordnance Survey licensing restrictions, current large scale (1:50,000 and more detailed) Ordnance Survey mapping cannot be accessed here: it is still available for image submission.</p>
                <p>The layer control includes the option to display recently taken and recently submitted images.</p>
                <p>The map pin icon will centre the map on your current location.</p>
                <p>There is a Take Photo button to quickly take a photo without really leaving the map. After taking optionally can upload it right away to the server for submission later. Works in similar way to the button on the standalone page, in that encodes location to filename, and is saved in Downloads folder.</p>
            </article>
        </section>

        <section>
            <h3>App Header</h3>
            <ul>
                <li><b>Home button</b>, header top left - returns to the app home screen</li>
                <li><b>User icon</b>, header top right - shows an abbreviated personal profile and recent images.<ul>
                    <li>If you have pending images, they will also be listed here.</li></ul></li>
                <li><b>Search icon</b>, header top right &ndash; uses a new search interface based on keywords and locations.<ul>
                    <li>You can restrict the search to just your own images, or can search around your device&rsquo;s current location.</li>
                    <li>Search results are not yet optimally formatted for mobile.</li></ul></li>
            </ul>
        </section>

        <section>
            <h3>App Footer</h3>
            <p>Menu, footer bottom right &ndash; opens a menu containing</p>
            <ul>
                <li><b>Open main site</b> &ndash; opens main site in web browser</li>
                <li><b>Settings</b> &ndash; access to app settings. The following are currently switchable:<ul>
                    <li>Dark mode</li>
                    <li>Maximum image resolution to be released</li>
                    <li>Auto-Locate Me on Map</li>
                    <li>Show Bottom Navigation Buttons</li>
                    <li>Small Header/Footer</li></ul></li>
                <li><b>App Help</b> &ndash; an abbreviated version of Geograph help FAQ</li>
                <li><b>Contact Us</b> &ndash; a link that will send a message to the Geograph helpdesk system</li>
                <li><b>Report a Concern</b> &ndash; a link to be used if you spot anything potentially illegal or harmful on the website.<ul>
	                <li>Please do not use this for general contact enquiries &ndash; use Contact Us!</li></ul></li>
                <li><b>Terms of Service</b> &ndash; Geograph&rsquo;s Terms of Service and Privacy Policy.<ul>
	                <li>These apply to both the app and the website</li></ul></li>
                <li><b>Open Discussion Forum</b> &ndash; a link to the discussion forums on the website</li>
                <li><b>Reload App (during dev)</b> &ndash; a link to reload the app pages during development</li>
                <li><b>App feedback</b> &ndash; a link to a Google feedback form asking about your experience of using the app today.<ul>
        	        <li>This will not record your identity so do not use it to send messages for which you expect a personal reply and do not include any personal information.</li></ul></li>
            </ul>
        </section>

    </main>

	<hr>
        <p>App version number: ${versionNumber}</p>
        <p><i>Please quote this if contacting us with feedback about a specific app issue, for example if reporting something not working.</i></p>

    `;
}

export async function onMount() {
}
