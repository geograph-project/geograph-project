{assign var="page_title" value="Articles"}
{assign var="rss_url" value="/article/feed/recent.rss"}
{include file="_std_begin.tpl"}
<style>{literal}
.content small a {
	color:gray;
}
.content small {
	color:silver;
}
{/literal}</style>
<div class="tabHolder">
	<a href="/content/" class="tab">Collections</a>
	<span class="tabSelected">Articles</span>
	<a href="/article/?table" class="tab">Article List</a>
	<a href="/gallery/" class="tab">Galleries</a>
	{if $enable_forums}
		<a href="/discuss/index.php?action=vtopic&amp;forum=6" class="tab">Themed Topics</a>
		<a href="/discuss/index.php?action=vtopic&amp;forum=5" class="tab">Grid Square Discussions</a>
		<a href="/article/Content-on-Geograph" class="tab">Contribute...</a>
	{/if}
</div>
<div class="interestBox">
<h2 style="margin:0">User Contributed Articles</h2>
</div>
{if $desc}
	<div style="position:relative; float:right; background-color:silver; padding:4px">
		Showing <b>articles{$desc|escape:'html'}</b> / <a href="/article/">Show all</a>
	</div>
{else}
	{dynamic}{if $article_count && !$linktofull}
		<div style="position:relative; float:right; background-color:silver; padding:4px">
			<a href="/article/?user_id={$user->user_id}">show only yours [{$article_count}]</a>
		</div>
	{/if}{/dynamic}
{/if}

<form action="/content/" method="get">
<div class="interestBox" style="margin-top:2px;width:420px">
<lable for="qs">Search:</label>
<input type="text" name="q" id="qs" size="22" {if $q} value="{$q|escape:'html'}"{/if}/>
Scope: <select name="scope" style="width:80px">
	<option value="">All</option>
	<option value="article" selected>Articles</option>
	<option value="gallery">Galleries</option>
	{dynamic}
	  {if $enable_forums && $user->registered}
		  <option value="themed">Themed Topics</option>
	  {/if}
	{/dynamic}
	<option value="help">Help Pages</option>
	<option value="document">Information Pages</option>
</select>
<input type="submit" value="Find"/>
</div>
</form>

{if $linktofull}
<div class="interestBox" style="text-align:center;background-color:yellow">
	<b>Content summary only</b> - <a href="?full">View full list</a>
</div>
{/if}

<ul class="explore">
{foreach from=$list item=item}
{if $lastcat != $item.category_name}
</ul>
<h3>{$item.category_name}</h3>
<ul class="content">
{/if}
	<li><b>{if $item.approved < 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved < 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}
	<small>by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}">{$item.realname}</a></small>
		{if $isadmin || $item.user_id == $user->user_id}
			<small><small><br/>&nbsp;&nbsp;&nbsp;&nbsp;
			{if $item.locked_user}
				Locked
			{else}
				[<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">Edit</a>]
			{/if}
			[<a title="Edit History for {$item.title}" href="/article/history.php?page={$item.url}">History</a>]
		{/if}
		{if $isadmin}
			{if $item.approved > 0}
				[<a href="/article/?page={$item.url}&amp;approve=0">Disapprove</a>]
				{if $item.approved != 2 && $item.edit_prompt}
					[<a href="/article/?page={$item.url}&amp;approve=2">Enable Collaborative Editing</a>]
				{/if}
			{else}
				[<a href="/article/?page={$item.url}&amp;approve=1">Approve</a>{if $item.approved == 0 and $item.licence != 'none'}
				{if $item.approved != 2 && $item.edit_prompt}
					[<a href="/article/?page={$item.url}&amp;approve=2">Enable Collaborative Editing</a>]
				{/if}
				<b>Ready to be Approved</b>{/if}]
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
<div style="float:right">Articles: <a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-geo">geo RSS</a>

Admin Feed: <a title="geoRSS Feed for Geograph Pending Articles" href="/article/syndicator.php?admin=1" class="xml-geo">geo RSS</a></div>

</div>
<br style="clear:both"/>

{if $linktofull}
<div class="interestBox" style="text-align:center;background-color:yellow">
	<b>This is a summary list only</b> - <a href="?full">View full list</a>
</div>
{/if}

<div class="interestBox">
{if $user->registered}
	<a href="/article/edit.php?page=new">Submit a new Article</a> (registered users only)
{else}
	<a href="/login.php">Login</a> to submit your own article!
{/if}
</div>

{include file="_std_end.tpl"}
