<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" id="geograph">
<head>
<title>Geograph sheet centred on {$gridref}{if $realname}, for {$realname}{/if}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'}" />
{else}<meta name="description" content="Geograph British Isles is a web based project to collect and reference geographically representative images of every square kilometer of the British Isles."/>{/if}
<meta name="DC.title" content="Geograph:: {$page_title|escape:'html'}">
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/feed/recent.rss"/>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
<style type="text/css">
{literal}

pre
{
	font-size:7pt;
	position:relative;
	font-family:monospace;
	line-height:0.9em;
	cursor:hand;
}

.zx 
{

}

.zy 
{
	
}

{/literal}
</style>
</head>

<body>


<div style="position:absolute;padding:5px;left:0.2em;top:0.1em;width:10em;height:0.8em;font-size:4em;border:1px solid black;background:white;">
<div style="font-size:8pt;font-family:Georgia;Arial;">Print this sheet centered on {$gridref} and take it out with you to mark off the squares that you do. Squares with Geographs are marked with an X. A square with only supplemental images is marked by "s", and "p" is shown on squares with just unmoderated images.<br/><div style="float:left; font-size:0.9em">From: <a href="http://{$http_host}/">{$http_host}</a></div><div style="text-align:right; font-size:0.9em">Generated {$smarty.now|date_format:"%A, %B %e, %Y at %H:%M"}</div></div>
</div>
 
<pre style="position:absolute;left:2em;top:8em;">  {section loop=100 name=x start=0}
{assign var="x" value=$smarty.section.x.index}
{if $x%10 == 0} {/if}
{$starte/10|string_format:"%1d"}{assign var="starte" value=$starte+1}{if $starte >= 100}{assign var="starte" value=$starte-100}{/if}
{/section}

  {assign var="starte" value=$starte+100}{if $starte > 100}{assign var="starte" value=$starte-100}{/if}
{section loop=100 name=x start=0}
{assign var="x" value=$smarty.section.x.index}
{if $x%10 == 0} {/if}
{$starte%10|string_format:"%1d"}{assign var="starte" value=$starte+1}{if $starte > 100}{assign var="starte" value=$starte-100}{/if}
{/section}

{section loop=100 name=y start=0}
{assign var="y" value=$smarty.section.y.index}
{if $y%10 == 0}

{/if}{$startn|string_format:"%02d"}{assign var="startn" value=$startn-1}{if $startn < 0}{assign var="startn" value=$startn+100}{/if}
{section loop=100 name=x start=0}
{assign var="x" value=$smarty.section.x.index}
{if $x%10 == 0} {/if}{if $grid.$x.$y}{assign var="mapcell" value=$grid.$x.$y}{if $mapcell.has_geographs}X{else}{if $mapcell.pending}p{else}{if $mapcell.accepted}s{else}-{/if}{/if}{/if}{else} {/if}
{/section}

{/section}</pre>
</body>
</html>

