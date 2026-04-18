{literal}
<style>
    .results-list { display:none; position: absolute; width: 100%; z-index: 1000; background: white; border: 1px solid #ccc;
         list-style: none; padding: 0; margin: 0; max-height: 50vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .results-list li { padding: 4px; border-bottom: 1px solid #eee; cursor: pointer; color: #333; }
    .results-list li:hover { background: #f8f8f8; }
    .results-list b { color: #000; font-weight:bold; } // weigth:500 doesnt work in Georgia
    .results-list .gridref { font-family: monospace }
    .results-list .locality { color: #666; display: block; margin-left:10px }

@media (any-pointer: coarse) {
    .results-list li { padding: 10px; }
}

@media screen and (min-width: 640px) {
	.results-list div.main-info { display: inline-block; min-width: 220px }
	.results-list .locality { display: inline-block; }
}

</style>

<form method="get" action="/finder/finder.php">
	<input type="search" name="loc" id="loc" placeholder="Search for a place..."><button type=submit style=font-weight:bold>View Images of Place</button><br>
	<div class="autocomplete-container">
	    <ul id="results-list" class="results-list"></ul>
	</div>
	<button type="button" id="get-loc-btn">Find my Location</button>
	<button type="button" id="open-map-btn">Search Places on Map</button>
</form>

<script type="module">
    import { handleGeolocation, setupPlaceAutocomplete, openPlaceSearch } from '/js/location-selector.module.js?v=3';

    const loc = document.getElementById('loc');

    // 1. Initialize Autocomplete
    setupPlaceAutocomplete('loc', {
        maplink: true,
        onSelect: (data) => {
            loc.value = data.value; //`${item.gr} ${item.name}`
            loc.form.submit();
        }
    });

    // 2. Bind the "Find my Location" button
    document.getElementById('get-loc-btn').addEventListener('click', (e) => {
        e.preventDefault();
        handleGeolocation('loc');
    });

    // 3. Bind the "Search Places On Map" button
    document.getElementById('open-map-btn').addEventListener('click', (e) => {
        e.preventDefault();

        openPlaceSearch(loc.value, function(name, gr, lat, lng) {
            loc.value = `${gr} ${name}`;
		    loc.form.submit();
	    });
    });
</script>
{/literal}

