<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" id="geograph">
<head>
<title>Geograph sheet {if substr($gridref, -1, 1) == '5' && substr(substr($gridref, -3, 3), 0, 1) == '5'}
   for Hectad {$gridref|regex_replace:"/([A-Z]+\d)\d(\d)\d/":"\\1\\2"} 
   {else} centred on {$gridref}{/if}</title>
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
div.g1 {
	background:white ;
	border:1px solid silver;
	font-size:4em;
	position:absolute;
	width:1em;
	height:1em;
	line-height:0.5em;
	text-align:center;
	cursor:hand;
}

div.g2 {
	background:lightgrey;
	border:1px solid silver;
	font-size:4em;
	position:absolute;
	width:1em;
	height:1em;
	line-height:0.5em;
	text-align:center;
	cursor:hand;
}

div.r {
	font-size:8pt;
}

div.d {
	color:gray;
	font-size:8pt;
	line-height:0px;
}

span.s {
	font-size:0.2em;
	line-height:0px;
}

div.t1 {
	font-family:Arial,Verdana;
	font-size:0.3em;
	padding:0;
	margin:0;
	line-height:1.4em;
}

div.t2 {
	font-family:Arial,Verdana;
	font-size:0.8em;
	padding:0;
	margin:0;
	line-height:0.5em;
}

div.zx {
	border-left:1px solid black;
}

div.zy {
	border-bottom:1px solid black;
}

div.hl {
	font-weight:bold;
	background-color:lightgreen;
}


{/literal}
</style>
</head>

<body>


<div style="position:absolute;padding:5px;left:0.2em;top:0.2em;width:10em;height:1em;font-size:4em;border:1px solid black;background:white;">
<div style="font-size:8pt;font-family:Georgia;Arial;">Print this sheet and take it out with you to mark off the squares that you do. Squares with Geographs are marked with an X, multiple geographs include the number in brackets, or 6+4 for 6 geographs and 4 supplementals. Squares with only supplemental images are marked by "sup" and the number, and "pend" is shown on squares with just unmoderated images. The taken date of the last photo in the square is also shown. <br/><div style="float:left; font-size:0.9em">From: <a href="http://{$http_host}/">{$http_host}</a></div><div style="text-align:right; font-size:0.9em">Generated {$smarty.now|date_format:"%A, %B %e, %Y at %H:%M"}</div></div>
</div>
 
{*begin map square divs*}
{foreach from=$grid key=x item=maprow}
{foreach from=$maprow key=y item=mapcell}
<div class="{if $mapcell.has_geographs}g2{else}g1{/if}{if substr($mapcell.grid_reference,$ofe,1) == '0'} zx{/if}{if substr($mapcell.grid_reference,$ofn,1) == '0'} zy{/if}{if $mapcell.grid_reference == $gridref_from} hl{/if}" style="left:{$x+0.2}em;top:{$y+1.6}em;" onclick="window.location='/gridref/{$mapcell.grid_reference}';"><div class="{if $mapcell.has_geographs}t2{else}t1{/if} nowrap">{if $mapcell.has_geographs}x{else}{if $mapcell.pending}pend{else}{if $mapcell.accepted}sup{else}&nbsp;{/if}{/if}{/if}{if $mapcell.imagecount > 1}<span class="s">({if $mapcell.imagecount !=$mapcell.accepted}{$mapcell.imagecount-$mapcell.accepted}{if $mapcell.accepted}+{$mapcell.accepted}{/if}{else}{$mapcell.imagecount}{/if})</span>{/if}</div><div class="r">{$mapcell.grid_reference}</div>{if $mapcell.last_date && $mapcell.last_date != '00/00/00'}<div class="d">{$mapcell.last_date}</div>{/if}</div>
{/foreach}
{/foreach}

{*end map square divs*}




</body>
</html>

