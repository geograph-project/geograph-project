{assign var="page_title" value="Most Geographed Squares"}
{include file="_std_begin.tpl"}

<h2>Most Geographed Squares{if $myriad}, for Myriad {$myriad}{/if}</h2>

<p>These are the squares with the best coverage so far! See also <a href="/statistics/most_geographed_myriad.php">100km x 100km Squares</a>. <br/>Note that <a href="/statistics/fully_geographed.php">Fully covered 10km x 10km Squares</a> are now listed separately.</p>
<p style="font-size:0.8em">The # number column is the number of squares with (at least) a geograph, and the % column is the percentage of the total 'land' based squares with coverage.</p>

{foreach from=$references_real item=ref key=ri}
{if count($most[$ri])}
<div style="float:left;position:relative;width:24%">
<h3>10km x 10km Squares</h3>
<h4>{$ref}</h4>
<table class="report"> 
<thead><tr><td>Rank</td><td>Square</td><td>#</td><td>%</td></tr></thead>
<tbody>

{foreach from=$most[$ri] key=id item=obj}
<tr><td align="right">{$obj.ordinal}</td><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right" title="{$obj.geograph_count}/{$obj.land_count}">{$obj.geograph_count}</td>
<td align="right">{$obj.percentage|thousends}</td></tr>
{/foreach}

</tbody>
</table>

</div>
{/if}
{/foreach}

{if count($onekm)}
<div style="float:left;position:relative;width:24%;background-color:#dddddd; padding:10px">
<h3>1km Grid Squares</h3>
<table class="report"> 
<thead><tr><td>Rank</td><td>Square</td><td>Images</td></tr></thead>
<tbody>

{foreach from=$onekm key=id item=obj}
<tr><td align="right">{$obj.ordinal}</td><td><a title="View images for {$obj.grid_reference}" href="/gridref/{$obj.grid_reference}">{$obj.grid_reference}</a></td>
<td align="right">{$obj.imagecount}</td>

</tr>
{/foreach}

</tbody>
</table>
</div>
{/if}
<br style="clear:both"/>

 		
{include file="_std_end.tpl"}
