<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" id="geograph">
<head>
<title>Geograph sheet centred on {$gridref}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'}" />
{else}<meta name="description" content="Geograph British Isles is a web based project to collect and reference geographically representative images of every square kilometer of the British Isles."/>{/if}
<meta name="DC.title" content="Geograph:: {$page_title|escape:'html'}">
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/basic.css" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/syndicator.php"/>
<script type="text/javascript" src="/geograph.js"></script>
<style type="text/css">
{literal}

.r
{
	font-size:5pt;	

	font-family:monospace;
	
	background:#eeeeee;
	border:1px solid silver;
	position:absolute;
	width:1em;
	height:1em;
	line-height:1em;
	text-align:center;
	cursor:hand;

	
}

div.zx 
{
	border-left:1px solid black;
}

div.zy 
{
	border-bottom:1px solid black;
}

{/literal}
</style>
</head>

<body>


<div style="position:absolute;padding:5px;left:0.2em;top:0.2em;width:10em;height:1em;font-size:4em;border:1px solid black;background:white;">
<div style="font-size:8pt;font-family:Georgia;Arial;">Print this sheet and take it out with you to mark off the squares that you do. Squares with Geographs are marked with an X. A square with only supplemental images is marked by "s" along with their number, and "p" is shown on squares with just unmoderated images.<br/><div style="float:left; font-size:0.9em">From: <a href="http://{$http_host}/">{$http_host}</a></div><div style="text-align:right; font-size:0.9em">Generated {$smarty.now|date_format:"%A, %B %e, %Y at %H:%M"}</div></div>
</div>
 
{*begin map square divs*}
{foreach from=$grid key=x item=maprow}
{foreach from=$maprow key=y item=mapcell}
<div class="r{if substr($mapcell.grid_reference,$ofe,1) == '0'} zx{/if}{if substr($mapcell.grid_reference,$ofn,1) == '0'} zy{/if}" style="left:{$x+2}em;top:{$y+16}em;">{if $mapcell.has_geographs}X{else}{if $mapcell.pending}p{else}{if $mapcell.accepted}s{else}.{/if}{/if}{/if}</div>
{/foreach}
{/foreach}

{*end map square divs*}




</body>
</html>

