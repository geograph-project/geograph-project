{assign var="page_title" value="Geograph Database Statistics"}
{include file="_std_begin.tpl"}

<h2>Geograph Database Estimates{if $ri} for {$references.$ri}{/if}</h2>

    <form method="get" action="{$script_name}">
    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    <input type="submit" value="Go"></p></form>

<p>See <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details of the various measures. Will be more accurate when limited to one country.</p>

<hr/>

<h3>Points</h3>
<p>We currently have {$point.count|thousends} points awarded, therefore at the current rate of {$point.average_r} points a day, we will reach {$point.next|thousends} in about {$point.days_r} days time!</p>

<p>Furthermore at the current rate of {$totall.average_r|thousends} points a week, we will reach {$totall.next|thousends} (full coverage) in about {$totall.weeks_r} weeks time, or {$totall.enddate}!</p>


<h3>Geographs</h3>
<p>We currently have {$geograph.count|thousends} geographs, therefore at the current rate of {$geograph.average_r} geographs a day, we will reach {$geograph.next|thousends} in about {$geograph.days_r} days time!</p>


<h3>Images</h3>
<p>We currently have {$image.count|thousends} images, therefore at the current rate of {$image.average_r} submissions a day, we will reach {$image.next|thousends} in about {$image.days_r} days time!</p>

<h3>Hectads</h3>
<p>We currently have {$hectad.count|thousends} completed hectads, therefore at the current rate of {$hectad.average_r} hectads a day, we will reach {$hectad.next|thousends} in about {$hectad.days_r} days time!</p>


<hr/>

{if $ri}<h2>Geograph Database Estimates</h2>{/if}

<h3>Users</h3>
<p>We currently have {$users.count|thousends} users, therefore at the current rate of {$users.average_r} users signing up a day, we will reach {$users.next|thousends} in about {$users.days_r} days time!</p>

<p>We currently have {$cusers.count|thousends} contributing users, therefore at the current rate of {$cusers.average_r} users signing up a day (who later contribute), we will reach {$cusers.next|thousends} in about {$cusers.days_r} days time!</p>


<h3>Forum Posts</h3>
<p>We currently have {$post.count|thousends} posts, therefore at the current rate of {$post.average_r} posts a day, we will reach {$post.next|thousends} in about {$post.days_r} days time!</p>

<h3>Change Suggestions</h3>
<p>Contributors have created {$ticket.count|thousends} tickets, therefore at the current rate of {$ticket.average_r} new tickets a day, we will reach {$ticket.next|thousends} in about {$ticket.days_r} days time!</p>


<h3>New Image Searches</h3>
<p>Visitors have created {$searches.count|thousends} searches, therefore at the current rate of {$searches.average_r} new searches a day, we will reach {$searches.next|thousends} in about {$searches.days_r} days time!</p>


<hr/>

<p style="font-size:0.8em">* current rate is based on the average for the past 7 days.</p>

<hr/>

<h3>Raw Data used in above calculations</h3>

	<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
	<thead><tr>
	{foreach from=$table.0 key=name item=value}
	<td style="direction: rtl; writing-mode: tb-rl;">{$name}</td>
	{/foreach}

	</tr></thead>
	<tbody>

	{foreach from=$table item=row}
	<tr>
		{foreach from=$row key=name item=value}
			<td align="right">{$value}</td>
		{/foreach}
	</tr>
	{/foreach}	

	</tbody>
	</table>

{include file="_std_end.tpl"}
