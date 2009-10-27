{if $token_zoomout}
        {assign var="page_title" value="Kartenansicht :: $gridref"}
{else}
        {assign var="page_title" value="Kartenansicht :: Deutschland"}
{/if}
{assign var="meta_description" value="Geograph-Abdeckungskarte Deutschlands, grüne Quadrate sind noch zu fotografieren"}
{assign var="extra_meta" value="<meta name=\"robots\" content=\"noindex, nofollow\"/>"}
{include file="_std_begin.tpl"}
 
    
 
{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width+20}px">
{if $token_zoomout}
	<div class="map" style="height:{$mosaic_height+20}px;width:{$mosaic_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="W" title="Nach Norden (Alt+W)" href="/map/{$token_north}"><img src="http://{$static_host}/templates/basic/img/arrow_n.gif" alt="North" width="13" height="8"/></a></div>
	<div class="cnr"></div>


	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="A" title="Nach Westen (Alt+A)" href="/map/{$token_west}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="http://{$static_host}/templates/basic/img/arrow_w.gif" alt="West" width="8" height="13"/></a></div>

	<div class="inner" style="width:{$mosaic_width}px;height:{$mosaic_height}px;">
	{if $token_zoomin}
	{foreach from=$mosaic key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
		alt="Karte" ismap="ismap" title="Anklicken um hereinzuzoomen oder Bilder zu betrachten" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
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
			alt="Karte" ismap="ismap" usemap="#map_{$x}_{$y}" title="Anklicken um hereinzuzoomen oder Bilder zu betrachten" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			<map name="map_{$x}_{$y}" id="map_{$x}_{$y}">
			{foreach from=$mapmap key=gx item=gridrow}
				{foreach from=$gridrow key=gy item=gridcell}
					<area shape="rect" coords="{$gx*$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km},{$gx*$mapcell->pixels_per_km+$mapcell->pixels_per_km},{$gy*$mapcell->pixels_per_km+$mapcell->pixels_per_km}" {if $gridcell.gridimage_id}{if $gridcell.imagecount > 1}href="/gridref/{$gridcell.grid_reference}"{else}href="/photo/{$gridcell.gridimage_id}"{/if} title="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} von {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} Bilder in diesem Quadrat){/if}" alt="{$gridcell.grid_reference} : {$gridcell.title|escape:'html'} von {$gridcell.realname|escape:'html'} {if $gridcell.imagecount > 1}&#13;&#10;({$gridcell.imagecount} Bilder in diesem Quadrat){/if}"{else} href="/gridref/{$gridcell.grid_reference}" alt="{$gridcell.grid_reference}" title="{$gridcell.grid_reference}"{/if}/>
				{/foreach}
			{/foreach}
			</map>
			{else}
			<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
			alt="Karte" ismap="ismap" title="Anklicken um hereinzuzoomen oder Bilder zu betrachten" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
			{/if}
		{/foreach}
		</div>
	{/foreach}
	{/if}</div>

	<div class="side" style="height:{$mosaic_height}px;"><a accesskey="D" title="Nach Osten (Alt+D)" href="/map/{$token_east}"><img style="padding-top:{$mosaic_height/2 - 4}px" src="http://{$static_host}/templates/basic/img/arrow_e.gif" alt="East" width="8" height="13"/></a></div>

	<div class="cnr"></div>
	<div class="side" style="width:{$mosaic_width}px;"><a accesskey="X" title="Nach Süden (Alt+X)" href="/map/{$token_south}"><img src="http://{$static_host}/templates/basic/img/arrow_s.gif" alt="South" width="13" height="8"/></a></div>
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
		alt="Karte" ismap="ismap" title="Anklicken um hereinzuzoomen oder Bilder zu betrachten" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
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
		ismap="ismap" alt="Karte" title="Anklicken wählt Ausschnitt in Hauptkarte" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
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
		<img alt="Übersichtskarte Deutschland" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/>
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
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="12" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="30" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="11" height="1"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="1"/></td>
  </tr>

  <tr><!-- row 1 -->
   <td colspan="6"><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/top.gif" width="143" height="9"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="9"/></td>
  </tr>

  <tr><!-- row 2 -->
   <td rowspan="6"><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/left.gif" width="12" height="211"/></td>
   <td>{if $token_zoomin}<a accesskey="S" title="Vergrößern (Alt+S)" href="/map/{$token_zoomin}" onmouseout="di20('zoomin','/templates/germanyde/mapnav/zoomin.gif');"  onmouseover="di20('zoomin','/templates/germanyde/mapnav/zoomin_F2.gif');" ><img alt="Hereinzoomen" id="zoomin" src="http://{$static_host}/templates/germanyde/mapnav/zoomin.gif" width="30" height="29"/></a>{else}<img alt="Hereinzoomen" title="Weiter vergrößern nicht möglich!" id="zoomin" src="http://{$static_host}/templates/germanyde/mapnav/zoomin_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill1" src="http://{$static_host}/templates/germanyde/mapnav/fill1.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="W" title="Nach Norden (Alt+W)" href="/map/{$token_north}" onmouseout="di20('north','/templates/germanyde/mapnav/north.gif');"  onmouseover="di20('north','/templates/germanyde/mapnav/north_F2.gif');" ><img id="north" alt="Nach Norden" src="http://{$static_host}/templates/germanyde/mapnav/north.gif" width="30" height="29"/></a>{else}<img alt="Norden" title="Norden" id="north" src="http://{$static_host}/templates/germanyde/mapnav/north_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill2" src="http://{$static_host}/templates/germanyde/mapnav/fill2.gif" width="30" height="29"/></td>
   <td rowspan="6"><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/right.gif" width="11" height="211"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 3 -->
   <td><img alt="" id="fill3" src="http://{$static_host}/templates/germanyde/mapnav/fill3.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="A" title="Nach Westen (Alt+A)" href="/map/{$token_west}" onmouseout="di20('west','/templates/germanyde/mapnav/west.gif');"  onmouseover="di20('west','/templates/germanyde/mapnav/west_F2.gif');"><img id="west" alt="Nach Westen" src="http://{$static_host}/templates/germanyde/mapnav/west.gif" width="30" height="29"/></a>{else}<img alt="Westen" title="Westen" id="west" src="http://{$static_host}/templates/germanyde/mapnav/west_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill4" src="http://{$static_host}/templates/germanyde/mapnav/fill4.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="D" title="Nach Osten (Alt+D)" href="/map/{$token_east}" onmouseout="di20('east','/templates/germanyde/mapnav/east.gif');"  onmouseover="di20('east','/templates/germanyde/mapnav/east_F2.gif');" ><img id="east" alt="Nach Osten" src="http://{$static_host}/templates/germanyde/mapnav/east.gif" width="30" height="29"/></a>{else}<img alt="Osten" title="Osten" id="east" src="http://{$static_host}/templates/germanyde/mapnav/east_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 4 -->
   <td>{if $token_zoomout}<a accesskey="Q" title="Verkleinern (Alt+Q)" href="/map/{$token_zoomout}" onmouseout="di20('zoomout','/templates/germanyde/mapnav/zoomout.gif');"  onmouseover="di20('zoomout','/templates/germanyde/mapnav/zoomout_F2.gif');"><img id="zoomout" src="http://{$static_host}/templates/germanyde/mapnav/zoomout.gif" width="30" height="29" alt="Herauszoomen"/></a>{else}<img alt="Herauszoomen" title="Weiter verkleinern nicht möglich!" id="zoomout" src="http://{$static_host}/templates/germanyde/mapnav/zoomout_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill5" src="http://{$static_host}/templates/germanyde/mapnav/fill5.gif" width="30" height="29"/></td>
   <td>{if $token_zoomout}<a accesskey="X" title="Nach Süden (Alt+X)" href="/map/{$token_south}" onmouseout="di20('south','/templates/germanyde/mapnav/south.gif');"  onmouseover="di20('south','/templates/germanyde/mapnav/south_F2.gif');"><img id="south" alt="Nach Süden" src="http://{$static_host}/templates/germanyde/mapnav/south.gif" width="30" height="29"/></a>{else}<img alt="Süden" title="Süden" id="south" src="http://{$static_host}/templates/germanyde/mapnav/south_F3.gif" width="30" height="29"/>{/if}</td>
   <td><img alt="" id="fill6" src="http://{$static_host}/templates/germanyde/mapnav/fill6.gif" width="30" height="29"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="29"/></td>
  </tr>

  <tr><!-- row 5 -->
   <td colspan="4"><img alt="" id="middle" src="http://{$static_host}/templates/germanyde/mapnav/middle.gif" width="120" height="11"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="11"/></td>
  </tr>

  <tr><!-- row 6 -->
   <td colspan="4" class="textcell" align="center">
   
   <div style="line-height:1em;padding-top:2px;">
   {if !$token_zoomin}
<form action="/map/{$mosaic_token}" method="get">
<div>
	<label for="gridref">Zentrum bei</label>
	<input id="gridref" type="text" name="gridref" value="{$gridref}" size="8"/>
	<input type="submit" name="setref" value="Los"/>
</div>
</form>{if $hectad}</b>
   Hectad<a href="/help/squares">?</a> <b><a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Bilder um {$gridref} suchen">{$hectad}</a></b>  
   {if $hectad_row}
   <a title="Mosaik für {$hectad_row.hectad_ref} zeigen, vervollständigt {$hectad_row.completed}" href="/maplarge.php?t={$hectad_row.largemap_token}">große Karte zeigen</a>
   {/if}
   {else}
 <a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Bilder um {$gridref} suchen">Bildersuche</a>
   {/if}
   {else}
   {if $hectad}</b>
   Hectad<a href="/help/squares">?</a> <b><a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Bilder um {$gridref} suchen">{$hectad}</a></b>  
   {if $hectad_row}
   <a title="Mosaik für {$hectad_row.hectad_ref} zeigen, vervollständigt {$hectad_row.completed}" href="/maplarge.php?t={$hectad_row.largemap_token}">große Karte zeigen</a>
   {/if}
   {else}Zentrum bei:
 {if $token_zoomout}
 <a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Bilder um {$gridref} suchen">{$gridref}</a>
 {else}
 {$gridref}
 {/if}{/if}{/if}</div>
 
  <div style="line-height:1em;padding-top:6px;">Breite: <b>{$mapwidth}&nbsp;<small>km</small></b></div>
 

 {if $token_big}
  <div style="line-height:1em;padding-top:6px;"><a href="/maplarge.php?t={$token_big}" style="color:#000066">Größere Karte</a></div>
 {/if}
 

 
 <br/>
   </td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="103"/></td>
  </tr>

  <tr><!-- row 7 -->
   <td colspan="4"><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/bottom.gif" width="120" height="10"/></td>
   <td><img alt="" src="http://{$static_host}/templates/germanyde/mapnav/shim.gif" width="1" height="10"/></td>
  </tr>

</table>

{literal}
<script type="text/javascript">
<!-- 
if (document.images) {
zoomin_F2 = new Image(30,29); zoomin_F2.src = "/templates/germanyde/mapnav/zoomin_F2.gif";
north_F2 = new Image(30,29); north_F2.src = "/templates/germanyde/mapnav/north_F2.gif";
west_F2 = new Image(30,29); west_F2.src = "/templates/germanyde/mapnav/west_F2.gif";
east_F2 = new Image(30,29); east_F2.src = "/templates/germanyde/mapnav/east_F2.gif";
zoomout_F2 = new Image(30,29); zoomout_F2.src = "/templates/germanyde/mapnav/zoomout_F2.gif";
south_F2 = new Image(30,29); south_F2.src = "/templates/germanyde/mapnav/south_F2.gif";
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
	<b><a title="nach Rechtsklick &quot;Link-Adresse kopieren&quot; wählen" href="{if $token_zoomout}/map/{$mosaic_token}{else}/profile/{$user_id}/map{/if}">Link zu dieser Karte</a></b>
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

{if $gridref_param && !$gridref_ok }
<div>
Ungültige Koordinaten: {$gridref_param}!
</div>
<br style="clear:both;"/><br/>
{/if}
<div class="tabHolder" style="margin-top:3px">
	Kartentyp:
	<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" href="/map/{$mosaic_token}?depth=0">Abdeckung</a>
	{dynamic}
	{if $realname}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2">Persönlich</a>
	{elseif $user->registered}
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" href="/map/{$mosaic_token}?mine">Persönlich</a>
	{/if}{/dynamic}
	{if $token_zoomin}
	<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" href="/map/{$mosaic_token}?depth=1">Dichte</a>
	{/if}
	{if ($mapwidth == 100 || !$token_zoomin) && $mosaic_ri == 1}
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}">Draggable OS
		{if $mapwidth == 100}<sup style="color:red">New!</sup>{/if}</a>
	{/if}
	{if !$token_zoomin && $mosaic_ri == 1}
	<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab5" href="/mapper/?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}&amp;centi=1">Centisquares Coverage</a>
	{/if}
	{if $mapwidth == 10 || $mapwidth == 100}
		<a class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6" href="/mapsheet.php?t={$mosaic_token}{dynamic}{if $gridref_from}&amp;gridref_from={$gridref_from}{/if}{/dynamic}" title="Druckbare Checkliste zum leichten Prüfen, welche Quadrate noch zu fotografieren sind">Checkliste</a>
	{/if}
	<a class="tab{if $tab == 7}Selected{/if} nowrap" id="tab7" href="/mapprint.php?t={$mosaic_token}">Druckansicht</a>
	
</div>
<div class="interestBox">

<h2 style="margin-bottom:0">Geograph Abdeckungskarte{if $realname}, für <a title="Profil anzeigen" href="/profile/{$user_id}">{$realname}</a>{/if}</h2>
</div>
{if $mosaic_updated}
	<p style="text-align:right; font-size:0.8em; margin-top:0">{$mosaic_updated}</p>
{/if}

<p>Einige Hinweise zur Bedienung:</p>

<ul>
{if !$token_zoomin}
<li>Fährt man den Mauszeiger über ein Bild, erscheint eine Bildbeschreibung. Rechtsklick + "In neuem Fenster/Tab öffnen" sollte bei dieser Vergrößerung funktionieren.</li>
{/if}
<li>Durch Anklicken der großen Karte kann man in ein bestimmtes Gebiet hineinzoomen. Außerdem kann mit den "+"- und "-"-Knöpfen vergrößert und verkleinet werden.</li>
<li>Die Links am Kartenrand sowie die 'N'-, 'O'-, 'S'- und 'W'-Knöpfe erlauben das Verschieben des Kartenausschnitts.</li>
<li>Auch durch das Anklicken der kleinen Übersichtskarte kann ein anderer Kartenausschnitt gewählt werden.</li>
<li>Mit den Tabs unter der Karte kann der Kartentyp geändert werden.</li>
<li>Der "Link zu dieser Karte" erstellt einen gut zugänglichen Link zu dieser Karte, der sauberer ist als die Adresse aus der Adressleiste des Browsers.</li>
{if $token_zoomin}
	<li>Hinweis: "In neuem Fenster/Tab öffnen" funktioniert mit dieser Karte nicht!</li>
{/if}
</ul>


 <hr/>
<div class="copyright">Karten auf dieser Seite: &copy; Copyright Hansj&ouml;rg Lipp und  <a href="osm_users.txt">viele OpenStreetMap-User</a>,
Verwertung unter dieser <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/" class="nowrap">Creative-Commons-Lizenz</a> möglich.</div>

 
{include file="_std_end.tpl"}
