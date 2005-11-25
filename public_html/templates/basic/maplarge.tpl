{assign var="page_title" value="Map Viewer :: $gridref"}
{include file="_std_begin.tpl"}

<h2>Geograph Mosaic for {$gridref2}</h2>
	<div class="map" style="height:{$mosaic_height+20}px;width:{$mosaic_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;">&nbsp;</div>
	<div class="cnr"></div>


	<div class="side" style="height:{$mosaic_height}px;">&nbsp;</div>

	<div class="inner" style="width:{$mosaic_width}px;height:{$mosaic_height}px;">
	{foreach from=$mosaic key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
		alt="Clickable map" ismap="ismap" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}
	</div>

	<div class="side" style="height:{$mosaic_height}px;">&nbsp;</div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;">&nbsp;</div>
	<div class="cnr"></div>
	</div>
<br/>
<div style="float:left; width:{$overview_width+30}px; height:{$overview_height+30}px; position:relative">
	{include file="_overview.tpl"}
</div>
<div>

<table class="report"> 
<thead><tr><td>Last Submission</td><td>Contributor</td><td>Photos</td></tr></thead>
<tbody>

{foreach from=$users key=id item=obj}
<tr><td>{$obj.last_date}</td><td><a title="View map for {$obj.tenk_square}" href="/profile.php?u={$obj.user_id}">{$obj.realname}</a></td>
<td align="right" >{$obj.count}</td></tr>
{/foreach}

</tbody>
</table>
</div>
 
<p style="clear:both"/>View <a href="/search.php?first={$gridref2}">First Geographs for {$gridref2} in Reverse Date Submitted Order</a>.</p>
 
 
{include file="_std_end.tpl"}
