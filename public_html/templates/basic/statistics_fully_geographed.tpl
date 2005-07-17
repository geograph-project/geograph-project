{assign var="page_title" value="Most Photographed Squares"}
{include file="_std_begin.tpl"}

<h2>Fully Photographed Squares</h2>

<p>These are the squares with full land coverage! See also <a href="/statistics/most_geographed.php">partially covered squares</a>.</p>
<p style="font-size:0.8em">The # number column is the number of squares with a photo, and the % column is the percentage of the total 'land' based squares with coverage.</p>

<div style="float:left;position:relative;width:40%">
<h3>10km x 10km Squares</h3>
<h4>Great Britain</h4>
<table class="report"> 
<thead><tr><td>Rank</td><td>Square</td><td>#</td><td>%</td></tr></thead>
<tbody>

{foreach from=$most1 key=id item=obj}
<tr><td align="right">{$obj.ordinal}</td><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right" title="{$obj.geograph_count}/{$obj.land_count}">{$obj.geograph_count}</td>
<td align="right">{$obj.percentage|thousends}</td></tr>
{/foreach}

</tbody>
</table>

</div>

<div style="float:left;position:relative;width:40%">
<h3>&nbsp;</h3>
<h4>Ireland</h4>
<table class="report"> 
<thead><tr><td>Rank</td><td>Square</td><td>#</td><td>%</td></tr></thead>
<tbody>

{foreach from=$most2 key=id item=obj}
<tr><td align="right">{$obj.ordinal}</td><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right" title="{$obj.geograph_count}/{$obj.land_count}">{$obj.geograph_count}</td>
<td align="right">{$obj.percentage|thousends}</td></tr>
{/foreach}

</tbody>
</table>

</div>

<div style="float:left;position:relative;width:20%">
<h3>Overview Map</h3>
<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img 
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
{foreach from=$markers key=i item=marker}
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="/templates/basic/img/crosshairs.gif" alt="{$marker->tenk_square}" width="16" height="16"/></div>
{/foreach}
</div>
</div>

</div>

<br style="clear:both"/>
<p style="text-align:center">Last generated at {$generation_time|date_format:"%H:%M"}</p>

 		
{include file="_std_end.tpl"}
