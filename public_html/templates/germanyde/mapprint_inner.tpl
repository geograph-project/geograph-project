<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" id="geograph">
<head>
<title>Geograph sheet {if substr($gridref, -1, 1) == '5' && substr(substr($gridref, -3, 3), 0, 1) == '5'}
   for Hectad {$gridref|regex_replace:"/([A-Z]+\d)\d(\d)\d/":"\\1\\2"} 
   {else} centred on {$gridref}{/if}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<script type="text/javascript" src="{"/geograph.js"|revision}"></script>
</style>
<base target="_parent"/>
</head>
<body>

<div style="position:relative;width:{$mosaic_width+250}px;"> 
 
{*begin containing div for main map*}
<div style="position:relative;float:left;width:{$mosaic_width}px;height:{$mosaic_height}px">

	<table cellspacing="0" cellpadding="0" style="float:left;border:10px solid #000066;line-height:0px">
	{foreach from=$mosaic key=y item=maprow}
		<tr>
		{foreach from=$maprow key=x item=mapcell}
		<td style="line-height:0px"><a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;zoomin=1"><img 
		alt="Clickable map" ismap="ismap" title="Click to zoom in or view image" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}" border="0"/></a></td>
		{/foreach}
		</tr>
	{/foreach}
	</table>
	{if $depth}
		<img src="/img/depthkey.png" width="{$mosaic_width}" height="20" style="padding-left:10px;"/>
	{/if}
	
	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
	&nbsp;&nbsp;&copy; Copyright Geograph Project Ltd</div>
{*end containing div for main map*}
</div>


{*begin containing div for overview map*}
<div style="position:relative;float:left;width:{$overview_width+20}px;margin-left:16px;">

<table cellspacing="0" cellpadding="0" style="border:10px solid #000066">
<tr><td><div style="position:relative;width:{$overview_width}px;height:{$overview_height}px">
{if $token_zoomout}
	{foreach from=$overview key=y item=maprow}
		<div style="position:absolute;top:0px;left:0px;">
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?t={$mosaic_token}&amp;i={$x}&amp;j={$y}&amp;recenter=1"><img 
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
		<img alt="British Isles Overview Map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/>
		{/foreach}
		</div>
	{/foreach}
{/if}
</div>	
</td></tr></table>




<table style="margin-top:5px;width:{$overview_width+20}px;" border="0" cellpadding="5" cellspacing="0">


  <tr>
   <td style="background:#6476fc;font-size:0.8em;text-align:center;" align="center">
   <div style="position:relative;width:{$overview_width}px;">
   <div style="line-height:1em;padding-top:2px;">Grid Reference at centre
 {if $token_zoomout}
 <a style="color:#000066" href="/search.php?{if $user_id}gridref={$gridref}&amp;u={$user_id}&amp;do=1{else}q={$gridref}{/if}" title="Search for images centered around {$gridref}">{$gridref}</a>
 {else}
 {$gridref}
 {/if}</div>
 
  <div style="line-height:1em;padding-top:6px;">Map width <b>{$mapwidth}&nbsp;<small>km</small></b></div>
 
 </div>
   </td>
 
  </tr>



</table>


</div>


 {*end containing div for overview map*}
 </div>
 


 <br style="clear:both;"/><br/>



</body>
</html>
