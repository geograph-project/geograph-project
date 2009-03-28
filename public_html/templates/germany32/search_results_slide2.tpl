{include file="_search_begin.tpl"}
{if $engine->resultCount}
	
	<embed type="application/x-shockwave-flash" src="http://picasaweb.google.com/s/c/bin/slideshow.swf" width="288" height="192" flashvars="host={$http_host}&RGB=0x000000&feed=http%3A%2F%2F{$http_host}%2Ffeed%2Fresults%2F{$i}{if $engine->currentPage > 1}%2F{$engine->currentPage}{/if}.media" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
	
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
