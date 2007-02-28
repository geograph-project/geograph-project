{assign var="page_title" value="Articles"}

{include file="_std_begin.tpl"}

<h2>Articles</h2>

<ul class="explore">
{foreach from=$list item=item}

	<li><b>{if $item.approved != 1 || $item.licence == 'none'}<s>{/if}<a title="View {$item.title}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved != 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}
	<small>by <a href="/profile.php?u={$item.user_id}" title="View Geograph Profile for {$item.realname}">{$item.realname}</a></small>
		{if $isadmin || $item.user_id == $user->user_id}
			<small><small><br/>&nbsp;&nbsp;&nbsp;&nbsp; 
			[<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">Edit</a>]
		{/if} 
		{if $isadmin}
			{if $item.approved == 1}
				[<a href="/article/?page={$item.url}&amp;approve=0">Disapprove</a>]
			{else}
				[<a href="/article/?page={$item.url}&amp;approve=1">Approve</a>{if $item.approved == 0 and $item.licence != 'none'} <b>Ready to be Approved</b>{/if}]
			{/if}
		{/if}
		{if $isadmin || $item.user_id == $user->user_id}
			</small></small>
		{/if}
	</li>
{foreachelse}
	<li><i>There are no articles to display at this time.</i></li>
{/foreach}

</ul>

<div class="interestBox">
{if $user->registered} 
	<a href="/article/edit.php?page=new">Submit a new Article</a> (Registered Users Only)
{else}
	<a href="/login.php">Login</a> to Submit your own article!
{/if}
</div>

{include file="_std_end.tpl"}
