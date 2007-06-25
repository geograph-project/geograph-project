{assign var="page_title" value="Most Geographed Squares"}
{include file="_std_begin.tpl"}
<script src="/sorttable.js"></script>

<h2>Most Geographed 100km x 100km Squares (Myriads)</h2>

<p>These are the squares with the best geograph coverage so far! Similar <a href="/statistics/breakdown.php?by=gridsq&ri=1&order=c2">Breakdown by total coverage</a><br/>See also <a href="/statistics/most_geographed.php">10km x 10km Squares</a> and <a href="/statistics/most_geographed.php">1km x 1km Grid Squares</a></p>
<p style="font-size:0.8em">The # number column is the number of squares with (at least) a geograph, and the % column is the percentage of the total 'land' based squares with coverage. Click a column header to change sort order.</p>

<div style="float:left;position:relative;width:50%">
<h4>Great Britain</h4>
<table class="report sortable" id="table1"> 
<thead><tr><td sorted="desc">Rank</td><td>Square</td><td>#</td><td>Land</td><td>%</td></tr></thead>
<tbody>

{foreach from=$most1 key=id item=obj}
<tr><td align="right" sortvalue="{$obj.percentage|thousends}">{$obj.ordinal}</td><td><a title="View map for {$obj.hunk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.hunk_square}</a></td>
<td align="right">{$obj.geograph_count}</td>
<td align="right">{$obj.land_count}</td>
<td align="right">{$obj.percentage|string_format:"%.1f"}</td></tr>
{/foreach}

</tbody>
</table>

</div>

<div style="float:left;position:relative;width:50%">
<h4>Ireland</h4>
<table class="report sortable"  id="table2"> 
<thead><tr><td sorted="desc">Rank</td><td>Square</td><td>#</td><td>Land</td><td>%</td></tr></thead>
<tbody>

{foreach from=$most2 key=id item=obj}
<tr><td align="right" sortvalue="{$obj.percentage|thousends}">{$obj.ordinal}</td><td><a title="View map for {$obj.hunk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.hunk_square}</a></td>
<td align="right">{$obj.geograph_count}</td>
<td align="right">{$obj.land_count}</td>
<td align="right">{$obj.percentage|string_format:"%.1f"}</td></tr>
{/foreach}

</tbody>
</table>

</div>


<br style="clear:both"/>

 		
{include file="_std_end.tpl"}
