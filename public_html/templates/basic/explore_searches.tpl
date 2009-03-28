{assign var="page_title" value="Explore Featured Searches"}
{assign var="rss_url" value="/explore/searches.rss.php"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>


<div style="float:right; padding-right:30px;">Feed of new searches -&gt; <a title="RSS Feed Featured Searches" href="{$rss_url}" class="xml-rss">RSS</a></div>

<h2>Featured Searches <small><a href="?table">View as table</a></small></h2>


<ul class="content">
{foreach from=$queries item=row}


	<li{if !$row.approved} style="color:gray;text-decoration:line-through"{/if}>
	<div style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
		{if $row.image}
		<a title="{$row.image->title|escape:'html'} by {$row.image->realname} - click to view full size image" href="/photo/{$row.image->gridimage_id}" target="_top">{$row.image->getSquareThumbnail(60,60)}</a>
		{/if}
	</div>
	<b><a href="/results/{$row.id}" target="_top">{$row.searchdesc|escape:'html'}</a></b><br/>
	<small><small style="color:gray">{if $row.count}with about {$row.count|thousends} results.{/if} 
	added {$row.created|date_format:"%a, %e %b %Y"}
	{if $is_mod}
		{if $row.approved}
			<a href="{$script_name}?i={$row.id}&amp;a=0">Unpprove</a>
		{else}
			<a href="{$script_name}?i={$row.id}&amp;a=1">Approve</a>
		{/if}
	{/if}
	
	
	{if $row.orderby == 'gridimage_id desc' || $row.orderby == 'submitted desc' || $row.orderby == 'post_id desc,seq_id desc' || $row.orderby == 'showday desc'}
		<br/><a href="/feed/results/{$row.id}.rss?expand=1" class="xml-rss" title="RSS feed for {$row.searchdesc|escape:'html'}">RSS</a>	&lt;- feed of new images in this search
	{/if}
	</small></small>
	<div style="clear:both"></div>
	</li>


{foreachelse}
	<li><i>There is no content to display at this time.</i></li>
{/foreach}
</ul>


	
<p>Suggest a new search for this page using the link on the search results page.</p>

<p>Note: Searches without a RSS icon, are not sorted in descending order which is required for RSS feeds to work well.</p>

{if $footnote}
	{$footnote}
{/if}


{include file="_std_end.tpl"}
