{assign var="page_title" value="Articles"}
{assign var="rss_url" value="/article/feed/recent.rss"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<div style="float:right"><a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-rss">RSS</a></div>

<h2>User Contributed Articles</h2>

<p>Click a column header to reorder, hover over a title for a brief introduction.</p>

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Category</td>
		<td>Title</td>
		<td>Author</td>
		<td>Updated</td>
		{if $isadmin}
			<td>Links</td>
		{/if}
	</tr>
</thead>
<tbody>
	{foreach from=$list item=item}
	<tr>
		<td>{$item.category_name}</td>
		<td sortvalue="{$row.title}"><b>{if $item.approved != 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved != 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}</td>
		<td style="font-size:0.9em"><a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}">{$item.realname}</a></td>
		<td sortvalue="{$item.update_time}" style="font-size:0.8em">{$item.update_time|date_format:"%a, %e %b %Y"}</td>
		{if $isadmin || $item.user_id == $user->user_id}
			<td style="font-size:0.8em">
			{if $item.locked_user} 
				Locked
			{else}
				[<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">Edit</a>]
			{/if}
		{/if} 
		{if $isadmin}
			{if $item.approved == 1}
				[<a href="/article/?page={$item.url}&amp;approve=0">Disapprove</a>]
			{else}
				[<a href="/article/?page={$item.url}&amp;approve=1">Approve</a>{if $item.approved == 0 and $item.licence != 'none'} <b>Ready to be Approved</b>{/if}]
			{/if}
			<br/>-- Version {$item.version}{if $item.modifier_id != $item.user_id} by <a href="/profile/{$item.modifier_id}" title="View Geograph Profile for {$item.modifier_realname}">{$item.modifier_realname}</a>{/if}
		{/if}
		{if $isadmin || $item.user_id == $user->user_id}
			</td>
		{/if}
	</tr>
	{/foreach}
</tbody>
</table>

</ul>
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
