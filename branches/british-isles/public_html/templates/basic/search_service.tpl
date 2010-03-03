{assign var="page_title" value="Search Results"}

{include file="_basic_begin.tpl"}
<div style="text-align:right">
	<a href="/search.php?searchtext={$searchq|escape:"url"}&amp;do=1" target="_parent">Run this query in the Full Search</a>
</div>

<div id="maincontent">
{if $suggestions} 
	<b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href='search-service.php?q={$row.gr}+{$row.query}'>{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul>
{/if}

{if $images}
	<div>
	{foreach from=$images item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{/foreach}
	<br style="clear:both"/>
	</div>

{else}
	{include file="_search_noresults.tpl"}
{/if}

	{if $query_info}
	<p style="clear:both">{$query_info}</p>
	{/if}
</div>
</body>
</html>
