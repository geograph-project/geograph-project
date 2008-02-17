<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{if $rastermap->service == 'Google'} xmlns:v="urn:schemas-microsoft-com:vml"{/if} xml:lang="en" id="geograph">
<head>
{if $page_title}<title>{$page_title|escape:'html'} :: Geograph British Isles - photograph every grid square!</title>
{else}<title>Geograph British Isles - photograph every grid square!</title>{/if}
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'|truncate:240:"... more"}" />
{else}<meta name="description" content="Geograph British Isles is a web based project to collect and reference geographically representative images of every square kilometre of the British Isles."/>{/if}
{if $lat && $long}<meta name="ICBM" content="{$lat}, {$long}"/>{/if}
<meta name="DC.title" content="Geograph{if $page_title}:: {$page_title|escape:'html'}{/if}"/>
{$extra_meta}
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/charcoal/css/charcoal.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
{if $image && $image->gridimage_id && $image->moderation_status ne 'rejected'}
<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/photo/{$image->gridimage_id}.kml"/>
{elseif $profile && $profile->user_id}
<link rel="alternate" type="application/rss+xml" title="Geograph RSS for {$profile->realname}" href="/feed/userid/{$profile->user_id}.rss"/>
<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/feed/userid/{$profile->user_id}.kml"/>
{elseif $engine && $engine->resultCount}
<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss"/>
<link rel="alternate" type="application/vnd.google-earth.kml+xml" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.kml"/>
{else}
<link rel="alternate" type="application/rss+xml" title="Geograph RSS" href="/feed/recent.rss"/>
{/if}
<link rel="search" type="application/opensearchdescription+xml" 
title="Geograph British Isles search" href="/stuff/osd.xml" />
<script type="text/javascript" src="{"/geograph.js"|revision}"></script>

<script type="text/javascript">
{literal}
var onloads = new Array();
function bodyOnLoad() 
{
	for ( var i = 0 ; i < onloads.length ; i++ )
	{
    	onloads[i]();
    }
}
{/literal}
</script>

</head>

<body onload="bodyOnLoad()">

<div id="banner">
	<h1>Geograph - photograph every grid square</h1>
	<div id="profile">
		<div id="profilebar">
		{dynamic}
		  {if $user->registered}
		  	  Logged in as {$user->realname|escape:'html'}
		  	  <span class="sep">|</span>
		  	  <a title="Profile" href="/profile/{$user->user_id}">profile</a>
		  	  <span class="sep">|</span>
		  	  <a title="Log out" href="/logout.php">logout</a>
		  {else}
			  You are not logged in
			  <a title="Already registered? Login in here" href="/login.php">login</a>
				<span class="sep">|</span>
			  <a title="Register to upload photos" href="/register.php">register</a>
		  {/if}
		{/dynamic}
		</div>
	</div>

	<div id="search">
		<div id="searchbar">
			<form method="get" action="/search.php">
			<input type="hidden" name="form" value="simple"/>
			Search <input id="q" type="text" name="q" value="{$searchq|escape:'html'}"/>
			<input id="b_sea" type="submit" name="search" value=""/>
			</form>
		</div>
	</div>


	<div id="tabs">
		<ul id="nav">
		<li id="t_hom"><a href="/" class="selected">Home</a></li>
		<li id="t_map"><a href="/mapbrowse.php">Map</a></li>
		<li id="t_sub"><a href="/submit.php">Submit</a></li>
		{if $enable_forums}<li id="t_dis"><a href="/discuss/">Discuss</a></li>{/if}
		<li id="t_sta"><a href="/numbers.php">Stats</a></li>
		<li id="t_hel"><a href="/faq.php">Help</a></li>
		</ul>
	</div>


</div>

<div id="breadcrumbs">
</div>

<div id="content" {if $maincontentclass}class="{$maincontentclass}"{/if}>


