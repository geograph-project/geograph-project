{assign var="page_title" value="Geograph Map"}

{include file="_basic_begin.tpl"}
{dynamic}
{if $error} 
	<p>ERROR: {$error}</p>
{else}
	{if $rastermap->enabled}
		
		{$rastermap->getImageTag($gridref)}

		{$rastermap->getScriptTag()}
		
		{$rastermap->getFooterTag()}
	{/if}
{/if}
{/dynamic}
</body>
</html>
