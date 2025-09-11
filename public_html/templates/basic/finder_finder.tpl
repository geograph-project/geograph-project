{assign var="page_title" value="Finder"}
{include file="_std_begin.tpl"}

<style type="text/css">
.hidden {
    display: none;
}
.finder-container {
    position: relative;
    height: 800px;
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
    text-align: center;
}
.display-options {
    text-align: right;
    font-size: 0.9em;
}
.results-count {
    text-align: right;
    padding: 4px;
}
.results-box {
    background-color: #ddd;
    padding: 32px;
}
.filter-box {
    background-color: #eee;
    padding: 10px;
    margin-top: 10px;
}
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
.display-details .details-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 10px;
    padding: 5px;
    border-bottom: 1px solid #ccc;
}
.display-details .details-item-thumb img {
    max-width: 120px;
}
.display-details .details-item-info {
    text-align: left;
}
.display-river .river-item {
    display: grid;
    grid-template-columns: 640px 1fr;
    gap: 10px;
    padding: 5px;
    border-bottom: 1px solid #ccc;
}
.display-river .river-item-thumb img {
    width: 100%;
}
.display-river .river-item-info {
    text-align: left;
}
</style>

<div class="finder-container">
	<div class="tabHolder">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap" data-template="/search.php?do=1&searchtext={q}&amp;location={loc}">Original Search</a>
		<a class="tab nowrap" data-template="/browser/browser-redirect.php?q={q}&amp;loc={loc}">Image Browser</a>
		<a class="tab nowrap">Browser Map</a>
		<a class="tab nowrap">Grouped Results</a>
		<a class="tab nowrap">Collections</a>
		{if $enable_forums}
			<a class="tab nowrap" id="tab8">Discussions</a>
		{/if}
	</div>
	<form id="finder-form" method="get" class="finder-form">
		<div class="form-column">
			Search For: <input type=search name=q size="30" value="bailey bridge"> <br>
			<label><input type=radio name=type value="keywords" checked>Keywords Match</label>
			<label><input type=radio name=type value="similarity">Similarity Match</label><br><br>
			<button>Update</button>
		</div>
		<div class="form-column">
			And/or Near:
			<input type=search id="loc" name="loc" size="30" placeholder="(enter location)"> <br>
			<br>
			<a href="#" id="add-date-filter">Add Date Filter</a> <a href="#" id="add-contributor-filter">Add Contributor Filter</a>
		</div>
		<div class="form-clear">
		</div>
	</form>
	<div id="date-filter-box" class="filter-box hidden">
		<label for="date_start">Start Date:</label>
		<input type="date" id="date_start" name="date_start">
		<label for="date_end">End Date:</label>
		<input type="date" id="date_end" name="date_end">
	</div>
	<div id="contributor-filter-box" class="filter-box hidden">
		<label for="contributor">Contributor:</label>
		<input type="text" id="contributor" name="contributor" placeholder="Enter contributor name">
	</div>
	<br>
	<input type="hidden" id="display-mode" name="display" value="small">
	<div id="display-tabs" class="tabHolder display-options">
		Display:
		<a href="#" class="tab tabSelected nowrap" data-display="small">Small Thumbs</a>
		<a href="#" class="tab nowrap" data-display="large">Large Thumbs</a>
		<a href="#" class="tab nowrap" data-display="details">Details</a>
		<a href="#" class="tab nowrap" data-display="river">GeoRiver</a>
		<a href="#" class="tab nowrap" data-display="map">Map</a>
		<a class="nowrap">more...</a>
	</div>
	<div id="results-count" class="results-count"></div>
	<div id="results" class="results-box display-large">
	</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>
<script type="text/javascript" src="/js/location-selector.js"></script>
<script type="text/javascript" src="/js/contributor-selector.js"></script>
<script type="text/javascript" src="/js/geograph-api-libs.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('finder-form').addEventListener('submit', function(event) {
        event.preventDefault();
        performSearch();
    });

    document.getElementById('add-date-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('date-filter-box').classList.toggle('hidden');
    });

    document.getElementById('add-contributor-filter').addEventListener('click', function(event) {
        event.preventDefault();
        document.getElementById('contributor-filter-box').classList.toggle('hidden');
    });

    handleUrlQuery();

    document.getElementById('display-tabs').addEventListener('click', function(event) {
        if (event.target.dataset.display) {
            event.preventDefault();

            // Update hidden input
            document.getElementById('display-mode').value = event.target.dataset.display;

            // Update selected class
            this.querySelectorAll('.tab').forEach(function(tab) {
                tab.classList.remove('tabSelected');
            });
            event.target.classList.add('tabSelected');

            // Re-run search
            performSearch();
        }
    });
});

window.addEventListener('popstate', handleUrlQuery);

function renderFinderResults(url, divId, countDivId) {
    const divElement = document.getElementById(divId);
    const countDivElement = document.getElementById(countDivId);
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
                                        <img src="${getGeographUrl(row.id, row.hash, 'full')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                    </a>
                                </div>
                                <div class="river-item-info">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank"><strong>${escapeHtml(row.title)}</strong></a><br>
                                    by <a href="/profile/${row.user_id}">${escapeHtml(row.realname)}</a><br>
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
                                        <img src="${getGeographUrl(row.id, row.hash, 'small')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                    </a>
                                </div>
                                <div class="details-item-info">
                                    <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank"><strong>${escapeHtml(row.title)}</strong></a><br>
                                    by <a href="/profile/${row.user_id}">${escapeHtml(row.realname)}</a><br>
                                    Grid Reference: ${row.grid_reference}
                                </div>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                        case 'small':
                            newDiv = document.createElement('div');
                            htmlContent = `
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                    <img src="${getGeographUrl(row.id, row.hash, 'small')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                </a>`;
                            newDiv.innerHTML = htmlContent;
                            break;
                        case 'large':
                        default:
                            newDiv = document.createElement('div');
                            htmlContent = `
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
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
                    countDivElement.textContent = `Showing ${data.rows.length} of ${data.meta.total_found} results.`;
                }
            }
        })
        .catch(error => console.error('Error fetching data:', error));
}

function searchAndRender() {
    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
    const display = document.getElementById('display-mode').value;

    const base = "https://www.geograph.org.uk/api-facetql.php";
    const data = {
        long: 1,
        select: "id,user_id,realname,grid_reference,title,hash",
        limit: 30,
        type: type,
        display: display
    };

    if (query) {
        data['match'] = getTextQuery(query);
    }
    if (loc) {
        data['location'] = loc;
    }
    if (date_start) {
        data['date_start'] = date_start;
    }
    if (date_end) {
        data['date_end'] = date_end;
    }
    if (contributor) {
        data['contributor'] = contributor;
    }

    const url = base + '?' + objectToUrlParams(data);
    renderFinderResults(url, 'results', 'results-count');
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
        }
    });

    if (date_start || date_end) {
        document.getElementById('date-filter-box').classList.remove('hidden');
    }

    if (contributor) {
        document.getElementById('contributor-filter-box').classList.remove('hidden');
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
</script>

{include file="_std_end.tpl"}
