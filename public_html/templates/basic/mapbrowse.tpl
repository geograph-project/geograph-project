{assign var="page_title" value="Map Browsing :: $gridref"}
{include file="_std_begin.tpl"}

    <h2>Map Browsing</h2>
 
{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width+20}px">

<div class="map" style="height:{$mosaic_width+20}px;width:{$mosaic_width+20}px">
<div class="cnr"></div>
<div class="side" style="width:{$mosaic_width}px;"><a accesskey="W" title="Pan map north (Alt+W)" href="/mapbrowse.php?t={$token_north}"><img src="/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"/></a></div>
<div class="cnr"></div>


<div class="side" style="height:{$mosaic_height}px;"><a accesskey="A" title="Pan map west (Alt+A)" href="/mapbrowse.php?t={$token_west}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"></a></div>

<div class="inner" style="width:{$mosaic_width}px;height:{$mosaic_height}px;">
{foreach from=$mosaic key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?t={$token}&i={$x}&j={$y}&zoomin="><img 
	ismap="ismap" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
</div>

<div class="side" style="height:{$mosaic_height}px;"><a accesskey="D" title="Pan map east (Alt+D)" href="/mapbrowse.php?t={$token_east}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"></a></div>

<div class="cnr"></div>
<div class="side" style="width:{$mosaic_width}px;"><a accesskey="X" title="Pan map south (Alt+X)" href="/mapbrowse.php?t={$token_south}"><img src="/templates/basic/img/arrow_s.gif" alt="North" width="13" height="8"></a></div>
<div class="cnr"></div>
</div>

{*end containing div for main map*}
</div>


{*begin containing div for overview map*}
<div style="position:relative;float:left;width:{$overview_width+20}px;margin-left:16px;">

<div class="map" style="height:{$overview_width+20}px;width:{$overview_width+20}px">
<div class="cnr"></div>
<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
<div class="cnr"></div>


<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">
{foreach from=$overview key=y item=maprow}
	<div style="position:absolute;top:0px;left:0px;">
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?t={$token}&i={$x}&j={$y}&recenter="><img 
	ismap="ismap" title="Click to pan main map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}

{if $marker->width < 150}

	{if $marker->width > 3}
	<div style="position:absolute;top:{$marker->top}px;left:{$marker->left}px;width:{$marker->width}px;height:{$marker->height}px; border:1px solid yellow; font-size:1px;"></div>
	{else}
	<div style="position:absolute;top:{$marker->top-4}px;left:{$marker->left-4}px;"><img src="	/templates/basic/img/crosshairs.gif" alt="+" width="9" height="9"></div>
	{/if}
{/if}
		
		
</div>

<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

<div class="cnr"></div>
<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
<div class="cnr"></div>


</div>

<br style="clear:both"/>
<br style="clear:both"/>
<br style="clear:both"/>

<a accesskey="S" title="Zoom in (Alt+S)" href="/mapbrowse.php?t={$token_zoomin}">Zoom in</a><br/>
<a accesskey="Q" title="Zoom out (Alt+Q)" href="/mapbrowse.php?t={$token_zoomout}">Zoom out</a><br/>
 
 <p>Grid Reference at center<br/>
 <b>{$gridref}</b></p>
 <p>&middot; Click on the map to zoom in</p>
 <p>&middot; Click a thumbnail to view</p>
 <p>&middot; Click on small map above <br/>
 to pan around</p>
 
 {*end containing div for overview map*}
 </div>
 
 





 <br style="clear:both;"/>
{if $is_admin}
<p><a href="mapbrowse.php?expireAll=0">Clear cache</a>
<a href="mapbrowse.php?expireAll=1">(clear basemaps too)</a></p>

{/if}
 
 &nbsp;
 
{include file="_std_end.tpl"}
