{if $token_zoomout}
        {assign var="page_title" value="Map Browsing :: $gridref"}
{else}
        {assign var="page_title" value="Map Browsing :: British Isles"}
{/if}
{assign var="meta_description" value="Geograph coverage map of the British Isles, showing where we have photos, green squares are yet to be photographed."}
{include file="_std_begin.tpl"}
<meta name="robots" content="noindex, nofollow"/>
{literal}<style type="text/css">
table.navtable {
	margin-top:5px; line-height:0px
}
.navtable img, .navtable a {
	display:block;
}
.navtable td.textcell {
	background:#6476fc; font-size:0.8em; text-align:center;
}
.navtable td.textcell a {
	display: inline;
}
</style>{/literal}


{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width+20}px">
{if $token_zoomout}
	<div class="map" style="height:{$mosaic_height+20}px;width:{$mosaic_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="W" title="Pan map north (Alt+W)" href="/map/{$token_north}"><img src="{$static_host}/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"/></a></div>
	<div class="cnr"></div>


	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="A" title="Pan map west (Alt+A)" href="/map/{$token_west}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="{$static_host}/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"/></a></div>

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
			{assign var="mapmap" value=$mapcell->getGridArray(true)}
			{if $mapmap}
			<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img
			alt="Clickable map" ismap="ismap" usemap="#map_{$x}_{$y}" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			<map name="map_{$x}_{$y}" id="map_{$x}_{$y}">
			{foreach from=$mapmap key=gx item=gridrow}
				{foreach from=$gridrow key=gy item=gridcell}
					<area shape="rect" coords="{$gx*$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km},{$gx*$mapcell->pixels_per_km+$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km+$mapcell->pixels_per_km}" {if $gridcell.gridimage_id}{if $gridcell.imagecount > 1}href="/gridref/{$gridcell.grid_reference}{if $user_id}?user={$user_id}{/if}"{else}href="/photo/{$gridcell.gridimage_id}"{/if} title="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} by {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}"{else} href="/gridref/{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}" title="{$gridcell.grid_reference}"{/if}/>
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
	{/if}</div>

	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="D" title="Pan map east (Alt+D)" href="/map/{$token_east}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="{$static_host}/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"/></a></div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="X" title="Pan map south (Alt+X)" href="/map/{$token_south}"><img src="{$static_host}/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"/></a></div>
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

{if $depth || $userdepth}
	<img src="{$static_host}/img/depthkey.png" width="{$mosaic_width}" height="20" style="padding-left:10px;"/>
{elseif !$token_zoomin}
	<div style="background-color:#000066;text-align:center;font-size:0.7em;color:white;border-top:1px solid silver">Key
	| <img src="{$static_host}/templates/basic/img/supp_dot.gif"/> Supplemental only
	| <span style="color:#75FF65">No photos</span> |
	</div>
{elseif $recent}
	<div style="background-color:#000066;text-align:center;font-size:0.7em;color:white;border-top:1px solid silver">Key
	| <span style="color:#FF0000">Recent photos</span>
	| <span style="color:#ECCE40">Only older photos</span>
	| <span style="color:#75FF65">No photos</span> |
	</div>
{else}
	<div style="background-color:#000066;text-align:center;font-size:0.7em;color:white;border-top:1px solid silver">Key
	| <span style="color:#FF0000">Geograph(s)</span>
	| <span style="color:#ECCE40">Supplemental only</span>
	| <span style="color:#75FF65">No photos</span> |
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
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
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


<table class="navtable" border="0" cellpadding="0" cellspacing="0" width="143">

  <tr><!-- Shim row, height 1. -->
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="12" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="11" height="1"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="1"/></td>
  </tr>

  <tr><!-- row 1 -->
   <td colspan="6"><img alt="" src="{$static_host}/templates/basic/mapnav/top.gif" width="143" height="9"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="9"/></td>
  </tr>

  <tr><!-- row 2 -->
   <td rowspan="6"><img alt="" src="{$static_host}/templates/basic/mapnav/left.gif" width="12" height="211"/></td>
   <td>{if $token_zoomin}<a accesskey="S" title="Zoom in (Alt+S)" href="/map/{$token_zoomin}" onmouseout="di20('zoomin','{$static_host}/templates/basic/mapnav/zoomin.gif');"  onmouseover="di20('zoomin','{$static_host}/templates/basic/mapnav/zoomin_F2.gif');" ><img alt="Zoom In" id="zoomin" src="{$static_host}/templates/basic/mapnav/zoomin.gif" width="30" height="29"/></a>{else}<img alt="Zoom In" title="Can't zoom in any further" id="zoomin" src="{$static_host}/templates/basic/mapnav/zoomin_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill1" src="{$static_host}/templates/basic/mapnav/fill1.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="W" title="Pan north (Alt+W)" href="/map/{$token_north}" onmouseout="di20('north','{$static_host}/templates/basic/mapnav/north.gif');"  onmouseover="di20('north','{$static_host}/templates/basic/mapnav/north_F2.gif');" ><img id="north" alt="Pan North" src="{$static_host}/templates/basic/mapnav/north.gif" width="30" height="29"/></a>{else}<img alt="North" title="North" id="north" src="{$static_host}/templates/basic/mapnav/north_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill2" src="{$static_host}/templates/basic/mapnav/fill2.gif" width="30" height="29"/></td>
   <td rowspan="6"><img alt="" src="{$static_host}/templates/basic/mapnav/right.gif" width="11" height="211"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 3 -->
   <td><img alt="" id="fill3" src="{$static_host}/templates/basic/mapnav/fill3.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="A" title="Pan west (Alt+A)" href="/map/{$token_west}" onmouseout="di20('west','{$static_host}/templates/basic/mapnav/west.gif');"  onmouseover="di20('west','{$static_host}/templates/basic/mapnav/west_F2.gif');"><img id="west" alt="Pan West" src="{$static_host}/templates/basic/mapnav/west.gif" width="30" height="29"/></a>{else}<img alt="West" title="West" id="west" src="{$static_host}/templates/basic/mapnav/west_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill4" src="{$static_host}/templates/basic/mapnav/fill4.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="D" title="Pan east (Alt+D)" href="/map/{$token_east}" onmouseout="di20('east','{$static_host}/templates/basic/mapnav/east.gif');"  onmouseover="di20('east','{$static_host}/templates/basic/mapnav/east_F2.gif');" ><img id="east" alt="Pan East" src="{$static_host}/templates/basic/mapnav/east.gif" width="30" height="29"/></a>{else}<img alt="East" title="East" id="east" src="{$static_host}/templates/basic/mapnav/east_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 4 -->
   <td>{if $token_zoomout}<a accesskey="Q" title="Zoom out (Alt+Q)" href="/map/{$token_zoomout}" onmouseout="di20('zoomout','{$static_host}/templates/basic/mapnav/zoomout.gif');"  onmouseover="di20('zoomout','{$static_host}/templates/basic/mapnav/zoomout_F2.gif');"><img id="zoomout" src="{$static_host}/templates/basic/mapnav/zoomout.gif" width="30" height="29" alt="Zoom Out"/></a>{else}<img alt="Zoom Out" title="Can't zoom out any further" id="zoomout" src="{$static_host}/templates/basic/mapnav/zoomout_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill5" src="{$static_host}/templates/basic/mapnav/fill5.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="X" title="Pan south (Alt+X)" href="/map/{$token_south}" onmouseout="di20('south','{$static_host}/templates/basic/mapnav/south.gif');"  onmouseover="di20('south','{$static_host}/templates/basic/mapnav/south_F2.gif');"><img id="south" alt="Pan South" src="{$static_host}/templates/basic/mapnav/south.gif" width="30" height="29"/></a>{else}<img alt="South" title="South" id="south" src="{$static_host}/templates/basic/mapnav/south_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill6" src="{$static_host}/templates/basic/mapnav/fill6.gif" width="30" height="29"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 5 -->
   <td colspan="4"><img alt="" id="middle" src="{$static_host}/templates/basic/mapnav/middle.gif" width="120" height="11"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="11"/></td>
  </tr>

  <tr><!-- row 6 -->
   <td colspan="4" class="textcell" align="center">

   <div style="line-height:1.2em;padding-top:2px;">
   {if $hectad}</b>
   Hectad<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> <b><a style="color:#000066" href="/gridref/{$hectad}">{$hectad}</a></b>
   {if $hectad_row}
   <div  style="background-color:skyblue;padding:5px;margin:5px"><a title="View Mosaic for {$hectad_row.hectad}, completed {$hectad_row.last_submitted}" href="/maplarge.php?t={$hectad_row.largemap_token}">View Photo Mosaic</a></div>
   {/if}
   {else}Grid Reference at centre
 {if $token_zoomout}
 <a style="color:#000066" {if $user_id}href="/search.php?gridref={$gridref}&amp;u={$user_id}&amp;do=1" title="Search for images centered around {$gridref}"{else}href="/gridref/{$gridref}/links" title="Links page for {$gridref}"{/if}>{$gridref}</a>
 {else}
 {$gridref}
 {/if}{/if}</div>

  <div style="line-height:1em;padding-top:6px;">Map width <b>{$mapwidth}&nbsp;<small>km</small></b></div>


 {if $token_big}
  <div style="line-height:1em;padding-top:6px;"><a href="/maplarge.php?t={$token_big}" style="color:#000066">bigger map</a></div>
 {/if}



 <br/>
   </td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="103"/></td>
  </tr>

  <tr><!-- row 7 -->
   <td colspan="4"><img alt="" src="{$static_host}/templates/basic/mapnav/bottom.gif" width="120" height="10"/></td>
   <td><img alt="" src="{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="10"/></td>
  </tr>

</table>

{literal}
<script type="text/javascript">
<!--
if (document.images) {
{/literal}
zoomin_F2 = new Image(30,29); zoomin_F2.src = "{$static_host}/templates/basic/mapnav/zoomin_F2.gif";
north_F2 = new Image(30,29); north_F2.src = "{$static_host}/templates/basic/mapnav/north_F2.gif";
west_F2 = new Image(30,29); west_F2.src = "{$static_host}/templates/basic/mapnav/west_F2.gif";
east_F2 = new Image(30,29); east_F2.src = "{$static_host}/templates/basic/mapnav/east_F2.gif";
zoomout_F2 = new Image(30,29); zoomout_F2.src = "{$static_host}/templates/basic/mapnav/zoomout_F2.gif";
south_F2 = new Image(30,29); south_F2.src = "{$static_host}/templates/basic/mapnav/south_F2.gif";
{literal}
}
-->
</script>
{/literal}



 {*end containing div for overview map*}
 </div>







 <br style="clear:both;"/>




{if $token_zoomout || $realname}
<div style="position:relative;">
	<div style="position:absolute;left:445px;top:5px;">
	<b><a title="right click and select&#13;&#10; [Copy Shortcut] or [Copy Link Location]" href="{if $token_zoomout}/map/{$mosaic_token}{else}/profile/{$user_id}/map{/if}">Link to this Map</a></b>
	</div>
</div>
{/if}
<br style="clear:both;"/><br/>

{if !$token_zoomout}
<small>TIP: The new Geograph Browser application, includes a <a href="/browser/#!{if $realname}/realname+%22{$realname|escape:'url'}%22{/if}/display=map">Interactive Map feature</a> - allows easy filtering of results shown<br/><br/></small>
{/if}

{if $coveragelink && !$realname}
	<p><span style=color:red>New!</span> Open this area in new <a href="{$coveragelink}">Interactive Coverage Map</a></p> 
{/if}


{if $realname}
	{assign var="tab" value="2"}
{elseif $depth && $token_zoomin}
	{assign var="tab" value="3"}
{elseif $userdepth && $token_zoomin}
	{assign var="tab" value="13"}
{elseif $recent}
	{assign var="tab" value="8"}
{else}
	{assign var="tab" value="1"}
{/if}

<div class="tabHolder" style="margin-top:3px">
	Style:
	<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" href="/map/{$mosaic_token}?depth=0" title="Map showing squares with/without photos so far">Coverage</a>
	{if $hectad && $hectad_row}
		<a class="tab nowrap" title="View Large Mosaic for {$hectad_row.hectad}, completed {$hectad_row.last_submitted}" href="/maplarge.php?t={$hectad_row.largemap_token}" style="background-color:yellow">Photo Mosaic</a>
  	{/if}
	{dynamic}
	{if $realname}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" title="coverage map of just your photos">Personalised</a>
	{elseif $user->registered}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" href="/map/{$mosaic_token}?mine" title="coverage map of just your photos"> Personalised</a>
	{/if}{/dynamic}
	{if $token_zoomin}
	<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" href="/map/{$mosaic_token}?depth=1" title="visualizes how many photos we have in each square">Depth</a>
	<a class="tab{if $tab == 13}Selected{/if} nowrap" id="tab13" href="/map/{$mosaic_token}?userdepth=1" title="visualizes how many contributors we have in each square">User Depth</a>
	{/if}
	{if $coveragelink}
		 <a class="tab" href="{$coveragelink}">Interactive</a>
	{/if}
	{if $mapwidth == 100 || !$token_zoomin}
		{if $mosaic_ri == 1}
			<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}" title="interactive coverage map overlaid over OS raster maps">Draggable OS</a>
		{/if}
		{if $lat && $long}
                	<a class="tab" href="/mapper/coverage.php?centi=1#zoom=7&lat={$lat}&amp;lon={$long}{if $square->reference_index == 2}&layers=FFT000000000B00FT{/if}">Draggable Map</a>
		{/if}
	{elseif $mosaic_ri == 1}
		<a class="tab" id="tab4">Draggable OS <sup style="font-size:0.7em;color:blue">[Zoom first]</sup></a>
	{/if}
	{if !$token_zoomin && $mosaic_ri == 1}
	<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab5" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}&amp;centi=1" title="shows the coverage at centisquare level - overlaid on OS raster maps">Centisquares Coverage</a>
	{/if}
	{if $mapwidth == 10 || $mapwidth == 100}
		<a class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6" href="/mapsheet.php?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}" title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field">{if $realname}Personalised {elseif $recent}Recent Only {elseif $tab ==3}Depth {elseif $userdepth}User Depth {/if}Check Sheet</a>
	{/if}
	<a class="tab{if $tab == 7}Selected{/if} nowrap" id="tab7" href="/mapprint.php?t={$mosaic_token}" title="A version of this map optimized for printing">Printable</a>
	<a class="tab{if $tab == 8}Selected{/if} nowrap" id="tab8" href="/map/{$mosaic_token}?recent=1" title="shows squares with recent squares - so can find squares without recent photos">Recent Only</a>

	<a href="/article/Mapping-on-Geograph" title="More information about the various map types" class="about">About</a>

</div>
<div class="interestBox">

<h2 style="margin-bottom:0">{if $recent}Recent{else}Geograph{/if} {if $depth && $token_zoomin}Depth{else}Coverage{/if} Map{if $realname}, for <a title="view user profile" href="/profile/{$user_id}">{$realname}</a>{/if}</h2>
{if $recent}<div>&middot;&nbsp;{if $token_zoomin}'Recent' is{else}<b>Only includes{/if} images <u>taken</u> since {$recent|date_format:"%A, %B %e, %Y"}</b>
{if $token_zoomin}<br>&middot;&nbsp;Orange (and Green) squares can earn a <b>TPoint for a contemporary photo</b>.{/if}</div>{/if}
</div>
{if $mosaic_updated}
	<p style="text-align:right; font-size:0.8em; margin-top:0">{$mosaic_updated}</p>
{/if}

<p>Here are a few tips for using our map:</p>

<ul>
{if !$token_zoomin}
<li>Hover over an image for a description.</li>
{/if}
<li>Click on the large map to zoom in on an area of interest. You can also use the +
and - buttons to zoom in and out, or the keyboard shortcuts Alt+Q to zoom out and Alt+S to zoom in</li>
<li>Pan the map using the links at the edges of the map, or the 'N' 'E' 'S' 'W' buttons.
You can also use the keyboard shortcuts Alt+W, Alt+D, Alt+X and Alt+A to pan the map</li>
<li>You can also pan the map by clicking the smaller overview map</li>
<li>Use the tabs under the map to change map style</li>
<li>The "Link to this Map" creates a nice accessible link to this map - which is tidier than many taken direct from the address bar.</li>
</ul>

 <hr/>
<b>Update 2015:</b><br>
<div style="margin-left:40px">This map is starting to show its age, it was made over 10 years ago, with what is now old technology; which alas many modern browsers no longer fully support. 
In particular the "open in new window"/tab does NOT function propelly, nor normal left-clicking on the map at thumbnail scale for some squares. And using other than 100% page zoom can cause issues.
For best results use Internet Explorer 10 or below (yes really!), or Firefox still seems functional.</div>
<br>
We have started work on a new mapping interface: <a href="/mapper/coverage.php">Experimental Coverage Map</a>, which welcome to try, it still needs some work. 

 <hr/>
<div class="copyright">Maps on this page, &copy; Copyright Geograph Project and
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative Commons Licence</a>.</div>


{include file="_std_end.tpl"}
