{assign var="page_title" value="Geograph Quick Search"}
{include file="_std_begin.tpl"}

{literal}
<style type="text/css">
.hidden {
    display: none;
}
.finder-container {
    position: relative;
    min-height: 800px;
}
.finder-form {
    background-color: #ddd;
    padding: 10px;
}
.form-column {
    float: left;
    width: 300px;
}
.form-clear {
    clear: both;
    padding-top:6px;
    --text-align: center;
}
.display-options {
    text-align: right;
}
.results-count {
    float:left;
    --padding: 4px;
}
.results-box {
    border: 5px solid #ddd;
	border-radius:10px;
    padding: 5px;
}
.filter-box {
    background-color: #eee;
    padding: 10px;
    margin-top: 10px;
}

.results-box p {
    max-width:60em;
}
/* ----------------------- */

.display-large {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(213px, 1fr));
    gap: 2px;
}
.display-large div {
    text-align: center;
    min-height: 160px;
}
.display-large div a:first-child {
    display: block;
}
/* ----------------------- */

.display-small {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 2px;
}
.display-small div {
    text-align: center;
    min-height: 120px;
}
.display-small div a:first-child {
    display: block;
}

/* ----------------------- */

.display-details .details-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 10px;
    padding: 5px;
    border-bottom: 1px solid #ccc;
}
.display-details .details-item-thumb {
    width: 213px;
    text-align: center;
}
.display-details .details-item-info {
    text-align: left;
}

/* ----------------------- */

.display-river .river-item {
    display: grid;
    grid-template-columns: 640px 1fr;
    gap: 10px;
    padding: 5px;
    border-bottom: 1px solid #ccc;
}
.display-river .river-item-thumb {
    text-align: right;
}
.display-river .river-item-info {
    text-align: left;
    font-size:1.2em;
    line-height:1.5em;
}

/* ----------------------- */

.distance-header {
    padding: 2px;
    background-color: #eee;
    margin-top: 5px;
    margin-bottom: 5px;
    width: 100%;
}
.display-small div.distance-header, .display-large div.distance-header {
    grid-column: 1 / -1;
    min-height:1em;
}

        #results.leaflet-container {
                height:calc( 100dvh - 300px );
        }

</style>

<div class="finder-container">
	<div class="tabHolder">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">Original Search</a>
		<a class="tab nowrap" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}">Image Browser</a>
		<a class="tab nowrap" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}&amp;display=map">Browser Map</a>
		<a class="tab nowrap" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}&amp;display=group&amp;group=decade&amp;n=4&amp;gorder=alpha%20desc">Grouped Results</a>
		<a class="tab nowrap" data-template="/content/?q={q}">Collections</a>
{/literal}
		{if $enable_forums}
			<a class="tab nowrap" data-template="/finder/discussions.php?q={literal}{q}{/literal}">Discussions</a>
		{/if}
	</div>
	<form id="finder-form" method="get" class="finder-form">
		<div class="form-column">
			Search For: <input type=search name=q size="36" placeholder="Enter Search Query"> <br>
			<label><input type=radio name=type value="keywords" checked>Keywords</label> /
			<label><input type=radio name=type value="similarity">&quot;Looks Like&quot;</label> Mode <a href="#" onclick="restoreInitialHelp();return false;">?</a>
		</div>
		<div class="form-column">
			And/or Near:   &nbsp; (<a href="#" onclick="getLocation(performSearch);return false;">My Location</a>)
			<input type=search id="loc" name="loc" size="36" placeholder="Enter location"><br>
			<label for="distance">Within Distance:</label> <input type="number" id="distance" name="distance" value="2000" style="width:80px;text-align:right" step=100 min=100 max="100000">m

			<div id="location-disambiguation"></div>
		</div>

		<div id="date-filter-box" class="form-column hidden">
			<label for="date_start">Start Date:</label>
			<input type="date" id="date_start" name="date_start" min="1800-01-01"><br>
			<label for="date_end">End Date:</label>
			<input type="date" id="date_end" name="date_end" min="1800-01-01">
			<button type="button" id="clear-dates-btn">Clear Dates</button>
		</div>
		<div id="contributor-filter-box" class="form-column hidden">
			<label for="contributor">Contributor:</label>
			<input type="search" id="contributor" name="contributor" placeholder="Enter contributor name">
		</div>

		<div class="form-clear">
			<button type="submit">Update</button>
			<a href="#" id="add-date-filter">Add Date Filter</a> <a href="#" id="add-contributor-filter">Add Contributor Filter</a>
		</div>
		<input type="hidden" id="display-mode" name="display" value="small">
	</form>
	<div id="correction-prompt"></div>
	<br>
{literal}
	<div id="results-count" class="results-count"></div>
	<div id="display-tabs" class="tabHolder display-options">
		Display:
		<a href="#" class="tab tabSelected nowrap" data-display="small">Small Thumbs</a>
		<a href="#" class="tab nowrap" data-display="large">Large Thumbs</a>
		<a href="#" class="tab nowrap" data-display="details">Details</a>
		<a href="#" class="tab nowrap" data-display="river">GeoRiver</a>
		<a href="#" class="tab nowrap" data-display="map">Map</a>
		<a class="nowrap" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">more...</a>
	</div>
	<div id="results" class="results-box">
		<p>Just click Update above to see recent images.</p>

		<h3>Keywords Mode</h3>

		<p>This traditional search finds images based on the words you type, using their descriptions and other 
		metadata. While it's great for finding specific text, be aware of possible false matches, for example, an image's 
		description might mention a place it doesn't actually show. For more advanced search techniques, you can explore
		<a href="https://www.geograph.org.uk/article/Keyword-Searching-in-the-Browser">the full syntax.</a>

		<h3>Looks Like Mode</h3>

		<p>This AI-powered search finds images that are visually similar to what you're looking for, bypassing text 
		descriptions. The system works best with general visual concepts, like "Gothic cathedral" or "castle at 
		sunset", rather than specific names or landmarks. The quality of results may decline as you scroll, and the 
		model might not recognize very specific places or species. However, its strength lies in combining visual 
		ideas, leading to unique and creative results. To search for a specific location, try using a general 
		description and then refining your search with the "Near to" option.

		<p>More Details: <a href="https://www.geograph.org.uk/article/Using-Looks-Like-Search">Using &quot;Looks Like&quot; Search</a>

	</div>

	<div id="more-results-prompt" class="hidden" style="text-align: center; padding: 20px;">
		<span id="results-count2"></span>
		Continue in: 
		<a href="#" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">Original Search</a>
		or
		<a href="#" data-template="/browser/redirect.php?q={q}&loc={loc}&date_start={date_start}&date_end={date_end}&contributor={contributor}&distance={distance}">Image Browser</a>
	</div>
{/literal}
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>

<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>
<script src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/js/location-selector.js"|revision}"></script>
<script type="text/javascript" src="{"/js/contributor-selector.js"|revision}"></script>
<script type="text/javascript" src="/js/geograph-api-libs.js?"></script>
{literal}
<script>

let map = null;
let layerGroup = null;
let initialHelp = null;

function restoreInitialHelp() {
	const resultDiv = document.getElementById("results")

                if (map) { //first need to destroy the map!
                        map.remove();
                        map = null;
                }

        resultDiv.className = 'results-box'; //remove all!
	resultDiv.innerHTML = initialHelp.replace(/see recent images/,'your results');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('finder-form').addEventListener('submit', function(event) {
        event.preventDefault();
        performSearch();
    });

    document.getElementById('add-date-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('date-filter-box').classList.toggle('hidden');
	document.getElementById('add-date-filter').classList.toggle('hidden');
    });

    document.getElementById('add-contributor-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('contributor-filter-box').classList.toggle('hidden');
	document.getElementById('add-contributor-filter').classList.toggle('hidden');
    });

    initialHelp = document.getElementById("results").innerHTML;

    handleUrlQuery();
    //only do this on page load for now!
    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    if (query && !loc) {
        lookForLocationMatches(query,'q');
    }

    document.getElementById('display-tabs').addEventListener('click', function(event) {
        if (event.target.dataset.display) {
            event.preventDefault();

            // Update hidden input
            document.getElementById('display-mode').value = event.target.dataset.display;

            // Update selected class
            this.querySelectorAll('.tabSelected').forEach(function(tab) {
                tab.classList.remove('tabSelected');
                tab.classList.add('tab');
            });
            event.target.classList.add('tabSelected');
            event.target.classList.remove('tab');

            // Re-run search
            performSearch();
        }
    });

    document.getElementById('clear-dates-btn').addEventListener('click', function(event) {
        document.getElementById('date_start').value = '';
        document.getElementById('date_end').value = '';
        performSearch();
    });

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date_start').max = today;
    document.getElementById('date_end').max = today;
});

window.addEventListener('popstate', handleUrlQuery);

function renderFinderResults(url, divId, countDivId) {
    const divElement = document.getElementById(divId);
    const countDivElement = document.getElementById(countDivId);
    const moreResultsPrompt = document.getElementById('more-results-prompt');
    const display = document.getElementById('display-mode').value;

    // Switch display class
    divElement.classList.remove('display-large', 'display-small', 'display-details', 'display-river'); // Add other classes here as they are created
    divElement.classList.add('display-' + display);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.rows) {
                // Clear previous results
                divElement.innerHTML = '';
                let lastDistanceGroup = null;

                data.rows.forEach(row => {
                    // Check for distance headers
                    if (typeof row.geodist !== 'undefined') {
                        const currentGroup = getDistanceGroup(row.geodist);
                        if (lastDistanceGroup !== currentGroup) {
                            const headerDiv = document.createElement('div');
                            headerDiv.className = 'distance-header';
                            headerDiv.innerHTML = `Within <b>${currentGroup}</b> km`;
                            divElement.appendChild(headerDiv);
                            lastDistanceGroup = currentGroup;
                        }
                    }

                    let htmlContent = '';
                    let newDiv;
                    switch (display) {
                        case 'river':
                            newDiv = document.createElement('div');
                            newDiv.className = 'river-item';
                            htmlContent = `
                                <div class="river-item-thumb">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                        <img src="${getGeographUrl(row.id, row.hash, 'full')}" alt="${escapeHtml(row.title)}" width="${row.width}" height="${row.height}" loading="lazy" crossorigin onerror="retryCross(this)">
                                    </a>
                                </div>
                                <div class="river-item-info">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank"><strong>${escapeHtml(row.title)}</strong></a><br>
                                    by <a href="/profile/${row.user_id}">${escapeHtml(row.realname)}</a><br>
				    Taken: ${space_date(row.takenday)}<br>
                                    Grid Reference: ${row.grid_reference}<br>
                                    ${row.geodist ? `<br>Distance: ${(row.geodist / 1000).toFixed(1)} km` : ''}
                                </div>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                        case 'details':
                            newDiv = document.createElement('div');
                            newDiv.className = 'details-item';
                            htmlContent = `
                                <div class="details-item-thumb">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                        <img src="${getGeographUrl(row.id, row.hash, 'med')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                    </a>
                                </div>
                                <div class="details-item-info">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank"><strong>${escapeHtml(row.title)}</strong></a><br>
                                    by <a href="/profile/${row.user_id}">${escapeHtml(row.realname)}</a><br>
				    Taken: ${space_date(row.takenday)}<br>
                                    Grid Reference: ${row.grid_reference}<br>
                                    ${row.geodist ? `<br>Distance: ${(row.geodist / 1000).toFixed(1)} km` : ''}
                                </div>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                        case 'small':
                            newDiv = document.createElement('div');
                            htmlContent = `
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank" title="${escapeHtml(row.title)} by ${escapeHtml(row.realname)}">
                                    <img src="${getGeographUrl(row.id, row.hash, 'small')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                </a>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                        case 'large':
                        default:
                            newDiv = document.createElement('div');
                            htmlContent = `
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank" title="${escapeHtml(row.title)} by ${escapeHtml(row.realname)}">
                                    <img src="${getGeographUrl(row.id, row.hash, 'med')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                </a>
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">${escapeHtml(row.title)}</a>
                                <span class="nowrap">by ${escapeHtml(row.realname)}</span>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                    }
                    divElement.appendChild(newDiv);
                });

                if (data.meta && data.meta.total_found && countDivElement) {
                    countDivElement.classList.remove('hidden');
                    countDivElement.textContent = `Showing ${data.rows.length} of ${data.meta.total_found} results.`;
                }

                // Handle 'More Results' prompt
                if (data.meta && data.meta.total_found > data.rows.length) {
		    document.getElementById('results-count2').textContent = `Showing ${data.rows.length} of ${data.meta.total_found} results.`;
                    moreResultsPrompt.classList.remove('hidden');
                } else {
                    moreResultsPrompt.classList.add('hidden');
                }
            } else {
                divElement.innerHTML = 'No Results';
                countDivElement.classList.add('hidden');
                moreResultsPrompt.classList.add('hidden');                
            }
        })
        .catch(error => console.error('Error fetching data:', error));
}

function searchAndRender() {
    let query = document.querySelector('input[name="q"]').value;
    let query_len = query.length; //grab before we modify below!
    const loc = document.querySelector('input[name="loc"]').value;
    let distance = parseInt(document.getElementById('distance').value,10) || 2000;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const display = document.getElementById('display-mode').value;

    let base = "https://www.geograph.org.uk/api-facetql.php";
    const data = {
        long: 1,
        select: "id,user_id,realname,grid_reference,title,hash,takenday,width,height",
        limit: 30
    };

    if (query && query.match(/^\d+(,\d+)*$/)) {
	//special handler for a list of ids!
        data['where'] = "id in ("+query+")";

        //todo, certainly doesnt make sense to filter by query, but might want to keep other filters?

        const url = base + '?' + objectToUrlParams(data);
        renderFinderResults(url, 'results', 'results-count');
        return;
    }

    if (contributor) {
	if (m = contributor.match(/^(\d+)\s/)) {
	     query += " user"+m[1];
        }
    }

    if (type == 'similarity' && query_len) { //no point doing similarity, if no query
        base = "https://www.geograph.org.uk/api-facetql-vector.php"; //for now, requires a different API endpoint
	data['label'] = query;
    } else {
	if (query) {
	    data['match'] = getTextQuery(query);
	}
    }

    let wgs84;
    if (loc) {
        if (type == 'keywords' && distance > 50000)
            distance = 50000;
		
	if (m = loc.match(/^([A-Z]{1,2}\d+)\s/)) {
		wgs84 = gridref2wgs(m[1]); //should automaticalyl 'fudge' 4fig GRs
                data.geo=parseFloat(wgs84.latitude).toFixed(6)+","+parseFloat(wgs84.longitude).toFixed(6)+","+distance;

                if (type == 'keywords')
			data.order = 'geodist asc';

	} else {
		//this is where gets tricky.
		//here we should call places.json.php ourself, and extract a list of possible names. 
		// and then render a dropdown (below the search box!) that lets them pick from options!
		lookForLocationMatches(loc, 'loc');
	}
    }

    if (date_start) {
        if (date_end) {
            data['filterrange[takendays]'] = "to_days("+date_start+"),to_days("+date_end+")";
        } else {
	    const futureDate = getFutureDateString();
            data['filterrange[takendays]'] = "to_days("+date_start+"),to_days("+futureDate+")";
        }
    } else if (date_end) {
        data['filterrange[takendays]'] = "to_days(1800-01-01),to_days("+date_end+")";
    }

    const correction_prompt =  document.getElementById('correction-prompt');
    if (!query_len && !loc && !date_end) {
        correction_prompt.textContent = "Defaulting to showing recent submissions...";
        data.order = 'id desc';
    } else {
        if (correction_prompt.textContent == "Defaulting to showing recent submissions...")
	    correction_prompt.textContent = '';
    }

    ////////////////////////////////////////////////////////

    if (display == 'map') {
        data.select += ",wgs84_lat,wgs84_long";
        //todo if (!data.order) data.order = 'sequence asc'; maybe??
	
                if (map && layerGroup) {
                    layerGroup.clearLayers();
                } else {
                        $('#results').empty().get(0).className = ''; //remove all classes

                    // Set the map's initial view to the UK
                    map = L.map('results').setView([54.0, -2.0], 6);

                    // Add a base map tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    layerGroup = L.layerGroup().addTo(map);
                }
                if (wgs84 && wgs84.latitude) {
                        L.circleMarker([wgs84.latitude, wgs84.longitude], {radius:6}).addTo(layerGroup);
			if (type == 'similarity' && query_len) {
				createRectangleFromRadius([wgs84.latitude, wgs84.longitude], distance, {stroke:0, fillOpacity:0.1}).addTo(layerGroup);
			} else {
				L.circle([wgs84.latitude, wgs84.longitude], {radius: distance, stroke:0, fillOpacity:0.1}).addTo(layerGroup);
			}
                }
                mapAPIResults(base+'?'+$.param(data), layerGroup, true, 'results-count'); //pass the layergroup, so markers are added to the group!

            if (type == 'keywords')
		    correction_prompt.textContent = 'This is only a basic map. Use the [Browser Map] link above to explore the results in more detail.';

	return;
    }


                if (map) { //first need to destroy the map!
                        map.remove();
                        map = null;
                        document.getElementById('results').className = ''; //remove all!
                }

    if (correction_prompt.textContent == 'This is only a basic map. Use the [Browser Map] link above to explore the results in more detail.')
	correction_prompt.textContent = '';

    $('#results').addClass('results-box');

    ////////////////////////////////////////////////////////

    const url = base + '?' + objectToUrlParams(data);
    renderFinderResults(url, 'results', 'results-count');
}

//callback for location-selector.js
function jumpLocation(form) {
    performSearch();
}
function jumpContributor(form) {
    performSearch();
}

function performSearch() {
    searchAndRender();

    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const distance = document.querySelector('input[name="distance"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const display = document.getElementById('display-mode').value;
    const params = new URLSearchParams();
    if (query) {
        params.append('q', query);
    }
    if (loc) {
        params.append('loc', loc);
        params.append('distance', distance);
    }
    if (type) {
        params.append('type', type);
    }
    if (date_start) {
        params.append('date_start', date_start);
    }
    if (date_end) {
        params.append('date_end', date_end);
    }
    if (contributor) {
        params.append('contributor', contributor);
    }
    if (display) {
        params.append('display', display);
    }

    const newUrl = window.location.pathname + '?' + params.toString();
    history.pushState({query: query, loc: loc, type: type, date_start: date_start, date_end: date_end, contributor: contributor, display: display}, '', newUrl);

    updateTabLinks();
}

function handleUrlQuery() {
    const params = new URLSearchParams(window.location.search);
    const query = params.get('q');
    const loc = params.get('loc');
    const distance = params.get('distance');
    const type = params.get('type');
    const date_start = params.get('date_start');
    const date_end = params.get('date_end');
    const contributor = params.get('contributor');
    const display = params.get('display') || 'small';

    document.querySelector('input[name="q"]').value = query ?? '';
    document.querySelector('input[name="loc"]').value = loc ?? '';
    document.querySelector('input[name="distance"]').value = distance ?? 2000;
    document.querySelector('input[name="date_start"]').value = date_start ?? '';
    document.querySelector('input[name="date_end"]').value = date_end ?? '';
    document.querySelector('input[name="contributor"]').value = contributor ?? '';
    document.getElementById('display-mode').value = display;

    if (type) {
        document.querySelector(`input[name="type"][value="${type}"]`).checked = true;
    }

    document.querySelectorAll('#display-tabs .tab').forEach(function(tab) {
        tab.classList.remove('tabSelected');
        if (tab.dataset.display === display) {
            tab.classList.add('tabSelected');
            tab.classList.remove('tab');
        }
    });

    if (date_start || date_end) {
        document.getElementById('date-filter-box').classList.remove('hidden');
	document.getElementById('add-date-filter').classList.add('hidden');
    }

    if (contributor) {
        document.getElementById('contributor-filter-box').classList.remove('hidden');
	document.getElementById('add-contributor-filter').classList.add('hidden');
    }

    if (query || loc || date_start || date_end || contributor) {
        searchAndRender();
    }

    updateTabLinks();
}

function updateTabLinks() {
    const params = {
        q: document.querySelector('input[name="q"]').value,
        loc: document.querySelector('input[name="loc"]').value,
        distance: document.querySelector('input[name="distance"]').value,
        type: document.querySelector('input[name="type"]:checked').value,
        date_start: document.querySelector('input[name="date_start"]').value,
        date_end: document.querySelector('input[name="date_end"]').value,
        contributor: document.querySelector('input[name="contributor"]').value
    };

    document.querySelectorAll('a[data-template]').forEach(function(tab) {
        let url = tab.dataset.template;
        for (const key in params) {
            url = url.replace(`{${key}}`, encodeURIComponent(params[key]));
        }
        tab.href = url;
    });
}

function getFutureDateString() {
    const futureTimestampInSeconds = (Math.ceil(new Date().getTime() / 3600000) * 3600) + 604800;
    const futureDate = new Date(futureTimestampInSeconds * 1000);
    const year = futureDate.getFullYear();
    const month = String(futureDate.getMonth() + 1).padStart(2, '0');
    const day = String(futureDate.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getDistanceGroup(distance) {
    if (distance === null || typeof distance === 'undefined') {
        return null;
    }
    let d2;
    let len = 4;
    const loc = document.querySelector('input[name="loc"]').value;
    if (loc && (m = loc.match(/^[A-Z]{1,2}(\d+)\s/))) {
        len = m[1].length;
    }

    if (distance < 800 && len > 4) {
        if (distance < 10 && len > 6) {
            d2 = 0.01;
        } else if (distance < 100) {
            d2 = 0.1;
        } else {
            d2 = (Math.floor(distance / 300) / 3) + 0.3;
        }
    } else {
        d2 = Math.floor(distance / 1000) + 1;
    }
    return d2.toFixed(2);
}

function lookForLocationMatches(loc,originalElement) {
    const container = document.getElementById('location-disambiguation');
    const correction_prompt =  document.getElementById('correction-prompt');
    container.innerHTML = 'Searching for location...';

    window.serveCallback = function(data) {
        container.innerHTML = ''; // Clear 'Searching...'
        if (data && data.total_found > 1) {
            if (originalElement && originalElement == 'q') {
		correction_prompt.textContent = "There are a number of places matching your query. Below are combined keyword results. Use the dropdown above to pick a specific place.";
		//todo, inlcude a link to finder/groups.php?
            }

            const label = document.createElement('label');
            label.textContent = 'Did you mean: ';
            container.appendChild(label);

            const select = document.createElement('select');
            
            const defaultOption = document.createElement('option');
            defaultOption.textContent = 'Choose Location';
            defaultOption.value = '';
            select.appendChild(defaultOption);

            data.items.forEach(function(item) {
                const option = document.createElement('option');
                option.value = `${item.gr} ${item.name}`;
                option.textContent = `${item.name.replace(new RegExp('/' + item.gr, 'g'), ' - ' + item.gr)}${item.localities ? ', ' + item.localities : ''}`;
                select.appendChild(option);
            });

            select.addEventListener('change', function(event) {
                if (event.target.value) {
                    document.getElementById('loc').value = event.target.value;
                    if (originalElement && originalElement == 'q')
			document.querySelector('input[name="q"]').value = '';
                    performSearch();
                    container.innerHTML = ''; // Clear the dropdown
                    correction_prompt.innerHTML = '';
                }
            });

            container.appendChild(select);

        } else if (data && data.total_found == 1) {
            // If only one result, just use it directly
            document.getElementById('loc').value = `${data.items[0].gr} ${data.items[0].name}`;
            if (originalElement && originalElement == 'q')
		document.querySelector('input[name="q"]').value = '';
            performSearch();
        } else {
            if (originalElement && originalElement == 'loc')
                container.innerHTML = 'No locations found.';
        }
    };

    const script = document.createElement('script');
    script.src = `/finder/places.json.php?q=${encodeURIComponent(loc)}&new=1&callback=serveCallback`;
    
    script.onload = () => document.body.removeChild(script);
    script.onerror = () => {
        container.innerHTML = 'Error searching for location.';
        document.body.removeChild(script);
    };
    document.body.appendChild(script);
}


    /**
     * Creates and returns a Leaflet rectangle centered on a point with a given radius.
     * The height and width of the rectangle will be equal to the diameter (radius * 2).
     * @param {L.LatLng} center The center point for the rectangle.
     * @param {number} radius The radius in meters.
     * @returns {L.Rectangle} The Leaflet rectangle object.
     */
    function createRectangleFromRadius(center, radius, options) {
        // Step 1: Create a temporary circle at the center point with the given radius.
        // We don't need to add this circle to the map, we just need its bounds.
        var tempCircle = L.circle(center, { radius: radius }).addTo(map);

        // Step 2: Get the bounding box (LatLngBounds) of the temporary circle.
        // This gives us the southwest and northeast corners of the box.
        var bounds = tempCircle.getBounds();

        // Step 3: Create a rectangle using the calculated bounds.
        var rectangle = L.rectangle(bounds, options);
        
	tempCircle.removeFrom(map);

        // Return the final rectangle object.
        return rectangle;
    }

</script>
{/literal}

{include file="_std_end.tpl"}
