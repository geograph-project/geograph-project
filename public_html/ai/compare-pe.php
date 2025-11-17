<?php
/**
 * $Project: GeoGraph $
 * $Id: compare-demo.php
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2024 BArry Hunter (geo@barryhunter.co.uk)
 *
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$smarty->display('_std_begin.tpl');
?>

<h2>AI Comparison Demo</h2>

<div class="main-layout2">
    <form id="queryForm" class="sidebar" onsubmit="return false;">
            <div class="colum">
		<b>Coverage:</b><br>
		<select id="coverage" name="coverage" style="width: 215px;">
		    <option value="valeofffestiniog" selected>Vale of Ffestiniog (SH64 and SH74)</option>
		    <option value="myriadsh">Myriad SH</option>
		    <option value="national">National</option>
		</select>
	    </div>
	<div class="results-container">
            <div class="column">
        	<b>AI Query 1:</b><br>
	        <input type="text" name="query1" id="queryInput1" placeholder="Enter your query" style="width: 200px;">
        	<input type="submit" value="Search">
	    </div>
            <div class="column">
        	<b>AI Query 2:</b><br>
	        <input type="text" name="query2" id="queryInput2" placeholder="Enter your query" style="width: 200px;" readonly>
        	<input type="submit" value="Search">
	    </div>
	</div>
    </form>
    <div class="results-container">
        <div class="column">
            <h3>Results A - Clip</h3>
            <div id="results1" class="grid-container"></div>
        </div>
        <div class="column">
            <h3>Results B - PE</h3>
            <div id="results2" class="grid-container"></div>
        </div>
    </div>
</div>

<style>
    form.sidebar {
	background-color:#ccc;
	padding:10px;
    }
    .main-layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 20px;
    }
    .results-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
.results-container::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: calc(50% - 1px); /* Centers the divider in the gap */
    width: 2px; /* The width of the divider */
    background-color: #ccc; /* The color of the divider */
    pointer-events: none; /* Prevents the pseudo-element from blocking clicks */
}

    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(213px, 1fr));
        gap: 2px;
    }
    .grid-container div {
        text-align: center;
        min-height: 160px;
    }
    .grid-container div a:first-child {
        display: block;
    }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="/js/geograph-api-libs.js?<?php echo filemtime("../js//geograph-api-libs.js"); ?>"></script>

<script>
$(function() {
     function runSearch() {
        var query1 = $('#queryInput1').val();
        var query2 = $('#queryInput2').val();
        if (!query1 || !query2) {
            return;
        }

	$('#results1, #results2').empty();

        var coverage = $('#coverage').val();
        var olbounds = null;

        switch (coverage) {
            case 'valeofffestiniog':
                olbounds = "-4.0846952743236,52.93908175401,-3.7909739060408,53.033777228772";
                break;
            case 'myriadsh':
                olbounds = "-4.9520110590438,52.560977841629,-3.5085777898973,53.487176816288";
                break;
            case 'national':
                // No bounds
                break;
        }

        // --- Column 1: Standard Search ---
        var data1 = {
            long: 1,
            select: "id,user_id,realname,grid_reference,title,hash",
            limit: 25,
            utf: 1,
            label: query1
        };
        if (olbounds) {
            data1.olbounds = olbounds;
        }
        var url1 = "/api-facetql-vector.php?" + $.param(data1);
        renderAPIResults(url1, 'results1');

        // --- Column 2: Title-only Search ---
        var data2 = {
            long: 1,
            select: "id,user_id,realname,grid_reference,title,hash",
            limit: 25,
            utf: 1,
            label: query2,
	    model: 'pe'
        };
        if (olbounds) {
            data2.olbounds = olbounds;
        }
        var url2 = "/api-facetql-vector.php?" + $.param(data2);
        renderAPIResults(url2, 'results2');
    }

    $('#queryForm').on('submit', function(e) {
        e.preventDefault();
        runSearch();
    });
    $('#queryInput1').on('input change', function(e) {
	var query1 = $('#queryInput1').val();
	$('#queryInput2').val(query1);
    });

    const urlParams = new URLSearchParams(window.location.search);
    const coverParam = urlParams.get('coverage');
    if (coverParam)
        $('#coverage').val(coverParam);

    const queryParam = urlParams.get('query');
    if (queryParam) {
        $('#queryInput1').val(queryParam);
        $('#queryInput2').val(queryParam);
        runSearch();
    }
});
</script>

<?php
$smarty->display('_std_end.tpl');
