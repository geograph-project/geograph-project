<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" id="geograph">
<head>
<title>Geograph Map</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/basic.css" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>

<script type="text/javascript" src="/geograph.js"></script>

</head>

<body>
{dynamic}
{if $error} 
	<p>ERROR: {$error}</p>
{else}
	{if $rastermap->enabled}
		<div style="float:left; position:relative; width: 350px">
		<div class="interestBox">Grid Reference: <b>{$gridref}</b></div>
	
		<div class="rastermap">
			<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
			{$rastermap->getImageTag()}<br/>
			<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>

			</div>

			{$rastermap->getScriptTag()}
		</div>
		
	{else} 
		<script type="text/javascript" src="/mapping.v{$javascript_version}.js"></script>
	{/if}
<div style="float:left; position:relative; font-size:0.8em;" class="interestBox">
<i>Placenames featured on this map:</i>
<ul>
{foreach from=$places item=place}
	<li>{$place.full_name}</li>
	
{/foreach}
</ul>
</div>
<br style="clear:both"/>
{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{/if}
{/dynamic}
</body>
</html>

