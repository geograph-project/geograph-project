/**
 * Home View Module
 */

export function render() {
    return `
        <div class="view home-view">
            <h2>Welcome to Geograph</h2>

            <div class="content-body">
		<button data-route="/app/upload" class="demo-btn">Upload Image</button>

		<button data-route="/app/map" class="demo-btn">View Map</button>

		<button data-route="/app/profile" class="demo-btn">Submitted Images</button>
            </div>

            <a href=# data-route="/app/settings">Settings &gt;</a>
        </div>
    `;
}

export function onMount() {
    console.log('Home View Mounted');
}
