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
		<button data-route="/app/upload" class="demo-btn">Upload Image</button>
		<button data-route="/app/submit" class="demo-btn">Submit Image</button>
		<button data-route="/app/profile" class="demo-btn">Submitted Images</button>
		<button data-route="/app/recent" class="demo-btn">Review Submissions</button>

		<button data-route="/app/map" class="demo-btn">View Map</button>

	        <button id="install-btn" class="demo-btn" style="display: none;">
                    Add to Home Screen
                </button>
            </div>

            <p align=center><a href=# data-route="/app/settings">Settings &gt;</a>
		<br><br>
	    <p align=center>Tip: Use 'Add to Home Screen' from the Chrome menu to install quick loading icon</p>

        </div>
    `;
}

export function onMount() {
    const installBtn = document.getElementById('install-btn');

    if (deferredPrompt) {
        installBtn.style.display = 'flex';
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
