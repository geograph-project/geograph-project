{include file="_std_begin.tpl"}

<div class="interestBox"><b>Are you involved in education?</b> We have a new <a href="/help/education_feedback">feedback form</a>, please take a moment to fill it out.</div>

<br/>

<div style="float:right;width:500px;background-color:white;position:relative;">
{literal}
<a class="twitter-timeline" href="https://twitter.com/geograph_bi" data-widget-id="527604092093202432">Tweets by @geograph_bi</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
{/literal}
</div>

<h2>Geograph Announcements</h2>

{if $news2}
{foreach from=$news2 item=newsitem}
<h3 class="newstitle" style="margin:0">{$newsitem.topic_title}</h3>
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

{else}
	There is no news right now. Check back soon, or try the <a href="/blog/">Blog</a>
{/if}

<br style="clear:both">
    
    
{include file="_std_end.tpl"}
