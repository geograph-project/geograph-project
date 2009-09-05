
<div style="text-align:right">
	<a href="/search.php?q={$searchq|escape:"url"}" target="_blank">Run this query in the Full Search</a>
</div>

{if $suggestions} 
	<b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href='search-service.php?q={$row.gr}+{$row.query}'>{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul>
{/if}

{if $images}
	{foreach from=$images item=image}
	  <div class="imageBox" id="imageBox{$image->gridimage_id}">
		<div class="imageBox_theImage" style="background-image:url('{$image->getThumbnail(120,120,true)}')" title="by {$image->realname|escape:'html'}"></div>
		<div class="imageBox_label"><span>{$image->title|escape:'html'}</span></div>
	
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

