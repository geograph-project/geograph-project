<?

require_once('geograph/global.inc.php');
init_session();

//you must be logged in to submit images
$USER->mustHavePerm("basic");

pageMustBeHTTPS();

dieIfReadOnly();

###################################################
// some basic pages for testing
if (!empty($_GET['inner'])) { ?>
	<html>
	<head>
	<style>.touchPadding li { padding-bottom:10px; } </style>
	</head>
	<body style="font-family: 'Georgia', sans-serif;">

<div class="container">
    <h1>The new Geograph app is here!</h1>
    <p>We've streamlined the experience to help you get things done quickly from your phone.</p>
    <p>Use the app to:</p>
    <ul>
        <li><strong>Contribute effortlessly:</strong> Submit images while you're out in the field.</li>
        <li><strong>Navigate with ease:</strong> Use the local map to find what you need.</li>
        <li><strong>Stay current:</strong> Review the latest submissions.</li>
    </ul>
    
    <div class="links">
        <p>For more information, visit our help pages:</p>
        <ul>
            <li><a href="https://www.geograph.org.uk/article/Geograph-Introductory-letter">Geograph Introductory letter</a></li>
            <li><a href="https://www.geograph.org.uk/article/Geograph-Frequently-Asked-Questions">Contributors FAQ</a></li>
        </ul>
        <p>To access the full range of features, visit our <a href="https://www.geograph.org.uk/">main website</a>.</p>
    </div>
</div>


	</body>
	<? exit;
}

if (!empty($_GET['search'])) { ?>
	<html>
	<head></head>
	<body style="font-family: 'Georgia', sans-serif;margin:0;padding:0">

	<form method=get action="/finder/of.php" style=background-color:#eee;padding:10px;>
		<input type=search name=q style=width:100% placeholder="enter query here">
		<br><br>
		<input type=submit value="Search..." style=width:100%>
	</form>

	<p>If want to search for a location, maybe best to use the <a href="/mapper/combined.php?mobile=1">Map View</a>, click the search icon top left of the map, and search by placename, or can enter a placename in the search box above.</p>

	</body>
	<? exit;
}

###################################################

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geograph Mobile</title>
    <!-- Use Tailwind CSS for easy styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS for fullscreen mobile experience */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Georgia', sans-serif;
        }

        body {
            background-color: #fff;
            display: flex;
            flex-direction: column;
        }
        header {
            background:#000066;
        }

        #app-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Ensure the iframe fills its container */
        .content-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        @media (orientation: landscape) and (max-height: 500px) {
            #app-container {
                flex-direction: row;
            }

            header {
                flex-direction: column;
                justify-content: center;
                width: 80px; /* Adjust width as needed */
                height: 100%;
                border-right: 2px solid #e5e7eb;
                border-bottom: none;
            }

            header .flex-1 {
                --writing-mode: vertical-rl;
                --transform: rotate(180deg);
                margin-bottom: 1rem;
            }

            header button {
                margin-left: 0;
                margin-top: 1rem;
            }
            header button.ml-2 {
                margin-left: 0;
            }

            nav {
                flex-direction: column;
                justify-content: space-around;
                width: 80px; /* Adjust width as needed */
                height: 100%;
                border-left: 2px solid #e5e7eb;
                border-top: none;
            }

            .nav-icon {
                --transform: rotate(-90deg);
            }
        }
    </style>
</head>
<body>
    <div id="app-container">

        <!-- Header Bar -->
        <header class="flex items-center justify-between p-2 border-b-2 border-gray-200 order-first">
            <!-- Geograph Logo -->
            <div class="flex-1 text-center">
                <a href="/"><img src="https://staging.s0.geograph.org.uk/img/geograph-logo.svg" alt="Geograph Logo" class="h-8 mx-auto"></a>
            </div>

            <!-- Search Icon -->
            <button class="p-2 rounded-full bg-white hover:bg-gray-100" data-url="?search=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>

            <!-- Profile Icon -->
            <button class="p-2 rounded-full bg-white hover:bg-gray-100 ml-2" data-url="/profile.php?mobile=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </button>
        </header>

        <!-- Main Content Area with an iframe -->
        <main class="flex-grow">
            <!-- The iframe will load different URLs based on the footer icons -->
            <iframe id="main-content-frame" class="content-frame" src="?inner=1" allow="geolocation"></iframe>
            <iframe id="submit-frame" class="content-frame" style="display:none" allow="geolocation"></iframe>
            <iframe id="map-frame"    class="content-frame" style="display:none" allow="geolocation"></iframe>
        </main>

        <!-- Bottom Navigation Bar -->
        <nav class="flex justify-around items-center p-2 bg-white border-t-2 border-gray-200">
            <!-- Nearby Icon -->
            <!--button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-url="https://m.geograph.org.uk/s/new.php?images">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin"><path d="M12 16.5s-3-4-3-6a3 3 0 0 1 6 0s0 2 3 6"/><circle cx="12" cy="10.5" r="1.5"/></svg>
                <span class="text-xs mt-1">Nearby</span>
            </button-->

            <!-- Submit Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-tab="submit" data-url="/submit-mobile.php?inner=1">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <span class="text-xs mt-1">Submit</span>
            </button>

            <!-- Take Photo Icon (New) -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-tab="take-photo" data-url="/submit-mobile.php?inner=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                <span class="text-xs mt-1">Take Photo</span>
            </button>

            <!-- My Photos Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-url="/submissions.php?mobile=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-images"><path d="M15 8.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/><path d="M7 2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M5 14h.5a2 2 0 0 1 2 2v.5h.5a2 2 0 0 1 2 2v.5H16a2 2 0 0 0 2-2V7.83"/><path d="M22 6v10a2 2 0 0 1-2 2h-1a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1a2 2 0 0 0-2 2v1a2 2 0 0 1-2 2h-1"/><path d="M10 14L2 6"/><path d="m10 14 1.5-1.5a2 2 0 0 1 2.8 0L16 14"/></svg>
                <span class="text-xs mt-1">My Photos</span>
            </button>

            <!-- Map Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-tab="map" data-url="/mapper/combined.php?mobile=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map"><path d="M14.1 6.5a2 2 0 1 0-2.2-2.2l-6.8 6.8a2 2 0 1 0-2.2-2.2l6.8-6.8z"/><path d="m14 14-6 6-4-4"/><path d="M12 12a2 2 0 1 0-2-2l-6 6-4-4z"/><path d="M16 16l4-4a2 2 0 1 0-2-2l-4 4z"/></svg>
                <span class="text-xs mt-1">Map</span>
            </button>

        </nav>
    </div>

    <!-- Hidden file input on the parent page -->
    <input type="file" id="parent-file-input" class="hidden" accept="image/jpeg">

    <script>
        // Get references to the iframe and all navigation buttons
        const iframeMain = document.getElementById('main-content-frame');
        const iframeSubmit = document.getElementById('submit-frame');
        const iframeMap = document.getElementById('map-frame');
        const navIcons = document.querySelectorAll('button[data-url]');
        const fileInput = document.getElementById('parent-file-input');

	var submitLoaded = false;
	var mapLoaded = false;

        // Add a click event listener to each nav button
        navIcons.forEach(icon => {
            icon.addEventListener('click', (event) => {
                // Get the URL from the data-url attribute
                const url = event.currentTarget.getAttribute('data-url');
		const tab = event.currentTarget.getAttribute('data-tab');

		if (tab === 'submit' || tab === 'take-photo') {

		    if (tab !== 'submit' || !submitLoaded) { //dont overwrite a 'active' submission. submit tab only!

                        // Handle the file input after the iframe has loaded
                        iframeSubmit.onload = () => {
                            // Clear the onload handler to prevent it from firing multiple times
                            iframeSubmit.onload = null;

                            // Dynamically set the file input attributes based on the button clicked
                            if (tab === 'submit') {
                                fileInput.removeAttribute('capture');
                            } else if (tab === 'take-photo') {
                                fileInput.setAttribute('capture', 'camera');
                            }

                            // Programmatically click the file input
                            fileInput.click();
                        };

		        iframeSubmit.src = url;
			submitLoaded = true;  //todo, would to have submission reset submitLoaded on 'thank you' page!
		    }

		    iframeMain.style.display = "none";
		    iframeSubmit.style.display = "";
		    iframeMap.style.display = "none";

		} else if (tab === 'map') {
		    if (!mapLoaded) { //only the first time!
		        iframeMap.src = url;
			mapLoaded = true;
		    }
		    iframeMain.style.display = "none";
		    iframeSubmit.style.display = "none";
		    iframeMap.style.display = "";

		} else {
	            iframeMain.src = url;
		    iframeMain.style.display = "";
		    iframeSubmit.style.display = "none";
		    iframeMap.style.display = "none";
		}
            });
        });

        // Event listener for the hidden file input
        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Pass the data URI to the iframe's content page
                    //iframe.src = `submit-view.html?image=${encodeURIComponent(e.target.result)}`;

                    // Post the data URI as a message to the iframe
                    iframeSubmit.contentWindow.postMessage({ type: 'image_data', data: e.target.result }, '*');
                };
                reader.readAsDataURL(file);
            }
        });

        // Set an initial active state on the first icon (optional, but good for UX)
        navIcons[0].classList.add('text-blue-600', 'bg-blue-50');
        // Add a listener to change active state on click
        navIcons.forEach(icon => {
            icon.addEventListener('click', (event) => {
                // Remove active classes from all icons
                navIcons.forEach(i => i.classList.remove('text-blue-600', 'bg-blue-50'));
                // Add active classes to the clicked icon
                event.currentTarget.classList.add('text-blue-600', 'bg-blue-50');
            });
        });

    </script>
</body>
</html>
