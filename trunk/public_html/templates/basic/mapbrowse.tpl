{assign var="page_title" value="Map Browsing :: $gridref"}
{include file="_std_begin.tpl"}

    <h2>Map Browsing</h2>
 
 <div style="float:right">

	<div style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;background:#6574FF;border:3px solid #000066;">
		<div style="position:absolute;top:0px;left:0px;">
		{foreach from=$overview key=y item=maprow}
			<div>
			{foreach from=$maprow key=x item=mapcell}
			<a href="/mapbrowse.php?t={$token}&i={$x}&j={$y}&recenter="><img 
			ismap="ismap" style="float:left;" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			{/foreach}
			</div>
		{/foreach}
		</div>
		{if $marker->width < 150}

			{if $marker->width > 3}
			<div style="position:absolute;top:{$marker->top}px;left:{$marker->left}px;width:{$marker->width}px;height:{$marker->height}px; border:1px solid yellow; font-size:1px;"></div>
			{else}
			<div style="position:absolute;top:{$marker->top-4}px;left:{$marker->left-4}px;"><img src="	/templates/basic/img/crosshairs.gif" alt="+" width="9" height="9"></div>
			{/if}
		{/if}

 	</div>
 	
<br/>
 <a href="/mapbrowse.php?t={$token_zoomout}">Zoom out</a><br/>
 
 <p>Grid Reference at center<br/>
 <b>{$gridref}</b></p>
 <p>&middot; Click on the map to zoom in</p>
 <p>&middot; Click a thumbnail to view</p>
 <p>&middot; Click on small map above <br/>
 to pan around</p>
 </div>
	<div class="mapnav" style="width:{$mosaic_width+20}px;height:9px;"><a href="/mapbrowse.php?t={$token_north}" class="mapnav"><img src="/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"><img src="/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"></a></div>
<div style="display:block; width:600px;">
	<div class="mapnav" style="float:left;width:9px;height:{$mosaic_height+2}px;"><a href="/mapbrowse.php?t={$token_west}" class="mapnav"><img src="/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"><img src="/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"></a></div>
	

	<div style="float:left;width:{$mosaic_width}px;height:{$mosaic_height}px;background:#6574FF;border:1px solid #000066;">
	{foreach from=$mosaic key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$token}&i={$x}&j={$y}&zoomin="><img 
		ismap="ismap" title="{$mapcell->getImageUrl()}" style="float:left;" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}
	</div>
	
	<div class="mapnav" style="float:left;width:9px;height:{$mosaic_height+2}px;"><a href="/mapbrowse.php?t={$token_east}" class="mapnav"><img src="/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"><img src="/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"></a></div>
	</div>
	<div class="mapnav" style="width:{$mosaic_width+20}px;height:9px;"><a href="/mapbrowse.php?t={$token_south}" class="mapnav"><img src="/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"><img src="/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"></a></div>
 <br/>
{if $is_admin}
<p><a href="mapbrowse.php?expireAll=0">Clear cache</a>
<a href="mapbrowse.php?expireAll=1">(clear basemaps too)</a></p>

{/if}
 
 
{include file="_std_end.tpl"}
