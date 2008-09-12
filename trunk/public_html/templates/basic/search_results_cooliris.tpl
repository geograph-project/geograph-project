{if $engine->currentPage > 1}{assign var="rss_url" value="/feed/results/`$i`/`$engine->currentPage`.media"}{else}{assign var="rss_url" value="/feed/results/`$i`.media"}{/if}
{include file="_search_begin.tpl"}
{literal}
<style type="text/css">
	.mbf-item { display: none; }
</style>
{/literal}
{if $engine->resultCount}
	<script type="text/javascript" 	src="http://lite.piclens.com/current/piclens.js"></script>

	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"html"}">Submit Yours Now</a>]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
	
	
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="760" height="450">
		<param name="movie" value="http://apps.cooliris.com/embed/cooliris.swf" />
		<param name="flashvars" value="feed={$rss_url}" />
	
		<param name="allowFullScreen" value="true" />
		<param name="allowScriptAccess" value="always" />
	
		<!--[if !IE]>-->
			<object id="coolirisSWF" type="application/x-shockwave-flash"
				data="http://apps.cooliris.com/embed/cooliris.swf" width="760" height="450">
	
			<param name="flashvars" value="feed={$rss_url}" />
			<param name="allowFullScreen" value="true" />
	
			<param name="allowScriptAccess" value="always" />
		<!--<![endif]-->
		<div><p><a href="http://www.adobe.com/go/getflashplayer">Get Adobe Flash</a></p></div>
	
		<!--[if !IE]>-->
		</object>
		<!--<![endif]-->
	</object>
	
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
