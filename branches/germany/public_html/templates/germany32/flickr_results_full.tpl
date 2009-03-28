{assign var="page_title" value="Flickr Search Results"}
{include file="_std_begin.tpl"}

<h2>Flickr Search Results</h2>
{dynamic}
<p>Your search for flickr photos<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
<b>{$engine->resultCount}</b> images:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) [<a href="flickr.php?i={$i}&amp;form=simple">search again</a>]
	</p>
<!--{if $nofirstmatch}
<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>]</p>
{/if}-->
	{foreach from=$engine->results item=image}
	  <div style="clear:both">
		<div style="float:left; position: relative; width:130px">
		<div align="center">
		<a title="{$image.title|escape:'html'} - click to view full size image" href="http://www.flickr.com/photo.gne?id={$image->id}"><img src="http://photos{$image.server}.flickr.com/{$image.id}_{$image.secret}_t.jpg" alt="{$image.title|escape:'html'}" border="0"></a>
		</div>
		</div>
	  <a title="view full size image" href="http://www.flickr.com/photo.gne?id={$image.id}">{$image.title|escape:'html'}</a>
	  by <a title="view user profile" href="http://www.flickr.com/photos/{$image.owner}/">{$image.realname|default:'-<i>unknown</i>-'}</a> <br/>
	  {if $image.isgeograph}geograph{/if} for square <a title="view page for {$image.grid_reference}" href="/gridref/{$image.grid_reference}">{$image.grid_reference}</a>

	  <i>{$image.dist_string}</i><br/><br/>
	  
	</div>


	{/foreach}

	<p style="clear:both">( Page {$engine->pagesString()})
{/if}
</p>
{/dynamic}		
{include file="_std_end.tpl"}
