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
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>

<script type="text/javascript" src="{"/geograph.js"|revision}"></script>
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
<div id="maincontent">
