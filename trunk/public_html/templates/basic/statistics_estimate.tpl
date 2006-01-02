{assign var="page_title" value="Geograph Database Statistics"}
{include file="_std_begin.tpl"}

<h2>Geograph Database Estimates{if $ri} for {$references.$ri}{/if}</h2>

    <form method="get" action="{$script_name}">
    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    <input type="submit" value="Go"></p></form>

<p>See <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details of the various measures.</p>

<h3>Points</h3>
<p>We currently have {$point.count|thousends} points awarded, therefore at the current rate of {$point.average_r} points a day, we will reach {$point.next|thousends} in about {$point.days_r} days time!</p>

<p>Furthermore at the current rate of {$totall.average_r|thousends} points a week, we will reach {$totall.next|thousends} (full coverage) in about {$totall.weeks_r} weeks time, or {$totall.enddate}!</p>


<h3>Geographs</h3>
<p>We currently have {$geograph.count|thousends} geographs, therefore at the current rate of {$geograph.average_r} geographs a day, we will reach {$geograph.next|thousends} in about {$geograph.days_r} days time!</p>


<h3>Squares</h3>
<p>We currently have {$square.count|thousends} squares photographed, therefore at the current rate of {$square.average_r} squares a day, we will reach {$square.next|thousends} in about {$square.days_r} days time!</p>


<h3>Images</h3>
<p>We currently have {$image.count|thousends} images, therefore at the current rate of {$image.average_r} submissions a day, we will reach {$image.next|thousends} in about {$image.days_r} days time!</p>


<hr/>

{if $ri}<h2>Geograph Database Estimates</h2>{/if}

<h3>Users</h3>
<p>We currently have {$users.count|thousends} users, therefore at the current rate of {$users.average_r} users signing up a day, we will reach {$users.next|thousends} in about {$users.days_r} days time!</p>

<p>We currently have {$cusers.count|thousends} contributing users, therefore at the current rate of {$cusers.average_r} users signing up a day (who later contribute), we will reach {$cusers.next|thousends} in about {$cusers.days_r} days time!</p>


<h3>Forum Posts</h3>
<p>We currently have {$post.count|thousends} posts, therefore at the current rate of {$post.average_r} posts a day, we will reach {$post.next|thousends} in about {$post.days_r} days time!</p>


<h3>Image Searches</h3>
<p>Visitors have performed {$searches.count|thousends} searches, therefore at the current rate of {$searches.average_r} searches a day, we will reach {$searches.next|thousends} in about {$searches.days_r} days time!</p>



<p style="font-size:0.8em">* current rate is based on the average for the past 7 days.</p>

{include file="_std_end.tpl"}
