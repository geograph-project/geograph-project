{assign var="page_title" value="Fully Geographed Squares"}
{include file="_std_begin.tpl"}
<script src="http://s0.{$http_host}/sorttable.js"></script>

<h2>Fully Geographed Hectads</h2>

<p>These are the 10km x 10km squares or hectads<a href="/help/squares">?</a> with full land coverage!
<br/> See also <a href="/statistics/most_geographed.php">partially covered squares</a>.</p>

<p style="font-size:0.8em">The # number column is the number of squares with (at least) a geograph, Click Mosaic for a large Map. Click a column header to change sort order.</p>

<div style="float:left;position:relative;width:50%">
<h4 style="padding-left:10px">Great Britain</h4>
<table class="report sortable" id="table1"> 
<thead><tr><td>Hectad</td><td sorted="desc">Date Completed</td><td>#</td><td>Mosaic</td></tr></thead>
<tbody>

{foreach from=$most1 key=id item=obj}
<tr><td sortvalue="{$obj.tenk_square}"><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right" sortvalue="{$obj.dateraw}">{$obj.date}</td>
<td align="right" title="{$obj.geograph_count}/{$obj.land_count}">{$obj.geograph_count}</td>
<td><a title="View Mosaic for {$obj.tenk_square}" href="/maplarge.php?t={$obj.largemap_token}">Mosaic</a></td></tr>
{/foreach}

</tbody>
</table>

</div>

<div style="float:left;position:relative;width:50%">
<h4 style="padding-left:10px">Ireland</h4>
<table class="report sortable" id="table2"> 
<thead><tr><td>Hectad</td><td sorted="desc">Date Completed</td><td>#</td><td>Mosaic</td></tr></thead>
<tbody>

{foreach from=$most2 key=id item=obj}
<tr><td sortvalue="{$obj.tenk_square}"><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right" sortvalue="{$obj.dateraw}">{$obj.date}</td>
<td align="right" title="{$obj.geograph_count}/{$obj.land_count}">{$obj.geograph_count}</td>
<td><a title="View Mosaic for {$obj.tenk_square}" href="/maplarge.php?t={$obj.largemap_token}">Mosaic</a></tr>
{/foreach}

</tbody>
</table>

</div>



<br style="clear:both"/>
		
{include file="_std_end.tpl"}
