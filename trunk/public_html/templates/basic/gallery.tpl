{assign var="page_title" value="Galleries"}
{assign var="rss_url" value="/discuss/syndicator.php?forum=11"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
ul.explore li {	padding:3px; }
</style>{/literal}

<div style="float:right"><a title="RSS Feed for Geograph Galleries" href="/discuss/syndicator.php?forum=11" class="xml-rss">RSS</a></div>

<h2>Themed Galleries</h2>

<ul class="explore">
{foreach from=$list item=item}


	<li><b><a  href="/gallery/{$item.url}">{$item.topic_title}</a></b><br/>
	<small><small style="color:gray">by <a href="/profile/{$item.topic_poster}" title="View Geograph Profile for {$item.topic_poster_name}"  style="color:#6699CC">{$item.topic_poster_name}</a>, with {$item.posts_count} posts, {$item.images_count} images and viewed {$item.topic_views} times.</small></small>

	</li>


{foreachelse}
	<li><i>There are no galleries to display at this time.</i></li>
{/foreach}

</ul>


<br style="clear:both"/>

<div style="text-align:right"><a title="RSS Feed for Geograph Galleries" href="/discuss/syndicator.php?forum=11" class="xml-rss">RSS</a></div>

{if $enable_forums}
	<div class="interestBox">
	{if $user->registered} 
		<a href="/discuss/?action=vtopic&forum=11">Submit a new Gallery</a> (Registered Users Only)
	{else}
		<a href="/login.php">Login</a> to Submit your own gallery!
	{/if}
	</div>
{/if}

{include file="_std_end.tpl"}
