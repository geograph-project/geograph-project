import AppState from '/app/js/app-state.js';

/**
 * Home View Module
 */

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
});

export function render() {
    return `
        <div class="view home-view">
            <h2 align=center>Welcome to Geograph</h2>

            <div class="content-body">
        		<button data-route="/app/help" class="demo-btn">Getting Started</button>

        		<button data-route="/app/capture" class="demo-btn">Take Photo / Save Location</button>

        		<button data-route="/app/upload" class="demo-btn">Upload Image(s)</button>
		        <button data-route="/app/uploaded" id="submit-btn" class="demo-btn">Submit Image</button>
        		<button data-route="/app/submit" id="resume-btn" class="demo-btn">Resume Submission</button>

        		<button data-route="/app/map" class="demo-btn">View Map</button>
		        <button data-route="/app/profile" class="demo-btn">Your Profile</button>

	            <button id="install-btn" class="demo-btn" style="display: none;">Add to Home Screen</button>
            </div>

    	    <p align=center><a href="/" target="_blank">Open Main Site</a><br><br>

            <p align=center><a href=# data-route="/app/settings">Settings &gt;</a>

	    	<br><br>
	        <p align=center class="install-tip">Tip: Use 'Add to Home Screen' from the Chrome menu to install quick loading icon</p>
        </div>

        <!--div style="background-color:#e4e4fc; padding:5px; position:sticky; bottom:0; left:0; right:0; color:blue; border-radius:8px;">
		    Grid Square #02 - March 2026 - <a href=# style="color:blue">view the project newsletter</a>
       	</div-->
    `;
}

function isMobile() {
    return /Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i.test(navigator.userAgent);
}

export function onMount() {
    const resumeBtn = document.getElementById('resume-btn');
    if (!AppState.upload_id || AppState.upload_id === 'none')
	resumeBtn.classList.add('hidden');

    //todo should also hide submit-btn, if no uploaded!

    const installBtn = document.getElementById('install-btn');
    const installTip = document.querySelector('.install-tip');

    // 1. If we are already running as an installed App, hide everything
    if (window.matchMedia('(display-mode: standalone)').matches) {
        if (installBtn) installBtn.style.display = 'none';
        if (installTip) installTip.style.display = 'none';
        return;
    }

    // 2. If the browser gave us the official install prompt
    if (deferredPrompt) {
        installBtn.style.display = 'flex';
        // Dynamically update the text
        installBtn.innerHTML = isMobile() ? 'Add to Home Screen' : 'Install to Desktop';

	// Don't need the tip, now shown the button!
	installTip.style.display = 'none';

    } else if (!isMobile()) {
	// otherwise show the tip on mobile only
	installTip.style.display = 'none';
    }

    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;

        // Show the prompt
        deferredPrompt.prompt();

        // Wait for user choice
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response: ${outcome}`);

        deferredPrompt = null;
        installBtn.style.display = 'none';
    });
}
