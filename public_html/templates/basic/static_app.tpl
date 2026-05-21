{assign var="page_title" value="Geograph App"}
{include file="_std_begin.tpl"}

<section id="geograph-app-landing" style="max-width:60em">
    <h2>The Geograph App</h2>
    
    <p class="intro">Our app allows you to take, upload, and submit photos to <b>Geograph Britain and Ireland</b>, review your recent submissions, view maps, and search for images 
        directly from your device.</p>

    {dynamic}
    {if $user->registered}
        <div class="user-status interestBox" style=" border-radius:9px">
            As you are already registered, you can simply log in with your existing credentials when you first open the app.
        </div><br>
    {else}
        <div id="post-reg-notice" style="display:none; background: #fffde7; border: 1px solid #ffd54f; padding: 1em; margin: 1em 0;  border-radius:9px">
            <h3 style="margin-top:0">Registration received!</h3>
            <p>Please check your email and follow the confirmation link to activate your account. Once confirmed, you will be able to use your new login details in the app.</p>
        </div>

        <div id="registration-notice" style="border: 1px solid #ccc; padding: 1em; margin: 1em 0; background-color:#e4e4fc;  border-radius:9px">
            <h3 style="margin-top:0">Before you start</h3>
            <p>To use the app, you must have a Geograph account. Registration is not yet available inside the app, so please create your account here first.</p>
            &#128073; <a href="/register.php?redir=/help/app%3Fregistered"><strong>Register for Geograph</strong></a>
        </div>
    {/if}
    {/dynamic}

    <div id="app-instructions">
        <h2 id="platform-heading">Get the App</h2>
        
        <div id="desktop-msg" style="display:none;">

            <p>The Geograph App is not found in the Google Play or Apple App Stores. Instead, you access it directly via your web browser, but can also install it to function like a 
                native app, with a quick access icon you can add to your home screen.</p>

            <p>For the best experience (taking and uploading photos in the field), <strong>open this URL on your mobile device:</strong></p>
            
            <p style="font-family: monospace; font-size:1.2em; background: #eee; padding: 10px; display: inline-block;">
                geograph.org.uk/app
            </p>

            <p>Once opened on your mobile, you have two choices:</p>
            <ol>
                <li><strong>Use directly:</strong> Just use it in your mobile browser like any other website.</li>

                <li><strong>Install it:</strong> Use <strong>Chrome</strong> (Android) or <strong>Safari</strong> (iOS) to "Add to Home Screen." This installs the app icon on your 
                device so it works just like a native app.</li>
            </ol>

            <p>Want to see how it looks now?<br>
            &#128073; <a href="https://www.geograph.org.uk/app/" class="button">Launch App on Desktop</a></p>    
        </div>
        
        <div id="mobile-msg" style="display:none;">
            <div style="margin-bottom: 20px;">
                1. <a href="https://www.geograph.org.uk/app/" class="button"
                style="font-weight:bold; font-size: 1.2em; padding: 10px 20px; background: #007aff; color: #fff; text-decoration: none; border-radius: 8px;"
                >Open the App</a>
            </div>

            <div id="install-guide">
                <h3 style="margin-top:0;">2. (Optional) Install to Home Screen</h3>
                
                <div id="android-path" style="display:none;">
                    <div id="is-chrome" style="display:none;">
                        <p>Once the app is open in <strong>Chrome</strong>, tap the menu (&#8942;) and select <strong>"Add to home screen"</strong> or <strong>"Install"</strong> to use it like a native app.</p>
                    </div>
                    <div id="not-chrome" style="display:none;">
                        <p>To install this as a native-style app, please ensure you have opened <strong>geograph.org.uk/app</strong> in the <strong>Google Chrome</strong> browser.</p>
                        <p><The App will use Chrome to run, but you can continue using your favorite browser for everything else.</p>
                    </div>
                </div>

                <div id="ios-path" style="display:none;">
                    <div id="is-safari" style="display:none;">
                        <p>Once the app is open in <strong>Safari</strong>, tap the <strong>Share</strong> icon (square with arrow) and choose <strong>"Add to home screen"</strong>.</p>
                    </div>
                    <div id="not-safari" style="display:none;">
                        <p>To install this as a native-style app, please ensure you have opened <strong>geograph.org.uk/app</strong> in the <strong>Safari</strong> browser.</p>
                        <p>The App will use Safari to run, but you can continue using your favorite browser for everything else.</p>
                    </div>
                </div>
            </div>

            <p>You can always use the app directly without installing.</p>
        </div>
    </div>

    <div style="background-color:#e4e4fc;padding:10px; border-radius:9px">
	We hope you enjoy using the Geograph app. It cost &pound;25k to run Geograph in 2025. Please consider making a one-off or regular donation. See <a href="https://www.geograph.org.uk/help/donate">geograph.org.uk/help/donate</a>
    </div>

    <div class="app-faq-preview">
        <h3>Privacy &amp; Permissions</h3>

        <p><strong>Does the app track my location?</strong> No. As a web app, we only access your location when using the app, and only for explicit features, such as centering the 
        map or geotagging a photo you take. We do not use your position data outside of these functions.</p>

        <h3>Important: Browser Settings</h3>
        <p>The app shares settings with the main Geograph website. If the app isn't working as expected, check the following:</p>
        <ul>
            <li><strong>Location Access:</strong> If you previously denied location access to the Geograph website, the app won't be able to find you. In Chrome, check "Site 
            settings" within "Settings" to allow it.</li>
            <li><strong>Desktop Site Mode:</strong> If your mobile browser is set to "Request Desktop Site," the app will not scale correctly. Ensure this mode is turned off for 
            <tt>www.geograph.org.uk</tt></li>
        </ul>
    </div>

    <div class="app-info-footer">
        <h3>Good to know</h3>
        <ul>
            <li><strong>Getting Started:</strong> Use the 'Getting Started' button within the app to find more information and access the FAQ.</li>
            <li><strong>Offline Use:</strong> The app requires a connection to open and upload. However, if you have already loaded a map area, it is cached so you can continue to 
            view the map and take photos even if you lose your signal in the field.</li>
            <li><strong>Beta Version:</strong> This app is still in development. We welcome all feedback via the link in the app menu.</li>
            <li><strong>Full Website:</strong> The app focuses on field tools; for advanced features, visit the main site at <a href="http://www.geograph.org.uk">geograph.org.uk</a>.</li>
        </ul>
    </div>
</section>

<script>{literal}
(function() {
        // Check for the 'registered' flag in the URL
        if (window.location.search.indexOf('registered') !== -1) {
            var regNotice = document.getElementById('registration-notice');
            var postRegNotice = document.getElementById('post-reg-notice');
            if (regNotice) regNotice.style.display = 'none';
            if (postRegNotice) postRegNotice.style.display = 'block';
        }

        var ua = navigator.userAgent || navigator.vendor || window.opera;
        var desktop = document.getElementById('desktop-msg');
        var mobile = document.getElementById('mobile-msg');
        var heading = document.getElementById('platform-heading');
        
        var isAndroid = /android/i.test(ua);
        var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        var isChrome = /Chrome|CriOS/i.test(ua);
        var isSafari = /Safari/i.test(ua) && !/Chrome|CriOS|OPiOS|mercury|FxiOS/i.test(ua);

        if (isAndroid) {
            mobile.style.display = 'block';
            heading.innerHTML = "Use/Install on Android";
            document.getElementById('android-path').style.display = 'block';
            if (isChrome) {
                document.getElementById('is-chrome').style.display = 'block';
            } else {
                document.getElementById('not-chrome').style.display = 'block';
            }
        } 
        else if (isIOS) {
            mobile.style.display = 'block';
            heading.innerHTML = "Use/Install on iOS";
            document.getElementById('ios-path').style.display = 'block';
            if (isSafari) {
                document.getElementById('is-safari').style.display = 'block';
            } else {
                document.getElementById('not-safari').style.display = 'block';
            }
        } 
        else if (/Mobi|Tablet|IPad/i.test(ua)) {
            mobile.style.display = 'block';
        } 
        else {
            desktop.style.display = 'block';
        }
    })();
</script>{/literal}

{include file="_std_end.tpl"}

