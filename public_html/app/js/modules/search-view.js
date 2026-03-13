export function render() {
    return `

<form method="get" name=theForm id=theForm action="/finder/finder.php" class="search-form">
    <div class="form-group">
        <label for="q">Search for:</label>
        <input type="search" name="q" id="q" placeholder="enter keywords">
    </div>

    <div class="form-group">
        <label for="loc">Near:</label>
        <div class="location-input">
            <input type="search" name="loc" id="loc" placeholder="placename, lat/long, etc.">
            <a href="#" id="get-geo-btn">Use My Location</a>
        </div>
    </div>

    <div id="my-group" class="checkbox-group">
        <input type="checkbox" name="contributor" id="my-images">
        <label for="my-images">Only Search Your images</label>
    </div>

    <input type="hidden" name="inner" value="true">
    <input type="hidden" name="standalone" value="true">

    <div class="form-group">
	<label></label>
        <div>
	    <button type="submit" class="btn btn-primary">Search</button>
	</div>
    </div>
</form>

<style>
    .search-form {
        display: grid;
        grid-template-columns: 80px 1fr; /* Defines a fixed width for labels */
        gap: 15px;
        align-items: center;
        max-width: 500px;
    }

    .form-group {
        display: contents; /* Allows children to participate in the parent grid */
    }
    .form-group input[type=search] {
        border-radius:10px;
    }

    .location-input {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .checkbox-group {
        grid-column: span 2; /* Spans across the full width */
	text-align:center;
    }

</style>

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
    if (window.GEOGRAPH_USER_PREFERENCES && window.GEOGRAPH_USER_PREFERENCES['user_id'])
        document.getElementById('my-images').value = window.GEOGRAPH_USER_PREFERENCES['user_id']+" Myself";
    else
         document.getElementById('my-group').style.display='none';

    document.getElementById('theForm').addEventListener('submit', submitForm);
}

function submitForm(event) {
    event.preventDefault();

    const form = document.getElementById('theForm');

    // 1. Create a FormData object from the form
    const formData = new FormData(form);

    // 2. Pass that directly into URLSearchParams to get the encoded string
    const queryString = new URLSearchParams(formData).toString();

    //this submits the querysting to the /finder/finder.php - in an iframe!)
    navigateTo('/app/results', {param:queryString});

    return false;
}

//actully seems like best way to request navigation is via an event!
function navigateTo(path, options) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;

    const event = new CustomEvent('request-navigation', {
        detail: { path, options },
        bubbles: true
    });
    target.dispatchEvent(event);
}
