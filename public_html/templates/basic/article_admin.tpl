{assign var="page_title" value="Articles"}
{assign var="rss_url" value="/article/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div style="float:right"><a title="geoRSS Feed for Geograph Pending Articles" href="/article/syndicator.php?admin=1" class="xml-geo">geo RSS</a> (unapproved articles)</div>

<h2>Articles</h2>

<ul class="explore">
{foreach from=$list item=item}
{if $lastcat != $item.category_name}
</ul>
<h3>{$item.category_name}</h3>
<ul class="explore">
{/if}
	<li><b>{if $item.approved != 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved != 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}
	<small>by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}">{$item.realname}</a></small>
		{if $isadmin || $item.user_id == $user->user_id}
			<small><small><br/>&nbsp;&nbsp;&nbsp;&nbsp; 
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
			-- Version {$item.version}{if $item.modifier_id != $item.user_id} by <a href="/profile/{$item.modifier_id}" title="View Geograph Profile for {$item.modifier_realname}">{$item.modifier_realname}</a>{/if}, updated {$item.update_time}
		{/if}
		{if $isadmin || $item.user_id == $user->user_id}
			</small></small>
		{/if}
	</li>
	{assign var="lastcat" value=$item.category_name}
{foreachelse}
	<li><i>There are no articles to display at this time.</i></li>
{/foreach}

</ul>
<div style="float:right"><a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-geo">geo RSS</a></div>
<br style="clear:both"/>

<div class="interestBox">
{if $user->registered} 
	<a href="/article/edit.php?page=new">Submit a new Article</a> (Registered Users Only)
{else}
	<a href="/login.php">Login</a> to Submit your own article!
{/if}
</div>

{include file="_std_end.tpl"}
