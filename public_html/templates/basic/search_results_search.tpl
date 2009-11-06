<html>
<head>
<title>Search Results</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
{dynamic}
<body style="background-color:{$maincontentclass|replace:"content_photo":""}"
{if $maincontentclass eq "content_photowhite"}
	text="#000000"
{/if}
{if $maincontentclass eq "content_photoblack"}
	text="#FFFFFF"
{/if}
{if $maincontentclass eq "content_photogray"}
	text="#CCCCCC"
{/if}
>
{/dynamic}

<h2>Search Results</h2>

<p><small>Your <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" target="_main">search</a> for images<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString('',' target="_self"')})</small>
	</p>

	<div>
	{foreach from=$engine->results item=image}
	
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_main">{$image->getThumbnail(120,120,false,true)}</a></div>
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
{/if}

</p>
	
</body>
</html>
