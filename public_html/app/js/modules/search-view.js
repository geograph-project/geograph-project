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
	background-color:var(--input-bg);
	color:var(--content-text);
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

:root {
    --results-bg: #eeeeee;
    --results-item-bg: #ffffff;
    --results-hover: #f8f8f8;
    --results-highlight: #000000;
    --results-underline: #eeeeee;
    --results-link: #0056b3;
    --results-subtext: #777777;
    --results-shadow: rgba(0, 0, 0, 0.1);
    --action-color: #0000ff;
--results-li-hover: #dddddd;
--results-highlight-bg: #f9f9e3;
}
body.dark-mode {
    --results-bg: #252525;        /* Slightly lighter than app-bg */
    --results-item-bg: #1e1e1e;   /* Matches card-bg */
    --results-hover: #333333;
    --results-highlight: #ffffff;
    --results-underline: #444444; /* Darker grey underline */
    --results-link: #4da3ff;      /* Brighter blue for dark contrast */
    --results-subtext: #aaaaaa;   /* Lighter grey for readability */
    --results-shadow: rgba(0, 0, 0, 0.5);
    --action-color: #58a6ff;
--results-li-hover: #2a2a20;
--results-highlight-bg: #3d3b26;
}

    .results-list { display:none; position: absolute; width: 100%; z-index: 1000; background: var(--results-bg); border: 1px solid var(--border-color); top:100%;
         list-style: none; padding: 0; margin: 0; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

    .results-list li { padding: 4px; cursor: pointer; display: flex; flex-direction: column; }
    .results-list li:hover { background: #f8f8f8; }

    .results-list .main-info { display: flex; flex-direction: row; justify-content: space-between; align-items: center;
	background-color: var(--results-item-bg); padding: 6px 10px; overflow: hidden; }

    .results-list .label { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--content-text); }
    .results-list b { color: var(--results-highlight); font-weight: 500; text-decoration: underline;  text-decoration-color: var(--results-underline); }
    .results-list .gridref { font-family: monospace; color: var(--results-link); padding-left: 10px; flex-shrink: 0; font-weight: 600; }
    .results-list .locality { color: var(--results-subtext); display: block; margin: 4px 0 2px 14px; }

.results-list li[data-action=map] { color: var(--action-color) !important; } 

	.results-list li:hover {
	    background: var(--results-li-hover);
	}
	.results-list li:hover .main-info {
	    background: var(--results-highlight-bg); /* Your new warm highlight */
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

    // Check if we have a saved query string
    const form = document.getElementById('theForm');
    if (queryString && form) {
        const params = new URLSearchParams(queryString);

        // Iterate through all entries in the query string
        for (const [key, value] of params.entries()) {
            const input = form.elements[key];
            if (!input) continue;

            if (input.type === 'checkbox') {
                // Checkboxes are "on" if present in the string
                input.checked = true;
            } else {
                // Standard text, selects, etc.
                input.value = value;
            }
        }
    }

}

var queryString; //save it locally

function submitForm(event) {
    event.preventDefault();

    const form = document.getElementById('theForm');

    // 1. Create a FormData object from the form
    const formData = new FormData(form);

    // 2. Pass that directly into URLSearchParams to get the encoded string
    queryString = new URLSearchParams(formData).toString();

    // 3. Check the "Soft" connection gate
    if (navigator.onLine === false) {
        alert("You appear to be offline. Please wait for a connection to search.");
        return false;
    }

    //this submits the querysting to the /finder/finder.php - in an iframe!)
    navigateTo('/app/results', {param:queryString});

    return false;
}

