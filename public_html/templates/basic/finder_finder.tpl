{assign var="page_title" value="Finder"}
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
    --text-align: center;
}
.display-options {
    text-align: right;
    font-size: 0.9em;
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
}
</style>

<div class="finder-container">
	<div class="tabHolder">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distance={distance}">Original Search</a>
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
			Search For: <input type=search name=q size="30" value="bailey bridge"> <br>
			<label><input type=radio name=type value="keywords" checked>Keywords Match</label>
			<label><input type=radio name=type value="similarity">Similarity Match</label>
		</div>
		<div class="form-column">
			And/or Near:
			<input type=search id="loc" name="loc" size="30" placeholder="(enter location)"><br>
			<label for="distance">Distance (m):</label> <input type="text" id="distance" name="distance" value="2000" size="5">

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
		<a class="tab nowrap" data-template="/browser/redirect.php?q={q}&amp;loc={loc}&amp;dist={distance}&amp;display=map">Map</a>
		<a class="nowrap" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}&amp;distance={distance}">more...</a>
	</div>
	<div id="results" class="results-box display-large">
	</div>

	<div id="more-results-prompt" class="hidden" style="text-align: center; padding: 20px;">
		<span id="results-count2"></span>
		Continue in: 
		<a href="#" data-template="/search.php?q={q}&loc={loc}&type={type}&date_start={date_start}&date_end={date_end}&contributor={contributor}&distance={distance}">Original Search</a>
		or
		<a href="#" data-template="/browser/redirect.php?q={q}&loc={loc}&type={type}&date_start={date_start}&date_end={date_end}&contributor={contributor}&distance={distance}">Image Browser</a>
	</div>
{/literal}
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>
<script src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/js/location-selector.js"|revision}"></script>
<script type="text/javascript" src="{"/js/contributor-selector.js"|revision}"></script>
<script type="text/javascript" src="/js/geograph-api-libs.js?"></script>
{literal}
<script>
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

                data.rows.forEach(row => {
                    let htmlContent = '';
                    let newDiv;
                    switch (display) {
                        case 'river':
                            newDiv = document.createElement('div');
                            newDiv.className = 'river-item';
                            htmlContent = `
                                <div class="river-item-thumb">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                        <img src="${getGeographUrl(row.id, row.hash, 'full')}" alt="${escapeHtml(row.title)}" width="${row.width}" height="${row.height}" loading="lazy">
                                    </a>
                                </div>
                                <div class="river-item-info">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank"><strong>${escapeHtml(row.title)}</strong></a><br>
                                    by <a href="/profile/${row.user_id}">${escapeHtml(row.realname)}</a><br>
				    Taken: ${space_date(row.takenday)}<br>
                                    Grid Reference: ${row.grid_reference}
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
                                    Grid Reference: ${row.grid_reference}
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
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const display = document.getElementById('display-mode').value;

    let base = "https://www.geograph.org.uk/api-facetql.php";
    const data = {
        long: 1,
        select: "id,user_id,realname,grid_reference,title,hash,takenday,width,height",
        limit: 30,
        type: type,
        display: display
    };

    if (contributor) {
	if (m = contributor.match(/^(\d+)\s/)) {
	     query += " user"+m[1];
        }
    }

    if (type == 'similarity') {
        base = "https://www.geograph.org.uk/api-facetql-vector.php"; //for now, requires a different API endpoint
	data['label'] = query;
    } else {
	    //todo, detect if user enters a list of ids!

	    if (query) {
		data['match'] = getTextQuery(query);
	    }
    }

    if (loc) {
	if (m = loc.match(/^([A-Z]{1,2}\d+)\s/)) {
                let distance = parseInt(document.getElementById('distance').value,10) || 2000;
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
    const type = params.get('type');
    const date_start = params.get('date_start');
    const date_end = params.get('date_end');
    const contributor = params.get('contributor');
    const display = params.get('display') || 'small';

    document.querySelector('input[name="q"]').value = query ?? '';
    document.querySelector('input[name="loc"]').value = loc ?? '';
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

    if (query || loc || type || date_start || date_end || contributor) {
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

</script>
{/literal}

{include file="_std_end.tpl"}
