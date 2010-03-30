{assign var="page_title" value="Articles"}
{assign var="meta_description" value="User contributed articles, combining Geograph photos with maps and more."}
{assign var="rss_url" value="/article/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div style="float:right"><a title="geoRSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-rss">RSS</a> {external href="http://maps.google.co.uk/maps?q=http://`$http_host`/article/feed/recent.rss" text="Map"}</div>

<div class="tabHolder">
	<a href="/content/" class="tab">Content</a>
	<span class="tabSelected">Articles</span>
	<a href="/article/?table" class="tab">Article List</a>
	<a href="/gallery/" class="tab">Galleries</a>
	{if $enable_forums}
		<a href="/discuss/index.php?action=vtopic&amp;forum={$forum_submittedarticles}" class="tab">Themed Topics</a>
		<a href="/discuss/index.php?action=vtopic&amp;forum={$forum_gridsquare}" class="tab">Grid Square Discussions</a>
		<a href="http://www.geograph.org.uk/article/Content-on-Geograph" class="tab">Contribute...</a>
	{/if}	
</div>
<div class="interestBox">
<h2>User Contributed Articles</h2>
</div>

{if $desc}
	<div style="position:relative; float:right; background-color:silver; padding:4px">
		Showing <b>articles{$desc|escape:'html'}</b> / <a href="/article/">Show all</a> 
	</div>
{else}
	{dynamic}{if $article_count}
		<div style="position:relative; float:right; background-color:silver; padding:4px">
			<a href="/article/?user_id={$user->user_id}">show only yours [{$article_count}]</a>
		</div>
	{/if}{/dynamic}
{/if}

{if $user->registered} 
<div class="interestBox">
	<ul style="margin:0px;"><li><a href="/article/edit.php?page=new">Create your own Article</a></li></ul>
</div>
{/if}

{assign var="lastid" value="0"}
{foreach from=$list item=item}
{if $lastcat != $item.category_name}
{if $lastcat}
</ul>
</div>
{cycle values=",<br style='clear:both'/>"}
{/if}
<div style="float:left;width:46%;position:relative; padding:5px;">
<h3>{$item.category_name}</h3>
<ul class="content">
{assign var="lastname" value=""}
{/if}
	<li><b>{if $item.approved < 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved < 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}<br/>
	<small id="att{$lastid+1}"><small style="color:lightgrey">by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a>
		{if (($item.user_id == $user->user_id) || $item.approved == 2) && !$item.locked_user}
			&nbsp;&nbsp;&nbsp;&nbsp; 
			[<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">Edit</a>] [<a title="Edit History for {$item.title}" href="/article/history.php?page={$item.url}">History</a>]
		{/if} 
		
		</small></small>

	</li>
	{if $lastname == $item.realname && $user->realname != $lastname}
		<script>document.getElementById('att{$lastid}').style.display='none'</script>
	{/if}
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.category_name}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no articles to display at this time.</i></li>
{/foreach}

</ul>
</div>
<br style="clear:both"/>

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
