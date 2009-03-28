<html>
<head>
<title>Search Results</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
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

<h2 class="nowrap">Search Results</h2>

<p><small>Your <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" target="_main">search</a> for images<i class="nowrap">{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
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
	{searchbreak image=$image}
	
	<span class="nowrap"><a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}" target="_main" style="font-size:0.6em">{$image->grid_reference}</a> <a title="{$image->comment|escape:"html"}" href="/editimage.php?id={$image->gridimage_id}" target="_main">{$image->title|escape:'html'}</a>
		  by <a title="view user profile" href="{$image->profile_link}" target="_main">{$image->realname}</a></span><br/>
	

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
