{assign var="page_title" value="Articles"}
{assign var="rss_url" value="/article/feed/recent.rss"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<div style="float:right"><a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-rss">RSS</a> {external href="http://maps.google.co.uk/maps?q=http://`$http_host`/article/feed/recent.rss" text="Map"}</div>

<div class="tabHolder">
	<a href="/content/" class="tab">Content</a>
	<a href="/article/" class="tab">Articles</a>
	<span class="tabSelected">Article List</span>
	<a href="/gallery/" class="tab">Galleries</a>
	{if $enable_forums}
		<a href="/discuss/index.php?action=vtopic&amp;forum=6" class="tab">Themed Topics</a>
		<a href="/discuss/index.php?action=vtopic&amp;forum=5" class="tab">Grid Square Discussions</a>
		<a href="/article/Content-on-Geograph" class="tab">Contribute...</a>
	{/if}	
</div>
<div class="interestBox">
<h2>User Contributed Articles</h2>
</div>
{if $desc}
	<div style="position:relative; float:right; background-color:silver; padding:4px">
		Showing <b>articles{$desc|escape:'html'}</b> / <a href="/article/?table">Show all</a> 
	</div>
{else}
	{dynamic}{if $article_count}
		<div style="position:relative; float:right; background-color:silver; padding:4px">
			<a href="/article/?table&amp;user_id={$user->user_id}">show only yours [{$article_count}]</a>
		</div>
	{/if}{/dynamic}
{/if}


<p>Click a column header to reorder, hover over a title for a brief introduction.</p>

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Category</td>
		<td>Title</td>
		<td>Author</td>
		<td>Updated</td>
		<td>Links</td>
	</tr>
</thead>
<tbody>
	{foreach from=$list item=item}
	<tr>
		<td>{$item.category_name}</td>
		<td sortvalue="{$item.title}"><b>{if $item.approved < 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved < 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}</td>
		<td style="font-size:0.9em"><a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}">{$item.realname}</a></td>
		<td sortvalue="{$item.update_time}" style="font-size:0.8em">{$item.update_time|date_format:"%a, %e %b %Y"}</td>
		<td style="font-size:0.8em">
		{if $isadmin || $item.user_id == $user->user_id}
			{if $item.locked_user} 
				Locked
			{else}
				[<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">Edit</a>]
			{/if}
		{/if} 
		[<a title="Edit History for {$item.title}" href="/article/history.php?page={$item.url}">History</a>]
		{if $isadmin}
			{if $item.approved > 0}
				[<a href="/article/?page={$item.url}&amp;approve=0">Disapprove</a>]
			{else}
				[<a href="/article/?page={$item.url}&amp;approve=1">Approve</a>{if $item.approved == 0 and $item.licence != 'none'} <b>Ready to be Approved</b>{/if}]
			{/if}
			<br/>-- Version {$item.version}{if $item.modifier_id != $item.user_id} by <a href="/profile/{$item.modifier_id}" title="View Geograph Profile for {$item.modifier_realname}">{$item.modifier_realname}</a>{/if}
		{/if}
		</td>
	</tr>
	{/foreach}
</tbody>
</table>


<div style="float:right"><a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-geo">geoRSS</a></div>
<br style="clear:both"/>

<div class="interestBox">
{if $user->registered} 
	<a href="/article/edit.php?page=new">Submit a new Article</a> (Registered Users Only)
{else}
	<a href="/login.php">Login</a> to Submit your own article!
{/if}
</div>

{include file="_std_end.tpl"}
