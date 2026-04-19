{assign var="page_title" value="Geograph Quick Search"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}

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
}
.form-column {
    float: left;
    width: 300px;
    margin:3px;
    background-color: #eee;
    border-radius:8px;
    padding: 8px;
}
.form-clear {
    clear: both;
    padding-top:6px;
    --text-align: center;
}

.filter-pill {
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 20px; /* Makes it a pill */
    padding: 4px 12px;
    font-size: 0.85em;
    cursor: pointer;
}
.filter-pill:hover {
    background: #e0e0e0;
}

.finder-form label:has(input:checked) {
    font-weight: bold;
}

.display-options {
    text-align: right;
}

/* 1. The Parent Wrapper */
.search-controls {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;       /* Allows wrapping on small screens */
    align-items: stretch;  /* Keeps everything the same height */
    gap: 15px;             /* Adds space between count and tabs */
    justify-content: space-between; /* Pushes count left and tabs right */
}

/* 2. The Results Count */
.results-count {
    display: flex;
    align-items: center;   /* Vertically centers the "Showing X..." text */
    flex: 1 0 200px;       /* Grows to fill space, but won't shrink below 200px */
    gap: 0.3ch;
}

/* 3. The Tabs Container */
.display-options {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 4px;              /* Clean way to space tabs instead of margins */
}

.search-controls .tabHolder a, .display-options .tabHolder span {
    white-space:normal;
}

/* 4. The Individual Tabs (Your existing classes) */
.display-options .tab, .display-options .tabSelected {
    display: flex;
    align-items: center; 
    justify-content: center;
    text-align: center;
}

@media (max-width: 480px) {
    .display-options {
        justify-content: flex-start; /* Align to the left on mobile */
    }
    .display-options .tab-label {
        display:none; /* hide the word 'display:' which takes up too much space */
    }

    .tab, .tabSelected {
        flex: 1 1 40%; /* Let each tab take up roughly half the width */
        font-size: 0.9em; /* Slightly smaller text saves a lot of horizontal room */
        padding: 8px 4px; /* Vertical padding is fine, keep horizontal slim */
    }
}

.btn-primary {
    font-weight: bold;
    font-size: 1.1em;

}
@media (any-pointer: coarse) {
    .btn-primary {
        background-color: #007AFF;
        color: white;
        min-width: 100px;
        border-radius: 10px;
        border: 0;
        padding: 5px;
    }
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
#date-filter-box {
    line-height:1.9em;
}
/* ----------------------- */

.display-large {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(213px, calc(50% - 9px)), 1fr));
    gap: 18px;
    background-color: #eeeeee38;
}
.display-large div {
    text-align: center;
    min-height: 160px;
    background-color: white;
    border-radius:10px;
}
.display-large div img {
    max-width:100%;
    border-radius:5px;
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
    max-width:50vw;
    text-align: center;
}
.display-details .details-item-thumb img{
    max-width:50vw;
}
.display-details .details-item-info {
    text-align: left;
	line-height:1.3em;
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


.display-river .river-item-info span, .display-details .details-item-info span {
    color:gray;
}
.display-river .river-item-info sup, .display-details .details-item-info sup {
    color:gray;
    font-size:0.6em;
}
.display-river .river-item-info tt, .display-details .details-item-info tt {
    font-size: 1rem;
    font-size-adjust: 0.66;
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
		min-height: 300px;
        }

@media only screen and (max-width: 960px) {
	a[data-display="river"] {
		--display:none;
	}
	.display-options a:last-child {
		display:none;
	}

    .display-river .river-item {
        grid-template-columns: 1fr;
    }
    .display-river .river-item-thumb {
	text-align:center;
    }
    .display-river .river-item-thumb img {
	border-radius:4px;
	box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
        max-width:100%;
	height: auto; /* just to make sure */
    }
    .display-river .river-item-info {
	text-align:center;
        margin-bottom:20px;
    }
}

@media only screen and (max-width: 612px) {
	.content2 {
		padding:0px;
		padding-top:6px;
	}
	.results-box {
		border:0;
		border-top:2px solid #ddd;
		border-bottom:2px solid #ddd;
		padding:1px;
		padding-top:4px;
	}
	.finder-form input[type=search] {
		max-width:100%;
        border-radius:6px;
        padding:4px;
        border:1px solid gray;
	}
    .finder-form input[type=number] {
        border-radius:6px;
        padding:4px;
        border:0;
    }
    .finder-form input[type=date] {
        border-radius:6px;
        padding:4px;
        border:0;
    }
    .display-details .details-item-info tt {
        font-size: 1rem;
        font-size-adjust: 0.5;
    }


	.finder-form select {
		max-width:100%;
        padding:4px;
	}
	.finder-form .form-column {
		float:none;
		width:inherit;
		margin-bottom:5px;
	}
	#correction-prompt {
		padding:0 4px;
	}

    .scroll-container {
      /* This is the container for your tabs */
      width: 100%;
      white-space: nowrap;
      overflow-x: auto;
      position: relative; /* Essential for positioning the pseudo-elements */
      touch-action:inherit;
      margin-top:-2px;
    }


}

</style>

<div class="finder-container">
	<div class="tabHolder scroll-container full-version">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap keywords-only" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">Original Search</a>
		<a class="tab nowrap keywords-only" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}">Image Browser</a>
		<a class="tab nowrap keywords-only" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}&amp;display=map">Browser Map</a>
		<a class="tab nowrap keywords-only" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}&amp;display=group&amp;group=decade&amp;n=4&amp;gorder=alpha%20desc">Grouped Results</a>
		<a class="tab nowrap keywords-only" data-template="/content/?q={q}">Collections</a>
{/literal}
		{if $enable_forums}
			<a class="tab nowrap keywords-only" data-template="/finder/discussions.php?q={literal}{q}{/literal}">Discussions</a>
		{/if}
	</div>
	<form id="finder-form" method="get" class="finder-form">
		<div class="form-column drop-container">
			<b>Search For</b>: <input type=search name=q size="36" placeholder="Enter Search Query"> <br>
			<label title="This traditional search finds images based on the words you enter, using their descriptions and other metadata.">
				<input type=radio name=type value="keywords" checked>Keywords</label> /
			<label title="This AI-powered search finds images that are visually similar to what you're looking for, instead of text descriptions. The system works best with general visual concepts, like 'gothic cathedral' or 'castle at sunset', rather than specific names or landmarks.">
				<input type=radio name=type value="similarity">&quot;Looks Like&quot;</label> Mode <a href="#" onclick="restoreInitialHelp();return false;">?</a>
		</div>
		<div class="form-column">
			And/or <b>Near</b>:   &nbsp; (<a href="#" onclick="getLocation(performSearch);return false;">My Location</a>)
			<input type=search id="loc" name="loc" size="36" placeholder="Enter location"><br>
			<label for="distance">Within Distance:</label> <input type="number" id="distance" name="distance" value="2000" style="width:60px;text-align:right" step=100 min=100 max="100000">m

			<div id="location-disambiguation"></div>
		</div>

		<div id="date-filter-box" class="form-column hidden">
			<label for="date_start"><b>Start Date</b>:</label>
			<input type="date" id="date_start" name="date_start" min="1800-01-01"><br>
			<label for="date_end"><b>End Date</b>:</label>
			<input type="date" id="date_end" name="date_end" min="1800-01-01">
			<button type="button" id="clear-dates-btn">Clear Dates</button><br>
			Quick: <button type="button" onclick="return setDateRange('-5 year','');">Last 5</button> or 
			<button type="button" onclick="return setDateRange('','-20 year');">Older than 20</button> years
		</div>
		<div id="contributor-filter-box" class="form-column hidden">
			<label for="contributor"><b>Contributor</b>:</label>
				{dynamic}{if $user && $user->user_id}
				(<a href="#" id="you-button" data-contributor="{$user->user_id} {$user->realname|escape:'html'}">You!</a>)
				{/if}{/dynamic}
				<a href="#" id="invert-button">Invert</a>
			<input type="search" id="contributor" name="contributor" placeholder="Enter contributor name">
		</div>
		<div id="tags-filter-box" class="form-column hidden">  <!-- cant add "keywords-only"  because will get shown when toggle mode -->
			<label for="contributor"><b>Tag(s)</b>:</label>  (ignored in Looks Like mode)
			<input type="search" id="tags" name="tags" placeholder="Enter tag(s) here" size=40>
		</div>
		<div id="resolution-filter-box" class="form-column hidden">
			<label for="resolution"><b>Minimum Resolution</b>:</label>
			<select id="resolution" name="resolution">
				<option value="">None</option>
				<option value="800">800</option>
				<option value="1024">1024</option>
				<option value="3000">3000</option>
			</select>
		</div>

		<div class="form-clear">
			<button type="submit" class="btn-primary">Update</button>
			<button type="button" class="filter-pill" id="add-date-filter">+ Date</button>
			<button type="button" class="filter-pill" id="add-contributor-filter">+ Contributor</button>
			<button type="button" class="filter-pill" id="add-tags-filter">+ Tag</button>
			<button type="button" class="filter-pill" id="add-resolution-filter">+ Resolution </button>
		</div>
		<input type="hidden" id="display-mode" name="display" value="small">
	</form>
	<div id="correction-prompt"></div>
	<br>
{literal}
    <div class="search-controls">
        <div id="results-count" class="results-count"></div>
        <div id="display-tabs" class="tabHolder display-options">
        	<span class="tab-label">Display:</span>
        	<a href="#" class="tab tabSelected" data-display="small">Small Thumbs</a>
        	<a href="#" class="tab" data-display="large">Large Thumbs</a>
        	<a href="#" class="tab" data-display="details">Details</a>
        	<a href="#" class="tab" data-display="river" title="our own format that displays a large image with details">Geo<wbr>River</a>
        	    <a href="#" class="tab" data-display="map">Map</a>
        	<a class="nowrap keywords-only full-version" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">more...</a>
        </div>
    </div>
	<div id="results" class="results-box">
		<p>Just click Update above to see recent images.</p>

		When searching we have offer two different styles of search... (<i>either can be combined with a location, date or contributor filter<span class="full-version">, more advanced filtering available via Original Search or Browser above</span></i>)

		<h3>Keywords Mode</h3>

		<p>This traditional search finds images based on the words you enter, using their <b>descriptions and other 
		metadata</b>.</p> 

		<p style="padding-left:20px"><i>While it's great for finding specific text, be aware of possible false 
		matches, for example, an image's description might mention a place it doesn't actually show. For more 
		advanced search techniques, you can explore <a 
		href="https://www.geograph.org.uk/article/Keyword-Searching-in-the-Browser">the full syntax.</a></i><p>

		<h3>Looks Like Mode</h3>

		<p>This AI-powered search finds images that are <b>visually similar</b> to what you're looking for, instead 
		of text descriptions. The system works best with general visual concepts, like "gothic cathedral" or 
		"castle at sunset", rather than specific names or landmarks.</p>

		<p style="padding-left:20px"><i>The quality of results may decline as you scroll, and the model might not 
		recognize very specific places or species. However, its strength lies in combining visual ideas eg "forest 
		on a sunny day", leading to unique and creative results. To search for a specific location, try using a 
		general description and then refining your search with the "Near" option. For more details: <a 
		href="https://www.geograph.org.uk/article/Using-Looks-Like-Search">Using &quot;Looks Like&quot; 
		Search</a>.</i></p>

		<h3 class="full-version">More</h3>

		<p class="full-version">Or maybe looking for original <a href="/search.php?form=text">Advanced Search</a>? Note however for 
		many queries the <a href="/browser/#!start">Image Browser</a> 
		offers even more options.</p>

	</div>

	<div id="load-more-container" class="hidden" style="text-align: center; padding: 10px;">
		<button id="load-more-btn">Load More Results</button>
	</div>

	<div id="more-results-prompt" class="hidden" style="text-align: center; padding: 20px;">
		<span id="results-count2"></span>
		<span class="nowrap full-version">Continue in: 
		<a href="#" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distancem={distance}">Original Search</a>
		or
		<a href="#" data-template="/browser/redirect.php?q={q}&loc={loc}&date_start={date_start}&date_end={date_end}&contributor={contributor}&distance={distance}">Image Browser</a></span>
	</div>
	<div class="similarity-only hidden" style="text-align: center; padding: 20px;">
		Tip: Drag a image thumbnail into the 'Search For' box, to look for visually similar images. This is a great away to look for more images.
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
<script type="text/javascript" src="{"/js/tags-selector.js"|revision}"></script>
<script type="text/javascript" src="{"/js/geograph-api-libs.js"|revision}"></script>
<script type="text/javascript" src="{"/js/Leaflet.ExpandControl.js"|revision}"></script>
{literal}
<script>

let map = null;
let layerGroup = null;
let initialHelp = null;
let currentLimit = 30;

function restoreInitialHelp() {
	const resultDiv = document.getElementById("results")

                if (map) { //first need to destroy the map!
                        map.remove();
                        map = null;
                }

        resultDiv.className = 'results-box'; //remove all!
	resultDiv.innerHTML = initialHelp.replace(/see recent images/,'return to your results');
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('standalone') === 'true') {
        document.querySelectorAll('.full-version').forEach(function(element) {
            //doing both, to try to make sure stays hidden, eg some code maniputates classes, otehrs change the style directly
            element.style.display = 'none';
            element.classList.add('hidden');
        });
    }

    document.getElementById('finder-form').addEventListener('submit', function(event) {
        event.preventDefault();
        performSearch();
    });

    document.getElementById('load-more-btn').addEventListener('click', function(event) {
        event.preventDefault();
        currentLimit = 100;
        searchAndRender();
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

    document.getElementById('add-tags-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('tags-filter-box').classList.toggle('hidden');
	document.getElementById('add-tags-filter').classList.toggle('hidden');
    });

    document.getElementById('add-resolution-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('resolution-filter-box').classList.toggle('hidden');
        document.getElementById('add-resolution-filter').classList.toggle('hidden');
    });


    document.getElementById('invert-button').addEventListener('click', function(event) {
        event.preventDefault();
	const element =  document.getElementById('contributor');
	if (element.value.indexOf('-') == 0) {
		element.value = element.value.replace(/^-+/,'');
	} else {
		element.value = '-' + element.value;
        }
	performSearch();
    });

    if (document.getElementById('you-button'))
    document.getElementById('you-button').addEventListener('click', function(event) {
        event.preventDefault();
	const element =  document.getElementById('contributor');
	element.value = event.target.dataset.contributor;
	performSearch();
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

    // Get the container and input elements
	const dropContainer = document.querySelector('.drop-container');
	const inputElement = document.querySelector('.drop-container input[name="q"]');

	// Add a dragover event listener
	dropContainer.addEventListener('dragover', function(event) {
	    // Prevent the default browser behavior
	    event.preventDefault();
	    // Set the cursor to a copy icon
	    event.dataTransfer.dropEffect = 'copy';
	});

	// Add a drop event listener
	dropContainer.addEventListener('drop', function(event) {
	    event.preventDefault();
	    const droppedData = event.dataTransfer.getData('text/plain');

	    if (m = droppedData.match(/\/photo\/(\d+)/)) {
	        // Set the value of the input element within the container
	        inputElement.value = "id:" + m[1];
	        document.querySelector('#finder-form input[name="type"][value="similarity"]').checked = true;
	        performSearch();
	    }

	    // Reset the cursor
	    event.dataTransfer.dropEffect = 'none';
	});

	document.body.addEventListener('dragover', function(event) {
	    // Check if the user is dragging an item
	    if (event.dataTransfer && event.dataTransfer.types.includes('text/plain')) {

	        // Get the current vertical position of the cursor
	        const mouseY = event.clientY;

	        // Define a "scroll zone" at the top of the viewport
	        const scrollZoneHeight = 50; // In pixels

	        // Get the current scroll position
	        const currentScrollY = window.scrollY;

	        // If the cursor is in the scroll zone, scroll the page up
	        if (mouseY < scrollZoneHeight) {
	            // Scroll up at a rate proportional to how close the cursor is to the top
	            const scrollSpeed = 8;
	            window.scrollBy(0, -scrollSpeed);
	        } else {
	            // Optional: You could add logic here for scrolling down if needed
	        }
	    }
	});

});

window.addEventListener('popstate', handleUrlQuery);

function renderFinderResults(url, divId, countDivId) {
    const divElement = document.getElementById(divId);
    const countDivElement = document.getElementById(countDivId);
    const moreResultsPrompt = document.getElementById('more-results-prompt');
    const loadMoreContainer = document.getElementById('load-more-container');
    const display = document.getElementById('display-mode').value;

    loadMoreContainer.classList.add('hidden');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                // Throw an error for non-200 status codes (e.g., 503, 404, 500)
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
	    // Switch display class
	    divElement.classList.remove('display-large', 'display-small', 'display-details', 'display-river'); // Add other classes here as they are created
	    divElement.classList.add('display-' + display);

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
				    <span>Taken:</span> ${formatTakenDate(space_date(row.takenday))}<br>
                                    <span>Grid Reference:</span> <tt>${row.grid_reference}</tt><br>
                                    ${row.geodist ? `<br><span>Distance:</span> ${(row.geodist / 1000).toFixed(1)} km` : ''}
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
				    <span>Taken:</span> ${formatTakenDate(space_date(row.takenday))}<br>
                                    <span>Grid Ref:</span> <tt>${row.grid_reference}</tt><br>
                                    ${row.geodist ? `<br><span>Distance</span>: ${(row.geodist / 1000).toFixed(1)} km` : ''}
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
                    countDivElement.innerHTML = `Showing <b>${data.rows.length} of ${data.meta.total_found}</b> results.`;
                }

                // Handle 'More Results' prompt
                if (data.rows.length === 30 && currentLimit === 30) {
                    loadMoreContainer.classList.remove('hidden');
                } 
                if (data.meta && data.meta.total_found > data.rows.length) {
		    document.getElementById('results-count2').textContent = `Showing ${data.rows.length} of ${data.meta.total_found} results.`;
                    moreResultsPrompt.classList.remove('hidden');
                } else {
                    moreResultsPrompt.classList.add('hidden');
                }
            } else {
                divElement.innerHTML = 'No Results';

                let query = document.querySelector('input[name="q"]').value;
		const type = document.querySelector('input[name="type"]:checked').value;
		if (type == 'keywords' && query && query.length > 6 && query.match(/ \w/)) {
	                divElement.innerHTML += '. can try: ';

			query = '"'+query.replace(/[^\w]+/g,' ').trim()+'"/1';
                        newBtn = document.createElement('button');
                        newBtn.innerHTML = 'Try a <b>Match-Any</b> query<br> (matching any words)';
			newBtn.addEventListener('click', function() {
				document.querySelector('input[name="q"]').value = query;
				performSearch();
			});
                        divElement.appendChild(newBtn);

                        newBtn = document.createElement('button');
                        newBtn.innerHTML = 'Try a <b>Looks-Like</b> Query<br> (just does best to match)';
			newBtn.addEventListener('click', function() {
				document.querySelector('input[name="type"][value=similarity]').checked=true;
				performSearch();
			});
                        divElement.appendChild(newBtn);
                }

                countDivElement.classList.add('hidden');
                moreResultsPrompt.classList.add('hidden');                
            }

            //todo, could consider checking if there are matches for 'loc' in the 'place' attribute, and offere to convert to a 'nearest' query??

        }).catch(function(error) {
		if (error == "Error: HTTP error! Status: 503") {
			countDivElement.innerHTML = "Service unavailable. Try again later.";
		} else {
			countDivElement.innerHTML = "An unknown error occurred. Please try again.";
		}
		console.error('Error fetching data:', error);
	});
}

function searchAndRender() {
    let query = document.querySelector('input[name="q"]').value;
    let query_len = query.length; //grab before we modify below!
    const loc = document.querySelector('input[name="loc"]').value;
    let distance = parseInt(document.getElementById('distance').value,10) || 2000;
    const type = document.querySelector('input[name="type"]:checked').value;
    const urlParams = new URLSearchParams(window.location.search);
    const model = urlParams.get('model');
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const tags = document.querySelector('input[name="tags"]').value;
    const display = document.getElementById('display-mode').value;
    const resolution = document.getElementById('resolution').value;

    //in keywords mode, catch a single ID: query - works as related image mode. In similarity mode, implemented server side!
    if (type === 'keywords') {
        const idMatch = query.match(/^\[*id:(\d+)\]*$/);
        if (idMatch) {
            const imageId = idMatch[1];
            fetchAndProcessImageForKeywords(imageId);
            return;
        }
    }

    let base = "https://www.geograph.org.uk/api-facetql.php";
    const data = {
        long: 1,
        select: "id,user_id,realname,grid_reference,title,hash,takenday,width,height",
        limit: currentLimit,
	utf: 1
    };

    //special handler for a list of ids!
    if (query && query.match(/^(id:)?\d+(,\d+)*$/) && type == 'keywords') {
        data['where'] = "id in ("+query.replace(/id:/g,'')+")";

        //todo, certainly doesnt make sense to filter by query, but might want to keep other filters?

        const url = base + '?' + objectToUrlParams(data);
        renderFinderResults(url, 'results', 'results-count');
        return;
    }

    if (contributor) {
	if (m = contributor.match(/^(\d+)\s/)) {
	     query += " user"+m[1];
        }
	if (m = contributor.match(/^-(\d+)\s/)) {
	     query += " -user"+m[1];
        }
    }

    if (type == 'similarity' && query_len) { //no point doing similarity, if no query
        base = "https://www.geograph.org.uk/api-facetql-vector.php"; //for now, requires a different API endpoint
	data['label'] = query;
        data['limit'] = currentLimit;
        if (model) {
            data['model'] = model;
        }
        if (resolution) {
            data['larger'] = resolution + '+';
        }
    } else {
	let matchQuery = query;
	if (resolution) {
		matchQuery += ` @larger ${resolution}`;
	}
	if (matchQuery) {
	    data['match'] = getTextQuery(matchQuery);
	}

	if (tags) {
		data.match = (data.match)?(data.match+' '):'';
		/*
		    const tagList = getSelectedTags(tags.replace(/:/g,' '))
		    data.match += '@tags "'+tagList.join('" "')+'"';
		*/
		data.match += getTextQuery(tags); //will automatically convert to right format! (in particular knows about special prefixes) 
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

	//todo, also look for gridref on the END '/finder/finder.php?loc=Abbeytown/C3411' - we may get them, particully via /place/ URLs!
	} else if (m = loc.match(/\/([A-Z]{1,2})(\d{2}|\d{4}|\d{6}|\d{8}|\d{10})$/)) {
		wgs84 = gridref2wgs(m[1]+m[2]);
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

			// Add a click event listener to the map
			map.on('click', function(e) {
			    // e.latlng contains the coordinates of the clicked point
			    const lat = e.latlng.lat;
			    const lng = e.latlng.lng;

			    const gridref = wgs2gridref(lat, lng, convertZoomtoLen(map.getZoom()));

			    const gridrefHtml = gridref ? `<br><br>Grid Reference: <b>${gridref}</b><br>
				<a href="#" onclick="jumpLocation('${gridref}'); map.closePopup(); return false;">Search This location</a>` : '';

			    // Create a new popup with dynamic content
			    const popupContent = `<p>You clicked the map at:<br>Lat/Long: ${lat.toFixed(6)}, ${lng.toFixed(6)} (WGS84)${gridrefHtml}</p>`;

			    L.popup()
			        .setLatLng(e.latlng) // Use the clicked coordinates to position the popup
			        .setContent(popupContent)
			        .openOn(map);
			});


                    layerGroup = L.layerGroup().addTo(map);

		    if (L.control && L.control.expandButton) {
			L.control.expandButton().addTo(map);
		    }
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
		    correction_prompt.textContent = 'This is only a basic map. Use the [Browser Map] link above (or expand button on map) to explore the results in more detail.';

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
    //we accept the form, or a string 
    if (form && typeof form === 'string')
	document.getElementById('loc').value = form;

	//todo, perhaps could clear whole of location-disambiguation, but for now just target the particully incorrect message
    const note_element = document.getElementById('location-ambigious');
    if (note_element) note_element.remove(); //if there is one of these still there then remove. if select suggesttion it cleared, but if search again manually, it not)

    performSearch();
}
function jumpContributor(form) {
    performSearch();
}

function fetchAndProcessImageForKeywords(imageId) {
    const data = {
        select: 'myriad,hectad,grid_reference,takenyear,takenmonth,takenday,groups,tags,types,contexts,snippets,subjects,place,county,country,scenti,user_id,realname,imageclass',
        where: 'id=' + imageId,
        long: 1,
        utf: 1
    };
    const url = 'https://www.geograph.org.uk/api-facetql.php?' + objectToUrlParams(data);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.rows && data.rows[0]) {
                processImageForKeywords(data.rows[0]);
            }
        });
}

function processImageForKeywords(row) {
    if (!row) return;

    let required = [];
    let optional = [];

    if (row.grid_reference) {
        required.push(row.grid_reference);
    } else if (row.hectad) {
        required.push(row.hectad);
    } else if (row.myriad) {
        required.push(row.myriad);
    }

    if (row.takenday) optional.push(row.takenday);
    if (row.takenmonth) optional.push(row.takenmonth);
    if (row.takenyear > '1' && row.takenyear < 2010) optional.push(row.takenyear);

    const splits = ['contexts', 'groups', 'tags', 'snippets', 'subjects'];
    splits.forEach(key => {
        if (row[key] && row[key].length > 5) {
            const list = row[key].replace(/(^\s*_SEP_\s*|\s*_SEP_\s*$)/g, '').replace(/(top|subject):/g, '').split(/ _SEP_ /);
            list.forEach(item => {
                if (row['tags'] && row['tags'].length > 5 && (item === 'Farm, Fishery, Market Gardening' || item === 'Roads, Road transport' || item === 'Wild Animals, Plants and Mushrooms')) {
                    // skip noisy tags
                } else {
                    optional.push('"' + item.replace(/ and /g, ' * ') + '"');
                }
            });
        }
    });

    if (row.place && row.place.length > 2) optional.push('"' + row.place + '"');
    if (row.user_id) optional.push("user" + row.user_id);
    if (row.imageclass) optional.push('"' + row.imageclass + '"');

    let match = required.join(" ");
    if (optional.length) {
        match += " (" + optional.join("|") + ")";
    }

    if (match) {
        document.querySelector('input[name="q"]').value = match;
        document.querySelector('input[name="type"][value="keywords"]').checked = true;
        performSearch();
    }
}

function performSearch() {
    currentLimit = 30;
    searchAndRender();

    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const distance = document.querySelector('input[name="distance"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const tags = document.querySelector('input[name="tags"]').value;
    const display = document.getElementById('display-mode').value;
    const resolution = document.getElementById('resolution').value;
    const urlParams = new URLSearchParams(window.location.search);
    const model = urlParams.get('model');
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
    if (tags) {
        params.append('tags', tags);
    }
    if (display) {
        params.append('display', display);
    }
    if (model) {
        params.append('model', model);
    }
    if (resolution) {
        params.append('resolution', resolution);
    }

    const newUrl = window.location.pathname + '?' + params.toString();
    history.pushState({query: query, loc: loc, type: type, date_start: date_start, date_end: date_end, contributor: contributor, tags:tags, display: display}, '', newUrl);

    updateTabLinks();
}

function handleUrlQuery() {
    const params = new URLSearchParams(window.location.search);
    let query = params.get('q');
    let loc = params.get('loc');
    const distance = params.get('distance');
    const type = params.get('type');
    const date_start = params.get('date_start');
    const date_end = params.get('date_end');
    const contributor = params.get('contributor');
    const tags = params.get('tags');
    const display = params.get('display') || 'small';
    const resolution = params.get('resolution');

	if (query && !loc && query.match(/(.*) near (.+)/)) {
	    const parts = query.split(' near ', 2);
	    query = parts[0].trim(); 
	    loc = parts[1].trim(); 
	}

    document.querySelector('input[name="q"]').value = query ?? '';
    document.querySelector('input[name="loc"]').value = loc ?? '';
    document.querySelector('input[name="distance"]').value = distance ?? 2000;
    document.querySelector('input[name="date_start"]').value = date_start ?? '';
    document.querySelector('input[name="date_end"]').value = date_end ?? '';
    document.querySelector('input[name="contributor"]').value = contributor ?? '';
    document.querySelector('input[name="tags"]').value = tags ?? '';
    document.getElementById('display-mode').value = display;
    document.getElementById('resolution').value = resolution ?? '';

    if (type) {
        document.querySelectorAll('#finder-form input[name="type"]').forEach(function(input) {
            if (input.value === type)
                input.checked = true;
        });
    }

    document.querySelectorAll('#display-tabs .tab, #display-tabs .tabSelected').forEach(function(tab) {
        tab.classList.remove('tabSelected');
        tab.classList.add('tab');
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

    if (tags) {
        document.getElementById('tags-filter-box').classList.remove('hidden');
	document.getElementById('add-tags-filter').classList.add('hidden');
    }

    if (resolution) {
        document.getElementById('resolution-filter-box').classList.remove('hidden');
        document.getElementById('add-resolution-filter').classList.add('hidden');
    }

    if (query || loc || date_start || date_end || contributor || tags || display == 'map') {
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
        contributor: document.querySelector('input[name="contributor"]').value,
        tags: document.querySelector('input[name="tags"]').value
    };

        if (tags) {
		//this is a stopgap, in particular for browser, shoudl be converted to 'attribute' filters, or maybe just let redirect.php handle it!
		// also need to figure how to exclude tags from the collections/discussions links??
                params.q = (params.q)?(params.q+' '):'';
                params.q += params.tags; //no special formatting!
        }

	const modeMap = {};
	document.querySelectorAll('input[type="radio"][name="type"]').forEach(function(input) {
	    modeMap[input.value] = `.${input.value}-only`;
	});

	// Hide all elements that match the dynamically generated selectors
	document.querySelectorAll(Object.values(modeMap).join(', ')).forEach(element => {
	    element.classList.add('hidden');
	});

        // Now, you can use the map to show only the selected type
	if (modeMap[params.type]) {
	    document.querySelectorAll(modeMap[params.type]).forEach(element => {
	        element.classList.remove('hidden');
	    });
	}

    document.querySelectorAll('a[data-template]').forEach(function(tab) {
        let url = tab.dataset.template;
        for (const key in params) {
            url = url.replace(`{${key}}`, encodeURIComponent(params[key]));
        }
	if (!params.q && !params.loc && !params.date_start && !params.date_end && !params.contributor)
		url = url.replace(/do=1/,''); //when no query, just goto the form
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

function setDateRange(date_start,date_end) {
	if (m = date_start.match(/-(\d+) year/)) {
		const today = new Date();
		today.setFullYear(today.getFullYear() - parseInt(m[1],10));
		date_start = today.toISOString().split('T')[0];
	}
	if (m = date_end.match(/-(\d+) year/)) {
		const today = new Date();
		today.setFullYear(today.getFullYear() - parseInt(m[1],10));
		date_end = today.toISOString().split('T')[0];
	}
        document.querySelector('input[name="date_start"]').value = date_start ?? '';
        document.querySelector('input[name="date_end"]').value = date_end ?? '';

	performSearch();
	return false;
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
            //todo if originalElement == 'loc'
            // ... should still say 'There are a number of places matching your query.' (but note the results are NOT 'combined'. its still likly a query without location!

            const label = document.createElement('label');
            label.innerHTML = '<b>Did you mean</b>:';
            container.appendChild(label);

            const select = document.createElement('select');
            
            const defaultOption = document.createElement('option');
            defaultOption.textContent = 'Choose Location (possible matches)';
            defaultOption.value = '';
            select.appendChild(defaultOption);

            const mapoption = document.createElement('option');
            mapoption.value = '--map--';
            mapoption.textContent = '[-- Open these results on map --]';
            select.appendChild(mapoption);

            data.items.forEach(function(item) {
                const option = document.createElement('option');
                option.value = `${item.gr} ${item.name}`;
                option.textContent = `${item.name.replace(new RegExp('/' + item.gr, 'g'), ' - ' + item.gr)}${item.localities ? ', ' + item.localities : ''}`;
                select.appendChild(option);
            });

            select.addEventListener('change', function(event) {
                if (event.target.value) {
                    if (event.target.value == '--map--') {
                        openPlaceSearch(loc, function(name, gr, lat, lng) {
                            //defer to the callback
                            document.getElementById('loc').value = `${gr} ${name}`;
                            if (originalElement && originalElement == 'q')
                                document.querySelector('input[name="q"]').value = '';
                            performSearch();
                            container.innerHTML = ''; // Clear the dropdown
                            correction_prompt.innerHTML = '';
                        });
                        //but delect for now, incase cancel
                        select.selectedIndex=0;
                        return;
                    }
                    document.getElementById('loc').value = event.target.value;
                    if (originalElement && originalElement == 'q')
                        document.querySelector('input[name="q"]').value = '';
                    performSearch();
                    container.innerHTML = ''; // Clear the dropdown
                    correction_prompt.innerHTML = '';
                }
            });

            container.appendChild(select);

            if (originalElement && originalElement == 'loc') {
                const note = document.createElement('div');
                //todo, if 'q' is empty, could run keyword results??
		note.id = "location-ambigious";
                note.innerHTML = 'Note, below results are not yet filtered by location. Choose place above';
                note.style.backgroundColor = 'yellow';
                container.appendChild(note);
            }

        } else if (data && data.total_found == 1) {
            // If only one result, just use it directly
            document.getElementById('loc').value = `${data.items[0].gr} ${data.items[0].name}`;
            if (originalElement && originalElement == 'q')
                document.querySelector('input[name="q"]').value = '';
            const note_element = document.getElementById('location-ambigious');
	    if (note_element) note_element.remove(); //if there is one of these still there then remove. if select suggesttion it cleared, but if search again manually, it not)
            performSearch();
        } else {
            if (originalElement && originalElement == 'loc')
                container.innerHTML = 'No locations found.';
            //todo, should make clear, the results are NOT filtered by location
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

//basic function to convert a zoom level to grid-reference length!
function convertZoomtoLen(num) {
  const clampedNum = Math.max(5, Math.min(18, num));
  const mappedNum = 4 + (clampedNum - 5) * (6 / 13);
  const roundedNum = Math.round(mappedNum / 2) * 2;
  return Math.max(4, Math.min(10, roundedNum));
}

</script>
{/literal}

{if $inner}
</body>
</html>
{else}
	{include file="_std_end.tpl"}
{/if}
