{assign var="page_title" value="Explore Featured Searches"}
{assign var="rss_url" value="/explore/searches.rss.php"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>


<div style="float:right; padding-right:30px;"><a title="RSS Feed Featured Searches" href="{$rss_url}" class="xml-rss">RSS</a></div>

<h2>Featured Searches</h2>



	
<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
<thead><tr>
	<td>Title</td>
	<td>Suggested</td>
	{if $is_mod}
		<td>Links</td>
	{/if}
	<td>Feed</td>
</tr></thead>
<tbody>

{foreach from=$queries item=row}
<tr{if !$row.approved} style="color:gray;text-decoration:line-through"{/if}>
	<td><a href="/results/{$row.id}">images{$row.searchdesc|escape:'html'}</a> {if $row.count}[{$row.count|thousends}]{/if}{if $row.comment}<br/><small><small>{$row.comment|escape:'html'}</small></small>{/if}</td>
	<td sortvalue="{$row.created}" style="font-size:0.8em">{$row.created|date_format:"%a, %e %b %Y"}</td>
	{if $is_mod}
	<td style="font-size:0.6em;">
		{if $row.approved}
			<a href="{$script_name}?i={$row.id}&amp;a=0">Unpprove</a>
		{else}
			<a href="{$script_name}?i={$row.id}&amp;a=1">Approve</a>
		{/if}
	</td>
	{/if}
	{if $row.orderby == 'gridimage_id desc' || $row.orderby == 'submitted desc' || $row.orderby == 'post_id desc,seq_id desc' || $row.orderby == 'showday desc'}
		<td><a href="/feed/results/{$row.id}.rss?expand=1" class="xml-rss" title="RSS feed for {$row.searchdesc|escape:'html'}">RSS</a></td>
	{else}
		<td>n/a</td>
	{/if}
</tr>
{/foreach}

</tbody>
</table>

	
<p>Suggest a new search for this page using the link on the search results page.</p>

<p>Note: Searches without a link in the RSS column, are not sorted in descending order which is required for RSS feeds to work well.</p>

{if $footnote}
	{$footnote}
{/if}


{include file="_std_end.tpl"}
