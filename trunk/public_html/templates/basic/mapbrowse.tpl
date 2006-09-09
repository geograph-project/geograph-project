{assign var="page_title" value="Map Browsing :: $gridref"}
{include file="_std_begin.tpl"}

    
 
{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width+20}px">
{if $token_zoomout}
	<div class="map" style="height:{$mosaic_height+20}px;width:{$mosaic_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="W" title="Pan map north (Alt+W)" href="/mapbrowse.php?t={$token_north}"><img src="/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"/></a></div>
	<div class="cnr"></div>


	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="A" title="Pan map west (Alt+A)" href="/mapbrowse.php?t={$token_west}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"></a></div>

	<div class="inner" style="width:{$mosaic_width}px;height:{$mosaic_height}px;">
	{if $token_zoomin}
	{foreach from=$mosaic key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
		alt="Clickable map" ismap="ismap" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}
	{else}
	{foreach from=$mosaic key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
			<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
			alt="Clickable map" ismap="ismap" usemap="#map_{$x}_{$y}" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			<map name="map_{$x}_{$y}">
			{foreach from=$mapcell->getGridArray(true) key=gx item=gridrow}
				{foreach from=$gridrow key=gy item=gridcell}
					<area shape="rect" coords="{$gx*$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km},{$gx*$mapcell->pixels_per_km+$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km+$mapcell->pixels_per_km}" {if $gridcell.gridimage_id}{if $gridcell.imagecount > 1}href="/gridref/{$gridcell.grid_reference}"{else}href="/photo/{$gridcell.gridimage_id}"{/if} title="{$gridcell.grid_reference} : {$gridcell.title} by {$gridcell.realname} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}"{else} href="/gridref/{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}"{/if}/>
				{/foreach}
			{/foreach}
			</map>
		{/foreach}
		</div>
	{/foreach}
	{/if}</div>

	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="D" title="Pan map east (Alt+D)" href="/mapbrowse.php?t={$token_east}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"></a></div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="X" title="Pan map south (Alt+X)" href="/mapbrowse.php?t={$token_south}"><img src="/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"></a></div>
	<div class="cnr"></div>
	</div>
{else}
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
{/if}
{*end containing div for main map*}
</div>


{*begin containing div for overview map*}
<div style="position:relative;float:left;width:{$overview_height+20}px;margin-left:16px;">

<div class="map" style="height:{$overview_height+20}px;width:{$overview_width+20}px">
<div class="cnr"></div>
<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
<div class="cnr"></div>


<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">
{if $token_zoomout}
	{foreach from=$overview key=y item=maprow}
		<div style="position:absolute;top:0px;left:0px;">
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$mosaic_token}&amp;{if !$token_zoomin}o={$overview_token}&amp;{/if}i={$x}&amp;j={$y}&amp;recenter=1"><img 
		ismap="ismap" alt="Clickable map" title="Click to pan main map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}

	{if $marker->width > 3}
	<div style="position:absolute;top:{$marker->top+1}px;left:{$marker->left+1}px;width:{$marker->width}px;height:{$marker->height}px; border:1px solid white; font-size:1px;"></div>
	<div style="position:absolute;top:{$marker->top}px;left:{$marker->left}px;width:{$marker->width}px;height:{$marker->height}px; border:1px solid black; font-size:1px;"></div>
	{else}
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="/templates/basic/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
	{/if}
{else}
	{foreach from=$overview key=y item=maprow}
		<div style="position:absolute;top:0px;left:0px;">
		{foreach from=$maprow key=x item=mapcell}
		<img alt="British Isles Overview Map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/>
		{/foreach}
		</div>
	{/foreach}
{/if}
		
		
</div>

<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

<div class="cnr"></div>
<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
<div class="cnr"></div>


</div>


<table style="margin-top:5px;line-height:0px" border="0" cellpadding="0" cellspacing="0" width="143">

  <tr><!-- Shim row, height 1. -->
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="12" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="11" height="1"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="1"/></td>
  </tr>

  <tr><!-- row 1 -->
   <td colspan="6"><img alt="" src="/templates/basic/mapnav/top.gif" width="143" height="9"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="9"/></td>
  </tr>

  <tr><!-- row 2 -->
   <td rowspan="6"><img alt="" src="/templates/basic/mapnav/left.gif" width="12" height="211"/></td>
   <td>{if $token_zoomin}<a accesskey="S" title="Zoom in (Alt+S)" href="/mapbrowse.php?t={$token_zoomin}" onmouseout="di20('zoomin','/templates/basic/mapnav/zoomin.gif');"  onmouseover="di20('zoomin','/templates/basic/mapnav/zoomin_F2.gif');" ><img alt="Zoom In" id="zoomin" src="/templates/basic/mapnav/zoomin.gif" width="30" height="29"/></a>{else}<img alt="Zoom In" title="Can't zoom in any further" id="zoomin" src="/templates/basic/mapnav/zoomin_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill1" src="/templates/basic/mapnav/fill1.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="W" title="Pan north (Alt+W)" href="/mapbrowse.php?t={$token_north}" onMouseOut="di20('north','/templates/basic/mapnav/north.gif');"  onMouseOver="di20('north','/templates/basic/mapnav/north_F2.gif');" ><img id="north" src="/templates/basic/mapnav/north.gif" width="30" height="29"/></a>{else}<img  alt="Pan North" title="Pan North" id="north" src="/templates/basic/mapnav/north_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill2" src="/templates/basic/mapnav/fill2.gif" width="30" height="29"/></td>
   <td rowspan="6"><img alt="" src="/templates/basic/mapnav/right.gif" width="11" height="211"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 3 -->
   <td><img alt="" id="fill3" src="/templates/basic/mapnav/fill3.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="A" title="Pan west (Alt+A)" href="/mapbrowse.php?t={$token_west}" onMouseOut="di20('west','/templates/basic/mapnav/west.gif');"  onMouseOver="di20('west','/templates/basic/mapnav/west_F2.gif');" ><img id="west" src="/templates/basic/mapnav/west.gif" width="30" height="29"/></a>{else}<img alt="Pan West" title="Pan West" id="west" src="/templates/basic/mapnav/west_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill4" src="/templates/basic/mapnav/fill4.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="D" title="Pan east (Alt+D)" href="/mapbrowse.php?t={$token_east}" onMouseOut="di20('east','/templates/basic/mapnav/east.gif');"  onMouseOver="di20('east','/templates/basic/mapnav/east_F2.gif');" ><img id="east" src="/templates/basic/mapnav/east.gif" width="30" height="29"/></a>{else}<img alt="Pan East" title="Pan East" id="east" src="/templates/basic/mapnav/east_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 4 -->
   <td>{if $token_zoomout}<a accesskey="Q" title="Zoom out (Alt+Q)" href="/mapbrowse.php?t={$token_zoomout}" onMouseOut="di20('zoomout','/templates/basic/mapnav/zoomout.gif');"  onMouseOver="di20('zoomout','/templates/basic/mapnav/zoomout_F2.gif');" ><img id="zoomout" src="/templates/basic/mapnav/zoomout.gif" width="30" height="29"/></a>{else}<img alt="Zoom Out" title="Can't zoom out any further" id="zoomout" src="/templates/basic/mapnav/zoomout_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill5" src="/templates/basic/mapnav/fill5.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="X" title="Pan south (Alt+X)" href="/mapbrowse.php?t={$token_south}" onMouseOut="di20('south','/templates/basic/mapnav/south.gif');"  onMouseOver="di20('south','/templates/basic/mapnav/south_F2.gif');" ><img id="south" src="/templates/basic/mapnav/south.gif" width="30" height="29"/></a>{else}<img alt="Pan South" title="Pan South" id="south" src="/templates/basic/mapnav/south_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill6" src="/templates/basic/mapnav/fill6.gif" width="30" height="29"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 5 -->
   <td colspan="4"><img alt="" id="middle" src="/templates/basic/mapnav/middle.gif" width="120" height="11"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="11"/></td>
  </tr>

  <tr><!-- row 6 -->
   <td colspan="4" style="background:#6476fc;font-size:0.8em;text-align:center;" align="center">
   
   <div style="line-height:1em;padding-top:2px;">
   {if $hectad}</b>
   Hectad<a href="/help/squares">?</a> <b><a style="color:#000066" href="/search.php?q={$gridref}" title="Search for images centered around {$gridref}">{$hectad}</a></b>  
   {if $hectad_row}
   <a title="View Mosaic for {$hectad_row.hectad_ref}, completed {$hectad_row.completed}" href="/maplarge.php?t={$hectad_row.largemap_token}">view large map</a>
   {/if}
   {else}Grid Reference at centre
 {if $token_zoomout}
 <a style="color:#000066" href="/search.php?q={$gridref}" title="Search for images centered around {$gridref}">{$gridref}</a>
 {else}
 {$gridref}
 {/if}{/if}</div>
 
  <div style="line-height:1em;padding-top:6px;">Map width <b>{$mapwidth}&nbsp;<small>km</small></b></div>
 
 {if $mapwidth == 10 || $mapwidth == 100}
  <div style="line-height:1em;padding-top:6px;"><a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}" style="color:#000066">print check sheet</a></div>
 {/if}
 
 {if $token_big}
  <div style="line-height:1em;padding-top:6px;"><a href="/maplarge.php?t={$token_big}" style="color:#000066">bigger map</a></div>
 {/if}
 
   <div style="line-height:1em;padding-top:6px;"><a href="/mapprint.php?t={$mosaic_token}" title="printable version" style="color:#000066">printable version</a></div>
 
 <br/>
   </td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="103"/></td>
  </tr>

  <tr><!-- row 7 -->
   <td colspan="4"><img alt="" src="/templates/basic/mapnav/bottom.gif" width="120" height="10"/></td>
   <td><img alt="" src="/templates/basic/mapnav/shim.gif" width="1" height="10"/></td>
  </tr>

</table>

{literal}
<script type="text/javascript">
<!-- 
if (document.images) {
zoomin_F2 = new Image(30,29); zoomin_F2.src = "/templates/basic/mapnav/zoomin_F2.gif";
north_F2 = new Image(30,29); north_F2.src = "/templates/basic/mapnav/north_F2.gif";
west_F2 = new Image(30,29); west_F2.src = "/templates/basic/mapnav/west_F2.gif";
east_F2 = new Image(30,29); east_F2.src = "/templates/basic/mapnav/east_F2.gif";
zoomout_F2 = new Image(30,29); zoomout_F2.src = "/templates/basic/mapnav/zoomout_F2.gif";
south_F2 = new Image(30,29); south_F2.src = "/templates/basic/mapnav/south_F2.gif";
}
-->
</script>
{/literal}



 {*end containing div for overview map*}
 </div>
 
 





 <br style="clear:both;"/>
 
{if $token_zoomout}
<div style="position:relative;">
	<div style="position:absolute;left:445px;top:5px;">
	<b><a title="right click and select&#13;&#10; [Copy Shortcut] or [Copy Link Location]" href="/map/{$mosaic_token}">Link to this Map</a></b>
	</div>
</div>
{/if}
<br/>

<h2>Map Browsing (beta!)</h2>
<p>Here's a few tips for using our map - we're still developing and testing this, so if you
notice any problems, do let us know.</p>
<ul>
<li style="color:blue">NEW! On the thumbnail (10km width) maps you can now hover over a image for a description. Also right-click "open in new window"/tab should function at this scale.</li>
<li>Click on the large map to zoom in on an area of interest. You can also use the +
and - buttons to zoom in and out, or the keyboard shortcuts Alt+Q to zoom out and Alt+S to zoom in</li>
<li>Pan the map using the links at the edges of the map, or the N,E,S,W buttons.
You can also use the keyboard shortcuts Alt+W, Alt+D, Alt+X and Alt+A to pan the map</li>
<li>You can also pan the map by clicking the smaller overview map</li>
</ul>

{if $mosaic_updated}
	<p align="center" style="font-size:0.8em">{$mosaic_updated}</p>
{/if}
 
<div class="copyright">Maps on this page, &copy; Copyright Geograph Project Ltd and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative Commons Licence</a>.</div> 

 
{include file="_std_end.tpl"}
