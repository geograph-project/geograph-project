{assign var="page_title" value="Map Viewer :: $gridref"}
{include file="_std_begin.tpl"}
{if $mosaic->pixels_per_km > 40}
<h2>Geograph Mosaic for {$gridref2}</h2>
{else}
<h2>Geograph Map for {$gridref}</h2>
{/if}
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

{if $users}
<table class="report"> 
<thead><tr><td>Contributor</td><td>Last Submission</td><td>First Geographs</td><td>Days</td><td>Categories</td></tr></thead>
<tbody>

{foreach from=$users key=id item=obj}
<tr><td><a title="View profile for {$obj.realname}" href="/profile.php?u={$obj.user_id}">{$obj.realname}</a></td><td>{$obj.last_date}</td>
<td align="right">{$obj.count}</td><td align="right">{$obj.days}</td><td align="right">{$obj.categories}</td></tr>
{/foreach}

</tbody>
</table>
<br/>
<small><small>* there is a known problem with this list, it might not add up to the correct total, apologies but we hope to resume normal service shortly...</small></small>
{if $mosaic->pixels_per_km > 40} 
<p style="clear:both"/>View <a href="/search.php?first={$gridref2}">First Geographs for {$gridref2} in Reverse Date Submitted Order</a>.</p>
{else}
<br style="clear:both"/>
{/if}
{/if} 
 <div style="text-align:center"><a href="/mapprint.php?t={$mosaic_token}">Printable version of this map</a></div>
 <br/>
 <br/>
<div class="copyright">Maps on this page, &copy; Copyright Geograph Project Ltd and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative Commons Licence</a>.</div>  
 
{include file="_std_end.tpl"}
