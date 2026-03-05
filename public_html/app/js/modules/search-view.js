export function render() {
    return `
        <div class="view search-view">
            <h2>Search Images</h2>
            <form method="get" action="/finder/finder.php">
                <div>
                    Search for: <input type="search" name="q" placeholder="enter keywords">
                </div>
                <div style="margin-top: 10px;">
                    Near: <input type="search" name="loc" id="loc" placeholder="placename, lat/long, etc.">
                    <br>
                    <small>
                        <a href="#" id="get-geo-btn">Use My Location</a>
                    </small>
                </div>
                <button type="submit" style="margin-top: 15px;">Search</button>
            </form>
        </div>
    `;
}

// Private helper function (not exported)
function handleGeolocation() {
    const locInput = document.getElementById('loc');

    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    // Visual feedback
    locInput.placeholder = "Locating...";

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;
            locInput.value = `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
            locInput.placeholder = "placename, lat/long, etc.";
        },
        (error) => {
            console.error("Error getting location:", error);
            locInput.placeholder = "Location access denied";
            alert("Could not get your location. Please type it manually.");
        },
        { enableHighAccuracy: true, timeout: 5000 }
    );
}

export function onMount() {
    console.log('Search View Mounted');

    // Attach the event listener to the link
    const geoBtn = document.getElementById('get-geo-btn');
    if (geoBtn) {
        geoBtn.addEventListener('click', (e) => {
            e.preventDefault();
            handleGeolocation();
        });
    }
}
