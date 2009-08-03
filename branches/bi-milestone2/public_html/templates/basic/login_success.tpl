{include file="_std_begin.tpl"}

{dynamic}
<h2>Welcome {$user->realname}</h2>
{/dynamic}

<p>You are logged into the site and can <a href="/submit.php">upload pictures</a> - 
you can also use the <a href="/search.php">search</a>, a <a href="/map/">map</a>, or the 
<a href="/browse.php">browse</a> pages to find the square you want to upload.</p>



{if $news2}
<br/><br/>
<div class="interestBox" style="font-size:0.8em">
<h2>Geograph Announcements</h2>
{foreach from=$news2 item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter" style="border-top:1px solid lightgrey;font-size:0.8em;padding-top:10px;">
Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>
{/foreach}
{if $rss_url}
<div style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">
<a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a>
</div>
{/if}

</div>
{/if}


    
    
{include file="_std_end.tpl"}
