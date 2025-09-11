{assign var="page_title" value="Finder"}
{include file="_std_begin.tpl"}

<div style="position:relative;height:800px;">
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
	<form id="finder-form" method="get" style="background-color:#ddd;padding:10px;">
		<div style="float:left; width:300px">
			Search For: <input type=search name=q size="30" value="bailey bridge"> <br>
			<label><input type=radio name=type value="keywords" checked>Keywords Match</label>
			<label><input type=radio name=type value="similarity">Similarity Match</label><br><br>
			<button>Update</button>
		</div>
		<div style="float:left; width:300px">
			And/or Near:
			<input type=search name=loc size="30" placeholder="(enter location)"> <br>
			<br>
			<a href="#" id="add-date-filter">Add Date Filter</a> <a href="#" id="add-contributor-filter">Add Contributor Filter</a>
		</div>
		<div style="clear:both;text-align:center">
		</div>
	</form>
	<div id="date-filter-box" style="display: none; background-color: #eee; padding: 10px; margin-top: 10px;">
		<label for="date_start">Start Date:</label>
		<input type="date" id="date_start" name="date_start">
		<label for="date_end">End Date:</label>
		<input type="date" id="date_end" name="date_end">
	</div>
	<div id="contributor-filter-box" style="display: none; background-color: #eee; padding: 10px; margin-top: 10px;">
		<label for="contributor">Contributor:</label>
		<input type="text" id="contributor" name="contributor" placeholder="Enter contributor name">
	</div>
	<br>
	<div class="tabHolder" style="text-align:right;font-size:0.9em">
		Display:
		<a class="tab{if !$display || $display == 'small'}Selected{/if} nowrap">Small Thumbs</a>
		<a class="tab{if $display == 'large'}Selected{/if} nowrap">Large Thumbs</a>
		<a class="tab{if $display == 'details'}Selected{/if} nowrap">Details</a>
		<a class="tab{if $display == 'river'}Selected{/if} nowrap">GeoRiver</a>
		<a class="tab{if $display == 'map'}Selected{/if} nowrap">Map</a>
		<a class="nowrap">more...</a>
	</div>
	<div id="results-count" style="text-align:right;padding:4px"></div>
	<div id="results" style="background-color:#ddd;padding:32px">
	</div>
</div>

<script type="text/javascript" src="/js/geograph-api-libs.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('finder-form').addEventListener('submit', function(event) {
        event.preventDefault();
        performSearch();
    });

    document.getElementById('add-date-filter').addEventListener('click', function(event) {
        event.preventDefault();
        const dateFilterBox = document.getElementById('date-filter-box');
        if (dateFilterBox.style.display === 'none') {
            dateFilterBox.style.display = 'block';
        } else {
            dateFilterBox.style.display = 'none';
        }
    });

    document.getElementById('add-contributor-filter').addEventListener('click', function(event) {
        event.preventDefault();
        const contributorFilterBox = document.getElementById('contributor-filter-box');
        if (contributorFilterBox.style.display === 'none') {
            contributorFilterBox.style.display = 'block';
        } else {
            contributorFilterBox.style.display = 'none';
        }
    });

    handleUrlQuery();
});

window.addEventListener('popstate', handleUrlQuery);

function searchAndRender() {
    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;

    const base = "https://www.geograph.org.uk/api-facetql.php";
    const data = {
        long: 1,
        select: "id,user_id,realname,grid_reference,title,hash",
        limit: 30,
        type: type
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
    renderAPIResults(url, 'results', 'results-count');
}

function performSearch() {
    searchAndRender();

    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const date_start = document.querySelector('input[name="date_start"]').value;
    const date_end = document.querySelector('input[name="date_end"]').value;
    const contributor = document.querySelector('input[name="contributor"]').value;
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

    const newUrl = window.location.pathname + '?' + params.toString();
    history.pushState({query: query, loc: loc, type: type, date_start: date_start, date_end: date_end, contributor: contributor}, '', newUrl);

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

    document.querySelector('input[name="q"]').value = query ?? '';
    document.querySelector('input[name="loc"]').value = loc ?? '';
    document.querySelector('input[name="date_start"]').value = date_start ?? '';
    document.querySelector('input[name="date_end"]').value = date_end ?? '';
    document.querySelector('input[name="contributor"]').value = contributor ?? '';

    if (type) {
        document.querySelector(`input[name="type"][value="${type}"]`).checked = true;
    }

    if (date_start || date_end) {
        document.getElementById('date-filter-box').style.display = 'block';
    }

    if (contributor) {
        document.getElementById('contributor-filter-box').style.display = 'block';
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
