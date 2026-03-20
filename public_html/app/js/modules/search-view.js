import { escapeHTML, navigateTo } from '/app/js/utils.js';
import { setupPlaceAutocomplete, handleGeolocation } from '/js/location-selector.module.js';

export function render() {
    return `

<form method="get" name=theForm id=theForm action="/finder/finder.php" class="search-form">
    <div class="form-group">
        <label for="q">Search for:</label>
        <input type="search" name="q" id="q" placeholder="enter keywords">
    </div>

    <div class="form-group">
        <label for="loc">Near:</label>
        <div class="location-input" style="position:relative">
            <input type="search" name="loc" id="loc" placeholder="placename, lat/long, etc.">
            <ul id="results-list" class="results-list"></ul>
        </div>
    </div>
    <div class="form-group">
        <label for=""></label>
        <a href="#" id="get-geo-btn">Use My Location</a>
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

    .results-list { display:none; position: absolute; width: 100%; z-index: 1000; background: #eee; border: 1px solid #ccc; top:100%;
         list-style: none; padding: 0; margin: 0; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

    .results-list li { padding: 4px; cursor: pointer; display: flex; flex-direction: column; }
    .results-list li:hover { background: #f8f8f8; }

    .results-list .main-info { display: flex; flex-direction: row; justify-content: space-between; align-items: center;
	background-color: white; padding: 6px 10px; overflow: hidden; }

    .results-list .label { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #333; }
    .results-list b { color: #000; font-weight: 500; text-decoration: underline;  text-decoration-color: #eee; }
    .results-list .gridref { font-family: monospace; color: #0056b3; padding-left: 10px; flex-shrink: 0; font-weight: 600; }
    .results-list .locality { color: #777; display: block; margin: 4px 0 2px 14px; }

	.results-list li:hover {
	    background: #ddd; /* Darken the "border" on hover */
	}
	.results-list li:hover .main-info {
	    background: #f9f9e3; /* Your new warm highlight */
	}

</style>

    `;
}

export function onMount() {
    console.log('Search View Mounted');

    // Attach the event listener to the link
    const geoBtn = document.getElementById('get-geo-btn');
    if (geoBtn) {
        geoBtn.addEventListener('click', (e) => {
            e.preventDefault();
            handleGeolocation('loc'); //element that gets the result
        });
    }
    if (window.GEOGRAPH_USER_PREFERENCES && window.GEOGRAPH_USER_PREFERENCES['user_id'])
        document.getElementById('my-images').value = window.GEOGRAPH_USER_PREFERENCES['user_id']+" Myself";
    else
        document.getElementById('my-group').style.display='none';

    document.getElementById('theForm').addEventListener('submit', submitForm);

    // Initialize Autocomplete //no auto-submit/callback
    setupPlaceAutocomplete('loc', { maplink: true });
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

