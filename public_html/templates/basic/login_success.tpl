{include file="_std_begin.tpl"}

{dynamic}
<h2>Welcome {$user->realname}</h2>
{/dynamic}

<p>You are logged into the site and can <a href="/submit.php">upload pictures</a> - 
you can also use the <a href="/search.php">search</a>, a <a href="/map/">map</a>, or the 
<a href="/browse.php">browse</a> pages to find the square you want to upload.</p>


<div class="interestBox" style="margin:20px"><b>Are you involved in education?</b> We have a new <a href="/help/education_feedback">feedback form</a>, please take a moment to fill it out.</div>

{if $enable_forums}
<!--ul>
	<li><a href="/discuss/index.php?&amp;action=vthread&amp;forum=2&amp;topic=12329">Geograph needs your Help!</a></li>
</ul-->

<!--ul>
	<li><a href="/discuss/index.php?&amp;action=vthread&amp;forum=2&amp;topic=12849">Want to keep things simple?</a></li>
</ul-->
{/if}

{if $news2}
<br/><br/>
<h2>Geograph Announcements</h2>
{foreach from=$news2 item=newsitem}
<div class="interestBox" style="margin-top:10px">
<h3 class="newstitle" style="margin:0">{$newsitem.topic_title}</h3>
</div>
<div class="newsbody" style="font-size:0.8em">{$newsitem.post_text|replace:'http://www.geograph.org.uk/discuss/index.php?&amp;action=vthread&amp;forum=1&amp;topic=12189&amp;dontcount=1&amp;page=0#2':'http://spreadsheets.google.com/viewform?formkey=dHBnc0Q1cnRxVy10dUlrWUY4MHpqWXc6MQ'|replace:'Link in next post':'Link to the Form'}</div>
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

{/if}


    
    
{include file="_std_end.tpl"}
