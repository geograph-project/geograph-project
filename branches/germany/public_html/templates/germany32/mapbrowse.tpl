{if $token_zoomout}
        {assign var="page_title" value="Map Browsing :: $gridref"}
{else}
        {assign var="page_title" value="Map Browsing :: Germany"}
{/if}
{assign var="meta_description" value="Geograph coverage map of Germany, showing where we have photos, green squares are yet to be photographed."}
{assign var="extra_meta" value="<meta name=\"robots\" content=\"noindex, nofollow\"/>"}
{include file="_std_begin.tpl"}
 
    
 
{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width+20}px">
{if $token_zoomout}
	<div class="map" style="height:{$mosaic_height+20}px;width:{$mosaic_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="W" title="Pan map north (Alt+W)" href="/map/{$token_north}"><img src="http://{$static_host}/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"/></a></div>
	<div class="cnr"></div>


	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="A" title="Pan map west (Alt+A)" href="/map/{$token_west}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="http://{$static_host}/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"/></a></div>

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
					<area shape="rect" coords="{$gx*$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km},{$gx*$mapcell->pixels_per_km+$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km+$mapcell->pixels_per_km}" {if $gridcell.gridimage_id}{if $gridcell.imagecount > 1}href="/gridref/{$gridcell.grid_reference}"{else}href="/photo/{$gridcell.gridimage_id}"{/if} title="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} by {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}" alt="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} by {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} images in this square){/if}"{else} href="/gridref/{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}" title="{$gridcell.grid_reference}"{/if}/>
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

	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="D" title="Pan map east (Alt+D)" href="/map/{$token_east}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="http://{$static_host}/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"/></a></div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="X" title="Pan map south (Alt+X)" href="/map/{$token_south}"><img src="http://{$static_host}/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"/></a></div>
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

{if $depth}
	<img src="/img/depthkey.png" width="{$mosaic_width}" height="20" style="padding-left:10px;"/>
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
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
	{/if}
{else}
	{foreach from=$overview key=y item=maprow}
		<div style="position:absolute;top:0px;left:0px;">
		{foreach from=$maprow key=x item=mapcell}
		<img alt="Germany Overview Map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/>
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
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="12" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="11" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="1"/></td>
  </tr>

  <tr><!-- row 1 -->
   <td colspan="6"><img alt="" src="http://{$static_host}/templates/basic/mapnav/top.gif" width="143" height="9"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="9"/></td>
  </tr>

  <tr><!-- row 2 -->
   <td rowspan="6"><img alt="" src="http://{$static_host}/templates/basic/mapnav/left.gif" width="12" height="211"/></td>
   <td>{if $token_zoomin}<a accesskey="S" title="Zoom in (Alt+S)" href="/map/{$token_zoomin}" onmouseout="di20('zoomin','/templates/basic/mapnav/zoomin.gif');"  onmouseover="di20('zoomin','/templates/basic/mapnav/zoomin_F2.gif');" ><img alt="Zoom In" id="zoomin" src="http://{$static_host}/templates/basic/mapnav/zoomin.gif" width="30" height="29"/></a>{else}<img alt="Zoom In" title="Can't zoom in any further" id="zoomin" src="http://{$static_host}/templates/basic/mapnav/zoomin_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill1" src="http://{$static_host}/templates/basic/mapnav/fill1.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="W" title="Pan north (Alt+W)" href="/map/{$token_north}" onmouseout="di20('north','/templates/basic/mapnav/north.gif');"  onmouseover="di20('north','/templates/basic/mapnav/north_F2.gif');" ><img id="north" alt="Pan North" src="http://{$static_host}/templates/basic/mapnav/north.gif" width="30" height="29"/></a>{else}<img alt="North" title="North" id="north" src="http://{$static_host}/templates/basic/mapnav/north_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill2" src="http://{$static_host}/templates/basic/mapnav/fill2.gif" width="30" height="29"/></td>
   <td rowspan="6"><img alt="" src="http://{$static_host}/templates/basic/mapnav/right.gif" width="11" height="211"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 3 -->
   <td><img alt="" id="fill3" src="http://{$static_host}/templates/basic/mapnav/fill3.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="A" title="Pan west (Alt+A)" href="/map/{$token_west}" onmouseout="di20('west','/templates/basic/mapnav/west.gif');"  onmouseover="di20('west','/templates/basic/mapnav/west_F2.gif');"><img id="west" alt="Pan West" src="http://{$static_host}/templates/basic/mapnav/west.gif" width="30" height="29"/></a>{else}<img alt="West" title="West" id="west" src="http://{$static_host}/templates/basic/mapnav/west_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill4" src="http://{$static_host}/templates/basic/mapnav/fill4.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="D" title="Pan east (Alt+D)" href="/map/{$token_east}" onmouseout="di20('east','/templates/basic/mapnav/east.gif');"  onmouseover="di20('east','/templates/basic/mapnav/east_F2.gif');" ><img id="east" alt="Pan East" src="http://{$static_host}/templates/basic/mapnav/east.gif" width="30" height="29"/></a>{else}<img alt="East" title="East" id="east" src="http://{$static_host}/templates/basic/mapnav/east_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 4 -->
   <td>{if $token_zoomout}<a accesskey="Q" title="Zoom out (Alt+Q)" href="/map/{$token_zoomout}" onmouseout="di20('zoomout','/templates/basic/mapnav/zoomout.gif');"  onmouseover="di20('zoomout','/templates/basic/mapnav/zoomout_F2.gif');"><img id="zoomout" src="http://{$static_host}/templates/basic/mapnav/zoomout.gif" width="30" height="29" alt="Zoom Out"/></a>{else}<img alt="Zoom Out" title="Can't zoom out any further" id="zoomout" src="http://{$static_host}/templates/basic/mapnav/zoomout_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill5" src="http://{$static_host}/templates/basic/mapnav/fill5.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="X" title="Pan south (Alt+X)" href="/map/{$token_south}" onmouseout="di20('south','/templates/basic/mapnav/south.gif');"  onmouseover="di20('south','/templates/basic/mapnav/south_F2.gif');"><img id="south" alt="Pan South" src="http://{$static_host}/templates/basic/mapnav/south.gif" width="30" height="29"/></a>{else}<img alt="South" title="South" id="south" src="http://{$static_host}/templates/basic/mapnav/south_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill6" src="http://{$static_host}/templates/basic/mapnav/fill6.gif" width="30" height="29"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 5 -->
   <td colspan="4"><img alt="" id="middle" src="http://{$static_host}/templates/basic/mapnav/middle.gif" width="120" height="11"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="11"/></td>
  </tr>

  <tr><!-- row 6 -->
   <td colspan="4" class="textcell" align="center">
   
   <div style="line-height:1em;padding-top:2px;">
   {if $hectad}</b>
   Hectad<a href="/help/squares">?</a> <b><a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Search for images centered around {$gridref}">{$hectad}</a></b>  
   {if $hectad_row}
   <a title="View Mosaic for {$hectad_row.hectad_ref}, completed {$hectad_row.completed}" href="/maplarge.php?t={$hectad_row.largemap_token}">view large map</a>
   {/if}
   {else}Grid Reference at centre
 {if $token_zoomout}
 <a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Search for images centered around {$gridref}">{$gridref}</a>
 {else}
 {$gridref}
 {/if}{/if}</div>
 
  <div style="line-height:1em;padding-top:6px;">Map width <b>{$mapwidth}&nbsp;<small>km</small></b></div>
 

 {if $token_big}
  <div style="line-height:1em;padding-top:6px;"><a href="/maplarge.php?t={$token_big}" style="color:#000066">bigger map</a></div>
 {/if}
 

 
 <br/>
   </td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="103"/></td>
  </tr>

  <tr><!-- row 7 -->
   <td colspan="4"><img alt="" src="http://{$static_host}/templates/basic/mapnav/bottom.gif" width="120" height="10"/></td>
   <td><img alt="" src="http://{$static_host}/templates/basic/mapnav/shim.gif" width="1" height="10"/></td>
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



 
{if $token_zoomout || $realname}
<div style="position:relative;">
	<div style="position:absolute;left:445px;top:5px;">
	<b><a title="right click and select&#13;&#10; [Copy Shortcut] or [Copy Link Location]" href="{if $token_zoomout}/map/{$mosaic_token}{else}/profile/{$user_id}/map{/if}">Link to this Map</a></b>
	</div>
</div>
{/if}
<br style="clear:both;"/><br/>

{if $realname}
	{assign var="tab" value="2"}
{elseif $depth && $token_zoomin}
	{assign var="tab" value="3"}
{else}
	{assign var="tab" value="1"}
{/if}

<div class="tabHolder" style="margin-top:3px">
	Style: 
	<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" href="/map/{$mosaic_token}?depth=0">Coverage</a>
	{dynamic}
	{if $realname}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2">Personalised</a>
	{elseif $user->registered}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" href="/map/{$mosaic_token}?mine"> Personalised</a>
	{/if}{/dynamic}
	{if $token_zoomin}
	<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" href="/map/{$mosaic_token}?depth=1">Depth</a>
	{/if}
	{if ($mapwidth == 100 || !$token_zoomin) && $mosaic_ri == 1}
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}">Draggable OS
		{if $mapwidth == 100}<sup style="color:red">New!</sup>{/if}</a>
	{/if}
	{if !$token_zoomin && $mosaic_ri == 1}
	<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab5" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}&amp;centi=1">Centisquares Coverage</a>
	{/if}
	{if $mapwidth == 10 || $mapwidth == 100}
		<a class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6" href="/mapsheet.php?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}" title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field">Check Sheet</a>
	{/if}
	<a class="tab{if $tab == 7}Selected{/if} nowrap" id="tab7" href="/mapprint.php?t={$mosaic_token}">Printable</a>
	
</div>
<div class="interestBox">

<h2 style="margin-bottom:0">Geograph Coverage Map{if $realname}, for <a title="view user profile" href="/profile/{$user_id}">{$realname}</a>{/if}</h2>
</div>
{if $mosaic_updated}
	<p style="text-align:right; font-size:0.8em; margin-top:0">{$mosaic_updated}</p>
{/if}

<p>Here are a few tips for using our map:</p>

<ul>
{if !$token_zoomin}
<li>Hover over an image for a description. Also right-click "open in new window"/tab should function at this scale.</li>
{/if}
<li>Click on the large map to zoom in on an area of interest. You can also use the +
and - buttons to zoom in and out, or the keyboard shortcuts Alt+Q to zoom out and Alt+S to zoom in</li>
<li>Pan the map using the links at the edges of the map, or the 'N' 'E' 'S' 'W' buttons.
You can also use the keyboard shortcuts Alt+W, Alt+D, Alt+X and Alt+A to pan the map</li>
<li>You can also pan the map by clicking the smaller overview map</li>
<li>Use the tabs under the map to change map style</li>
<li>The "Link to this Map" creates a nice accessible link to this map- which is tidier than many taken direct from the address bar.</li>
{if $token_zoomin}
	<li>NOTE: "open in new window"/tab does NOT function with this map correctly</li>
{/if}
</ul>


 <hr/>
<div class="copyright">Maps on this page, &copy; Copyright Hansj&ouml;rg Lipp and  <a href="osm_users.txt">many OpenStreetMap users</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative Commons Licence</a>.</div> 

 
{include file="_std_end.tpl"}
