{include file="_std_begin.tpl"}

{dynamic}
<h2>Welcome {$user->realname}</h2>
{/dynamic}

<p>You are logged into the site and can <a href="/submit.php">upload pictures</a> - 
you can also use the <a href="/search.php">search</a>, a <a href="/map/">map</a> (or <a href="/mapper/combined.php">new version</a>), or the 
<a href="/browse.php">browse</a> pages to find the square you want to upload.</p>

<h3>Geograph Announcements</h3>

{if $enable_forums}

	<div class="interestBox" style="margin-top:10px">
		<h3 class="newstitle" style="margin:0">&middot; Help us to improve Suggestions</h3>
	</div>
	<div class="newsbody" style="padding:5px;">
		We are running a survey of Geograph contributors and users about the Suggestions (ticket) system, seeking to understand how we can improve the system. 
		If you have a few moments, please visit: {external href="https://docs.google.com/forms/d/e/1FAIpQLSfCHv-Q4ww6Kan3hgTYQ-DjDlR2HqzDUFBwK5STdmuKcVxAFw/viewform" text="Help us to improve Suggestions" target="_blank"}.
	</div>
	<br><br>

<!--ul>
	<li><a href="/discuss/index.php?&amp;action=vthread&amp;forum=2&amp;topic=12329">Geograph needs your Help!</a></li>
</ul-->

<!--ul>
	<li><a href="/discuss/index.php?&amp;action=vthread&amp;forum=2&amp;topic=12849">Want to keep things simple?</a></li>
</ul-->
{else}
	<div class="interestBox" style="margin-top:10px">
                <h3 class="newstitle" style="margin:0">&middot; Are you involved in education?</h3>
        </div>
	<div class="newsbody" style="font-size:0.8em;padding:5px;">
		We have a <a href="/help/education_feedback">feedback form</a>, please take a moment to fill it out.
	</div>
	<br><br>

{/if}

{if $news2}

{foreach from=$news2 item=newsitem}
	<div class="interestBox" style="margin-top:10px">
		<h3 class="newstitle" style="margin:0">&middot; {$newsitem.topic_title}</h3>
	</div>
	<div class="newsbody" style="font-size:0.8em;padding:5px;">{$newsitem.post_text|replace:'http://www.geograph.org.uk/discuss/index.php?&amp;action=vthread&amp;forum=1&amp;topic=12189&amp;dontcount=1&amp;page=0#2':'http://spreadsheets.google.com/viewform?formkey=dHBnc0Q1cnRxVy10dUlrWUY4MHpqWXc6MQ'|replace:'Link in next post':'Link to the Form'}</div>
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
