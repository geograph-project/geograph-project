<!DOCTYPE html>
<html id="geograph">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	{if $responsive}
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	{/if}

	{pageheader}
	{if $page_title}<title>{$page_title|escape:'html'} :: Geograph Britain and Ireland</title>
	{else}<title>Geograph Britain and Ireland - photograph every grid square!</title>{/if}
	{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'|truncate:240:"... more"}" />
	{else}<meta name="description" content="Geograph Britain and Ireland is a web-based project to collect and reference geographically representative images of every square kilometre of the British Isles."/>{/if}
	{if $lat && $long}<meta name="ICBM" content="{$lat|escape:'html'}, {$long|escape:'html'}"/>{/if}
	<meta name="DC.title" content="Geograph{if $page_title}:: {$page_title|escape:'html'}{/if}"/>
	{$extra_meta}
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/resp/css/modification.css"|revision}" media="screen" />
	{dynamic}{if $responsive}
		<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/resp/css/responsive.css"|revision}" media="screen" />
	{/if}{/dynamic}
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
	<link rel="search" type="application/opensearchdescription+xml" title="Geograph Britain and Ireland search" href="/stuff/osd.xml" />
	<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
</head>
<body>
<div id="header_block">
  <div id="header">
    <h1 onclick="document.location='/';"><a title="Geograph home page" href="/">Geograph - photograph every grid square</a></h1>
  </div>
</div>
{if $right_block}
{dynamic}<div {if $maincontentclass}class="{$maincontentclass}"{else}class="content3"{/if} id="maincontent_block">{/dynamic}
{else}
{dynamic}<div {if $maincontentclass}class="{$maincontentclass}"{else}class="content2"{/if} id="maincontent_block">{/dynamic}
{/if}
<div id="maincontent" style="position:relative">
