<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{if $rastermap->service == 'Google'} xmlns:v="urn:schemas-microsoft-com:vml"{/if} xml:lang="en" id="geograph">
<head>
{if $page_title}<title>{$page_title|escape:'html'} :: Geograph Prydain ac Iwerddon</title>
{else}<title>Geograph Prydain ac Iwerddon - llun o bob sgw&acirc;ar y grid!</title>{/if}
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
{if $meta_description}<meta name="description" content="{$meta_description|escape:'html'|truncate:240:"... more"}" />
{else}<meta name="description" content="Nod prosiect Geograph Prydain ac Iwerddon yw casglu lluniau a gwybodaeth ar gyfer pob cilometr sgw&acirc;r ym Mhrydain Fawr ac Iwerddon, a gallwch chi fod yn rhan o hynny."/>{/if}
{if $lat && $long}<meta name="ICBM" content="{$lat}, {$long}"/>{/if}
<meta name="DC.title" content="Geograph{if $page_title}:: {$page_title|escape:'html'}{/if}"/>
{$extra_meta}
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/charcoal/css/charcoal.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="{$static_host}/favicon.ico"/>
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
title="Chwilio Geograph Prydain ac Iwerddon" href="/stuff/osd.xml" />
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
<style type="text/css">{literal}
#tabs #nav2 {
    height: 29px;
    list-style: none;
    display: inline;	
}
#tabs #nav2 li {
	display:inline-block;
	background-color:#999;
	width:80px;
	height:22px;
	padding-top:7px;
	border-top-left-radius:10px;
	border-top-right-radius:10px;
	text-align:center;
}
#tabs #nav2 li:hover {
	background-color:#ccc;
}
#tabs #nav2 li.selected {
	background-color:white;
}

#tabs #nav2 li a {
	color:white;
	text-decoration:none;
}
#tabs #nav2 li:hover a {
	color:black;
}
#tabs #nav2 li a.selected {
	color:black;
}
{/literal}</style>
</head>

<body>

<div id="banner" style="background-image: url({$static_host}/templates/charcoal_cy/img/banner-welsh.png) !important;">
	<h1>Geograph - llun o bob sgw&acirc;r ar y grid</h1>
	<div id="profile">
		<div id="profilebar">
		{if $english_url}
		<span id="language_block">
		        [Cymraeg/<a href="{$english_url}">English</a>] &nbsp;
		</span>
		{/if}
		{dynamic}
		  {if $user->registered}
		  	  Wedi mewngofnodi fel {$user->realname|escape:'html'}
		  	  <span class="sep">|</span>
		  	  <a title="Profile" href="/profile.php">proffil</a>
		  	  <span class="sep">|</span>
		  	  <a title="Log out" href="/logout.php">allgofnodi</a>
		  {else}
			  heb fewngofnodi
			  <a title="Already registered? Login in here" href="/login.php">mewngofnodi</a>
				<span class="sep">|</span>
			  <a title="Register to upload photos" href="/register.php">cofrestru</a>
		  {/if}
		{/dynamic}
		</div>
	</div>

	<div id="search">
		<div id="searchbar">
			<form method="get" action="/finder/welsh.php">
			<input type="hidden" name="lang" value="cy"/>
			<input type="text" name="q" value="{$searchq|escape:'html'}" placeholder="(geiriau allweddol yma)"/>
			<input type="submit" value="Chwilio..."/>
			</form>
		</div>
	</div>


	<div id="tabs">
		<ul id="nav2">
		<li class=selected><a href="/?lang=cy" class="selected">Hafan</a></li>
		<li><a href="/mapper/combined.php?lang=cy">Mapiau</a></li>
		<li><a href="/submit.php">Cyflwyno</a></li>
		{if $enable_forums}<li><a href="/discuss/">Discuss</a></li>{/if}
		<li><a href="/numbers.php">Ystadegau</a></li>
		<li><a href="/faq3.php?l=0">Cymorth</a></li>
		</ul>
	</div>


</div>

<div id="breadcrumbs">
</div>
{dynamic}
<div id="content" {if $maincontentclass}class="{$maincontentclass}"{/if}>
{/dynamic}

