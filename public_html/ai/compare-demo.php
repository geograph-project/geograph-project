<?php
/**
 * $Project: GeoGraph $
 * $Id: compare-demo.php
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2024 BArry Hunter (geo@barryhunter.co.uk)
 *
 */

require_once('../../geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$smarty->display('_std_begin.tpl');
?>

<h2>AI Comparison Demo</h2>

<div class="main-layout">
    <form id="queryForm" class="sidebar" onsubmit="return false;">
        <b>Query:</b><br>
        <input type="text" name="query" id="queryInput" placeholder="Enter your query" style="width: 200px;">
        <input type="submit" value="Search">
    </form>
    <div class="results-container">
        <div class="column">
            <h3>Results A</h3>
            <div id="results1" class="grid-container"></div>
        </div>
        <div class="column">
            <h3>Results B</h3>
            <div id="results2" class="grid-container"></div>
        </div>
    </div>
</div>

<style>
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
    $('#queryForm').on('submit', function(e) {
        e.preventDefault();
        var query = $('#queryInput').val();
        if (!query) {
            return;
        }

        // --- Column 1: Standard Search ---
        var data1 = {
            long: 1,
            select: "id,user_id,realname,grid_reference,title,hash",
            limit: 15,
            utf: 1,
            match: getTextQuery(query)
        };
        var url1 = "https://www.geograph.org.uk/api-facetql.php?" + $.param(data1);
        renderAPIResults(url1, 'results1');

        // --- Column 2: Title-only Search ---
        var data2 = {
            long: 1,
            select: "id,user_id,realname,grid_reference,title,hash",
            limit: 15,
            utf: 1,
            match: getTextQuery('@title ' + query)
        };
        var url2 = "https://www.geograph.org.uk/api-facetql.php?" + $.param(data2);
        renderAPIResults(url2, 'results2');
    });
});
</script>

<?php
$smarty->display('_std_end.tpl');
?>