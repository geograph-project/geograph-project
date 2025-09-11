{assign var="page_title" value="Finder"}
{include file="_std_begin.tpl"}

<div style="position:relative;height:800px;">
	<div class="tabHolder">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap">Original Search</a>
		<a class="tab nowrap">Image Browser</a>
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
			<a href="#">Add Date Filter</a> <a href="#">Add Contributor Filter</a>
		</div>
		<div style="clear:both;text-align:center">
		</div>
	</form>
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

    handleUrlQuery();
});

window.addEventListener('popstate', handleUrlQuery);

function searchAndRender() {
    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;

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

    const url = base + '?' + objectToUrlParams(data);
    renderAPIResults(url, 'results', 'results-count');
}

function performSearch() {
    searchAndRender();

    const query = document.querySelector('input[name="q"]').value;
    const loc = document.querySelector('input[name="loc"]').value;
    const type = document.querySelector('input[name="type"]:checked').value;
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

    const newUrl = window.location.pathname + '?' + params.toString();
    history.pushState({query: query, loc: loc, type: type}, '', newUrl);
}

function handleUrlQuery() {
    const params = new URLSearchParams(window.location.search);
    const query = params.get('q');
    const loc = params.get('loc');
    const type = params.get('type');

    document.querySelector('input[name="q"]').value = query ?? '';
    document.querySelector('input[name="loc"]').value = loc ?? '';

    if (type) {
        document.querySelector(`input[name="type"][value="${type}"]`).checked = true;
    }

    if (query || loc || type) {
        searchAndRender();
    }
}
</script>

{include file="_std_end.tpl"}
