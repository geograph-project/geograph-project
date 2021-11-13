<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{if $rastermap->service == 'Google'} xmlns:v="urn:schemas-microsoft-com:vml"{/if} xml:lang="en" id="geograph">
<head>
	{pageheader}
	{if $page_title}<title>{$page_title|escape:'html'} :: Geograph Ireland</title>
	{else}<title>Geograph Ireland - photograph every grid square!</title>{/if}
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'|truncate:240:"... more"}" />
	{else}<meta name="description" content="Geograph Ireland is a web based project to collect and reference geographically representative images of every square kilometre of Ireland."/>{/if}
	{if $lat && $long}<meta name="ICBM" content="{$lat}, {$long}"/>{/if}
	<meta name="DC.title" content="Geograph{if $page_title}:: {$page_title|escape:'html'}{/if}"/>
	{$extra_meta}
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/ireland/css/basic.css"|revision}" media="screen" />
	<link rel="shortcut icon" type="image/x-icon" href="{$static_host}/favicon.ico"/>
	{if $rss_url}
	<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}"/>
	{elseif $image && $image->gridimage_id && $image->moderation_status ne 'rejected'}
        <link rel="alternate" type="application/json+oembed" href="https://api.geograph.org.uk/api/oembed?url=http%3A%2F%2Fwww.geograph.ie%2Fphoto%2F{$image->gridimage_id}&amp;format=json"/>
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

	<script type="text/javascript">
	var static_host = '{$static_host}';
	{literal}
	function setuptreemenu() {
		ddtreemenu.createTree("treemenu1", true, 5);
	}
	AttachEvent(window,window.addEventListener?'DOMContentLoaded':'load',setuptreemenu,false);
	{/literal}</script>
	<script type="text/javascript" src="{"/js/simpletreemenu.js"|revision}"></script>
	<link rel="stylesheet" type="text/css" title="Monitor" href="{"/js/simpletree.css"|revision}" media="screen" />
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
{dynamic}
	{if $show_appeal}

<div id="appeal_block" style="background-color:#ffffae;padding:10px;margin-bottom:10px">
	<div style="float:right">
		<a href="#" onclick="return hide_appeal()">Dismiss</a>
	</div>
	<i>"Geograph - exactly what the Internet was invented for."</i><br>

	Mike Parker, the author of the book "Map Addict" among others, said this on becoming Geograph's esteemed Patron.  Like many of the visitors to our site, Mike is a keen user of the vast resource of images held on the site.  
	We are all so used to just dipping into the huge amount of information and images on the Internet, of which Geograph is a significant contributor, that it is easy to forget that running a site like this cost money. 
	You are one of millions of visitors to the website each year.  You and all our other visitors have access to a vast resource of images that may support things like school projects, parish magazines and news articles, or planning your walks and holidays, or just armchair exploring.  Here's just one image of the six million plus to choose from!<br>
	<br>
        {if !$pictureoftheday && !$image}
	<div style=float:left;padding-right:12px>
		<a href="https://www.geograph.org.uk/photo/14" title="SY8080 : Durdle Door from the east by Helena Downton">
			<img src="https://s2.geograph.org.uk/photos/00/00/000014_bfd815d0_213x160.jpg" width=213 height=160></a>
	</div>
	{/if}

	Access to all this is given to you totally free of charge, as our contributors all share their photographs so that they are free to download and use.
	But it costs thousands of pounds every year to run Geograph.  This means that we rely entirely on donations from our users, such as you, to keep the site up and running. Costs have increased dramatically as we have moved to Cloud hosting, and we have to pay for technical assistance.  We have no paid employees, everyone is a volunteer. 
	If you value Geograph, please consider <a href="https://cafdonate.cafonline.org/18714">donating now</a>.  We are pleased to accept one-off donations, or even better, regular monthly gifts, with Gift Aid if you are eligible. 
	Your generosity will help us maintain, expand and improve the resources we can give you.<br>
	Thank you.<br><br>

	Donate: <a href="https://cafdonate.cafonline.org/18714" target=_blank>via Charities Aid Foundation</a>
	or <a href="/help/donate">more options</a>...   (<a href="#" onclick="return hide_appeal()">Dismiss Message</a>)
	<br style=clear:both>
	{literal}
	<script>
	function hide_appeal() {
		setTimeout(function() {
			document.getElementById('appeal_block').style.display='none';
		}, 100);
		var value = 1;	
		var date = new Date();
		date.setTime(date.getTime() + 14*24*60*60*1000); 
		document.cookie = "appeal="+value+"; path=/; expires=" + date.toGMTString();
		return false;
	}
	</script>
	{/literal}
</div>

	{/if}
{/dynamic}
<div id="maincontent" style="positon:relative">
