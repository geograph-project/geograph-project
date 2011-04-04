{assign var="page_title" value="Map Viewer :: $gridref"}
{include file="_std_begin.tpl"}

{if $gridref2}
<h2>Geograph Mosaic for <a href="/gridref/{$gridref2}">{$gridref2}</a></h2>
{else}
<h2>Geograph Map for <a href="/gridref/{$gridref}">{$gridref}</a></h2>
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
			{assign var="mapmap" value=$mapcell->getGridArray(true)}
			{if $mapmap}
			<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
			alt="Clickable map" ismap="ismap" usemap="#map_{$x}_{$y}" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			<map name="map_{$x}_{$y}">
			{foreach from=$mapmap key=gx item=gridrow}
				{foreach from=$gridrow key=gy item=gridcell}
					<area shape="rect" coords="{$gx*$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km},{$gx*$mapcell->pixels_per_km+$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km+$mapcell->pixels_per_km}" {if $gridcell.gridimage_id}{if $gridcell.imagecount > 1}href="/gridref/{$gridcell.grid_reference}"{else}href="/photo/{$gridcell.gridimage_id}"{/if} title="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} by {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}" alt="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} by {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}"{else} href="/gridref/{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}"{/if}/>
				{/foreach}
			{/foreach}
			</map>
			{else}
			<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
			alt="Clickable map" ismap="ismap" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			{/if}
		{/foreach}
		</div>
	{/foreach}
	</div>

	<div class="side" style="height:{$mosaic_height}px;">&nbsp;</div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;">&nbsp;</div>
	<div class="cnr"></div>
	</div>


{if $hectads}
<p><small>Nearby completed hectads: 
{foreach from=$hectads key=id item=obj}
{if $obj.hectad == $gridref2} 
<b>{$gridref2}</b>,
{else}
<a title="View Photo Mosaic for {$obj.hectad}, completed {$obj.last_submitted}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad}</a>,
{/if}
{/foreach} <a href="/statistics/fully_geographed.php?myriad={$myriad}">More</a></small></p>
{else}
<br/>
{/if}

<div style="float:left; width:{$overview_width+30}px; height:{$overview_height+30}px; position:relative">
	{include file="_overview.tpl"}
</div>

{if $users}
<table class="report"> 
<thead><tr><td>Photographer</td><td>Last Submission</td><td>First Geographs</td><td>Days</td><td>Categories</td></tr></thead>
<tbody>

{foreach from=$users key=id item=obj}
<tr><td><a title="View profile for {$obj.realname}" href="/profile/{$obj.user_id}{if $obj.credit_realname}?a={$obj.realname|escape:'url'}{/if}">{$obj.realname|escape:'html'}</a></td><td>{$obj.last_date}</td>
<td align="right">{$obj.count}</td><td align="right">{$obj.days}</td><td align="right">{$obj.categories}</td></tr>
{/foreach}

</tbody>
</table>
<br/>
<small><small>* there is a known problem with this list, it might not add up to the correct total, apologies but we hope to resume normal service shortly...</small></small>
{if $mosaic->pixels_per_km > 40} 
<p style="clear:both"/>View <a href="/search.php?first={$gridref2}">First Geographs for {$gridref2} in reverse Date Submitted order</a>.</p>
{else}
<br style="clear:both"/>
{/if}
{/if}

{if $mosaic_updated}
	<p align="center" style="font-size:0.8em">{$mosaic_updated}</p>
{/if}

<p align="center">{if $gridref2}More links for <a href="/gridref/{$gridref2}">{$gridref2}</a> |{/if} <a href="/mapprint.php?t={$mosaic_token}">Printable version of this map</a> | <a href="/mapsheet.php?t={$mosaic_token}">Printable Checksheet for this square</a></p>

<br/>
<div class="copyright">Maps on this page, &copy; Copyright Geograph Project Ltd and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative Commons Licence</a>.</div>  
 
{include file="_std_end.tpl"}
