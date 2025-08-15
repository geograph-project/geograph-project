<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geograph Mobile Mockup</title>
    <!-- Use Tailwind CSS for easy styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS to center the mobile container and add a shadow */
        body {
            background-color: #f0f4f8; /* A light background color for contrast */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Georgia', sans-serif;
        }

        .mobile-container {
            width: 100%;
            max-width: 390px; /* Standard mobile width */
            height: 844px; /* Standard mobile height */
		max-height: 100dvh;
            background-color: #fff;
            border-radius: 2.5rem; /* Rounded corners to look like a phone */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); /* Large shadow */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Ensure the iframe fills its container */
        .content-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="mobile-container">

        <!-- Header Bar -->
        <header class="flex items-center justify-between p-4 bg-white border-b-2 border-gray-200">
            <!-- Geograph Logo -->
            <div class="flex-1 text-center">
                <img src="https://staging.s0.geograph.org.uk/img/geograph-logo.svg" alt="Geograph Logo" class="h-8 mx-auto">
            </div>

            <!-- Search Icon -->
            <button class="p-2 rounded-full hover:bg-gray-100"  data-url="/of/">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>

            <!-- Profile Icon -->
            <button class="p-2 rounded-full hover:bg-gray-100 ml-2" data-url="/profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </button>
        </header>

        <!-- Main Content Area with an iframe -->
        <main class="flex-grow">
            <!-- The iframe will load different URLs based on the footer icons -->
            <iframe id="main-content-frame" class="content-frame" src="/" allow="geolocation"></iframe>
        </main>

        <!-- Bottom Navigation Bar -->
        <nav class="flex justify-around items-center p-4 bg-white border-t-2 border-gray-200">
            <!-- Nearby Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-url="https://m.geograph.org.uk/s/new.php?images">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin"><path d="M12 16.5s-3-4-3-6a3 3 0 0 1 6 0s0 2 3 6"/><circle cx="12" cy="10.5" r="1.5"/></svg>
                <span class="text-xs mt-1">Nearby</span>
            </button>

            <!-- Submit Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-tab="submit" data-url="/submit-mobile.php?auto=1">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <span class="text-xs mt-1">Submit</span>
            </button>

            <!-- Take Photo Icon (New) -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-tab="take-photo" data-url="/submit-mobile.php?auto=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                <span class="text-xs mt-1">Take Photo</span>
            </button>

            <!-- Map Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-url="/mapper/combined.php?mobile=1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map"><path d="M14.1 6.5a2 2 0 1 0-2.2-2.2l-6.8 6.8a2 2 0 1 0-2.2-2.2l6.8-6.8z"/><path d="m14 14-6 6-4-4"/><path d="M12 12a2 2 0 1 0-2-2l-6 6-4-4z"/><path d="M16 16l4-4a2 2 0 1 0-2-2l-4 4z"/></svg>
                <span class="text-xs mt-1">Map</span>
            </button>

            <!-- My Photos Icon -->
            <button class="nav-icon flex flex-col items-center p-2 rounded-lg hover:bg-gray-100" data-url="/submissions.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-images"><path d="M15 8.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/><path d="M7 2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M5 14h.5a2 2 0 0 1 2 2v.5h.5a2 2 0 0 1 2 2v.5H16a2 2 0 0 0 2-2V7.83"/><path d="M22 6v10a2 2 0 0 1-2 2h-1a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1a2 2 0 0 0-2 2v1a2 2 0 0 1-2 2h-1"/><path d="M10 14L2 6"/><path d="m10 14 1.5-1.5a2 2 0 0 1 2.8 0L16 14"/></svg>
                <span class="text-xs mt-1">My Photos</span>
            </button>
        </nav>
    </div>

    <!-- Hidden file input on the parent page -->
    <input type="file" id="parent-file-input" class="hidden">

    <script>
        // Get references to the iframe and all navigation buttons
        const iframe = document.getElementById('main-content-frame');
        const navIcons = document.querySelectorAll('button[data-url]');
        const fileInput = document.getElementById('parent-file-input');

        // Add a click event listener to each nav button
        navIcons.forEach(icon => {
            icon.addEventListener('click', (event) => {
                // Get the URL from the data-url attribute
                const url = event.currentTarget.getAttribute('data-url');
		const tab = event.currentTarget.getAttribute('data-tab');

		if (url.match(/auto/)) {
                    // Handle the file input after the iframe has loaded
                    iframe.onload = () => {
                        // Clear the onload handler to prevent it from firing multiple times
                        iframe.onload = null;
                        
                        // Dynamically set the file input attributes based on the button clicked
                        if (tab === 'submit') {
                            fileInput.removeAttribute('capture');
                        } else if (tab === 'take-photo') {
                            fileInput.setAttribute('capture', 'camera');
                        }
                        
                        // Programmatically click the file input
                        fileInput.click();
                    };
		}

                // Update the iframe's src attribute
                iframe.src = url;
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
                    iframe.contentWindow.postMessage({ type: 'image_data', data: e.target.result }, '*');
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
