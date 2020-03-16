<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{if $rastermap->service == 'Google'} xmlns:v="urn:schemas-microsoft-com:vml"{/if} xml:lang="en" id="geograph">
<head>
	{pageheader}
	{if $page_title}<title>{$page_title|escape:'html'} :: Geograph Prydain ac Iwerddon</title>
	{else}<title>Geograph Prydain ac Iwerddon - llun o bob sgw&acirc;ar y grid!</title>{/if}
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'|truncate:240:"... mwy"}" />
	{else}<meta name="description" content="Nod prosiect Geograph Prydain ac Iwerddon yw casglu lluniau a gwybodaeth ar gyfer pob cilometr sgw&acirc;r ym Mhrydain Fawr ac Iwerddon, a gallwch chi fod yn rhan o hynny."/>{/if}
	{if $lat && $long}<meta name="ICBM" content="{$lat|escape:'html'}, {$long|escape:'html'}"/>{/if}
	<meta name="DC.title" content="Geograph{if $page_title}:: {$page_title|escape:'html'}{/if}"/>
	{$extra_meta}
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
	<link rel="shortcut icon" type="image/x-icon" href="{$static_host}/favicon.ico"/>
	{if $rss_url}
		<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}"/>
	{elseif $image && $image->gridimage_id && $image->moderation_status ne 'rejected'}
                <link rel="alternate" type="application/json+oembed" href="https://api.geograph.org.uk/api/oembed?url=http%3A%2F%2Fwww.geograph.org.uk%2Fphoto%2F{$image->gridimage_id}&amp;format=json"/>
		<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/photo/{$image->gridimage_id}.kml"/>
	{elseif $profile && $profile->user_id}
		<link rel="alternate" type="application/rss+xml" title="Geograph RSS for {$profile->realname}" href="/feed/userid/{$profile->user_id}.rss"/>
		<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/feed/userid/{$profile->user_id}.kml"/>
	{elseif $engine && $engine->resultCount}
		{if $engine->criteria->displayclass == 'piclens'}
			<link rel="alternate" type="application/rss+xml" title="Media RSS feed" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.media" id="gallery" />
		{else}
			<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss"/>
			<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.kml"/>
		{/if}
	{else}
		<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/feed/recent.rss"/>
	{/if}
	<link rel="search" type="application/opensearchdescription+xml" title="Chwilio Geograph Prydain ac Iwerddon" href="/stuff/osd.xml" />
	<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
	<style>{literal}
		#header h1 {
			background-image: url({/literal}{$static_host}{literal}/templates/basic/img/geograph-logo-welsh-small.png);
		}
		#search_block {
		    width: 430px;
		}
		.nav ul li ul li {
			border-top: 1px solid lightgrey;
		}
	{/literal}</style>
</head>
<body>
<div id="header_block">
  <div id="header">
    <h1 onclick="document.location='/?lang=cy';"><a title="Hafan Geograph" href="/?lang=cy">Geograph - llun o bob sgw&acirc;r ar y grid</a></h1>
  </div>
</div>
{if $right_block}
{dynamic}<div {if $maincontentclass}class="{$maincontentclass}"{else}class="content3"{/if} id="maincontent_block">{/dynamic}
{else}
{dynamic}<div {if $maincontentclass}class="{$maincontentclass}"{else}class="content2"{/if} id="maincontent_block">{/dynamic}
{/if}
<div id="maincontent">

