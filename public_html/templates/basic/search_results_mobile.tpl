<html>
<head>
<title>Search Results</title>
 <meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
</head>
{dynamic}
<body {if $maincontentclass}class="{$maincontentclass}" style="margin:2;border:0"{/if}>
{/dynamic}

<div style="background-color:#000066">
<a target="_top" href="https://www.geograph.org.uk/"><img src="{$static_host}/templates/basic/img/logo.gif" height="50"></a>
- <a href="/search.php?i={$i}&amp;displayclass=full" style="color:white">View Desktop Results</a>
</div>

<h2>Search Results <small><a href="/search.php?i={$i}&amp;displayclass=slidebig">View as Slideshow</a></small></h2>

<p><small>Your search for images<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString('',' target="_self"')})</small>  {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>

	<div>
	{foreach from=$engine->results item=image}
	
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="https://m.geograph.org.uk/photo/{$image->gridimage_id}" target="_main">{$image->getThumbnail(120,120,false,true)|replace:'src=':'src="/img/blank.gif" data-src='}</a></div>
	  </div>

	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	<br style="clear:both"/>
	</div>
	{if $engine->results}
		<p><small>Search took {$querytime|string_format:"%.2f"} secs, <br/>( Page {$engine->pagesString('',' target="_self"')})</small>
	{/if}
{else}
	 {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
{/if}

</p>

<hr>
- <a href="https://www.geograph.org.uk/">Homepage</a>
- <a href="https://www.geograph.org.uk/search.php?i={$i}&amp;displayclass=full">View Desktop Results</a>
- <a href="/search.php?i={$i}&amp;displayclass=slidebig">View as Slideshow</a>

<script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>

</body>
</html>
