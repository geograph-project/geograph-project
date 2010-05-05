{if $engine->currentPage > 1}{assign var="rss_url" value="/feed/results/`$i`/`$engine->currentPage`.media"}{else}{assign var="rss_url" value="/feed/results/`$i`.media"}{/if}
{include file="_search_begin.tpl"}

{if $engine->resultCount}
	
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="100%" height="500">
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
