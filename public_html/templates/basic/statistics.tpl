{assign var="page_title" value="Statistics"}
{include file="_std_begin.tpl"}
 
    <h2>Overview Statistics for Geograph British Isles</h2>

<ul>
<li><b>{$users_submitted}</b> Users have Submitted Images</li>
<li><b>{$users_thisweek}</b> new Users in past 7 days</li>
<li>A total of <b>{$images_ftf}</b> Geograph Points have been awarded</li>
</ul>

<table border="1" cellpadding="4" cellspacing="0" class="statstable">
<thead><tr>
	<th>&nbsp;</th>
	<th>Great Britain</th>
	<th>Ireland</th>
	<th><i>Total</i></th>
</tr></thead>
<tbody><tr>
	<th>Images Submitted</th>
	<td><b>{$images_total_1|thousends}</b></td>
	<td><b>{$images_total_2|thousends}</b></td>
	<td>{$images_total_both|thousends}</td>
</tr>
<tr>
	<th>&nbsp;[in past 7 days]</th>
	<td><b>{$images_thisweek_1|thousends}</b></td>
	<td><b>{$images_thisweek_2|thousends}</b></td>
	<td>{$images_thisweek_both|thousends}</td>
</tr>
<tr>
	<th>1km<sup>2</sup> Grid Squares</th>
	<td><b>{$squares_submitted_1|thousends}</b><br/>/{$squares_total_1|thousends}</td>
	<td><b>{$squares_submitted_2|thousends}</b><br/>/{$squares_total_2|thousends}</td>
	<td valign="top">{$squares_submitted_both|thousends}<br/>/{$squares_total_both|thousends}</td>
</tr>
<tr>
	<th>&nbsp;[as percentage]</th>
	<td><b>{$percent_1}%</b></td>
	<td><b>{$percent_2}%</b></td>
	<td>{$percent_both}%</td>
</tr>
<tr>
	<th>&nbsp;[with geograph(s)]</th>
	<td><b>{$geographs_submitted_1|thousends}</b></td>
	<td><b>{$geographs_submitted_2|thousends}</b></td>
	<td>{$geographs_submitted_both|thousends}</td>
</tr>
<tr>
	<th>Hectads<a href="/help/squares">?</a><br/>10km x 10km Squares</th>
	<td><b>{$tenk_submitted_1|thousends}</b><br/>/{$tenk_total_1|thousends}</td>
	<td><b>{$tenk_submitted_2|thousends}</b><br/>/{$tenk_total_2|thousends}</td>
	<td valign="top">{$tenk_submitted_both|thousends}<br/>/{$tenk_total_both|thousends}</td>
</tr>
<tr>
	<th>Myriads<a href="/help/squares">?</a><br/>100km x 100km Squares</th>
	<td><b>{$grid_submitted_1}</b><br/>/{$grid_total_1}</td>
	<td><b>{$grid_submitted_2}</b><br/>/{$grid_total_2}</td>
	<td>{$grid_submitted_1+$grid_submitted_2}<br/>/{$grid_total_1+$grid_total_2}</td>
</tr>
</tbody>
</table>

{if $overview}
<div style="float:right; width:{$overview_width+30}px; position:relative">

<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
{if $marker_1}
<div style="position:absolute;top:{$marker_1->top-8}px;left:{$marker_1->left-8}px;"><img src="http://{$static_host}/img/crosshairs.gif" alt="Centre for {$place_1.reference_name}" width="16" height="16"/></div>
{/if}
{if $marker_2}
<div style="position:absolute;top:{$marker_2->top-8}px;left:{$marker_2->left-8}px;"><img src="http://{$static_host}/img/crosshairs.gif" alt="Centre for {$place_2.reference_name}" width="16" height="16"/></div>
{/if}
</div>
</div>
</div>
{/if}

<p>The <acronym title="the Centre of 'gravity' for all images submitted so far" style="text-decoration:underline">Geograph Centre</acronym> for images in the {$references.1} is <a href="/gridref/{$centergr_1}" title="view square {$centergr_1}">{$centergr_1}</a>, {place place=$place_1}.

And for {$references.2} is <a href="/gridref/{$centergr_2}" title="view square {$centergr_2}">{$centergr_2}</a>, {place place=$place_2}.</p>
    
    <h3><a name="more"></a>More Statistics</h3>

   <p>User leaderboards: <a href="/statistics/moversboard.php">Weekly</a>, <a href="/statistics/leaderboard.php">All Time</a>, <a href="/statistics/monthlyleader.php">By Month</a>, <a href="/statistics/leaderhectad.php">First Hectads</a>,  <a href="/statistics/leaderallhectad.php">Hectads</a> and <a href="/statistics/busyday.php?users=1">Most in One Day</a></p>

   <p><b>Covering the squares</b><a href="/help/squares">?</a>:<br/><br/>
   None: <a href="/statistics/not_geographed.php">Hectads</a> (10<small>km</small> x 10<small>km</small> Squares) - shrinking all the time!<br/><br/>
   Mostly: <a href="/statistics/most_geographed.php">Grid Squares &amp; Hectads</a> and <a href="/statistics/most_geographed_myriad.php">Myriads</a> <small>(100 x 100 Squares)</small>.<br/><br/>
   <b>Fully: <a href="/statistics/fully_geographed.php">Hectads</a> <small>(10 x 10 Squares)</small> - including Large Mosaic!</b><br/><br/>
   Graph: <a href="/statistics/photos_per_square.php">Gridsquares</a>, <a href="/statistics/hectads.php">Hectad</a>, <a href="/statistics/coverage_by_county.php">County</a> and <a href="/statistics/coverage_by_country.php">Country</a> Coverage</p>

   <p><b>Past Activity:</b><br/><br/>
   Graphs: <a href="/statistics/moversboard.php#rate_graph">Weekly Submissions</a>, <a href="/statistics/leaderboard.php#submission_graph">Overall Submissions</a>, <a href="/statistics/contributors.php">Contributor Graphs</a>.<br/><br/>
   Monthly Breakdown: <a href="/statistics/overtime.php" title="Monthly Breakdown of Images Submitted">Submissions</a>, <a href="/statistics/overtime.php?date=taken" title="Monthly Breakdown of Images Taken">Date Taken</a>, <a href="/statistics/overtime_users.php" title="Monthly Breakdown new User Signups">User Signups</a>, <a href="/statistics/overtime_forum.php" title="Monthly Breakdown for Forum Posts">Forum Posts</a> and <a href="/statistics/overtime_tickets.php">Change Suggestions</a>.<br/><br/>
   Hourly and Weekday Breakdown: <a href="/statistics/date_graphs.php" title="Hourly and Weekday Breakdown of Images Submitted">Submissions</a>, <a href="/statistics/date_graphs.php?date=taken" title="Hourly and Weekday Breakdown of Images Taken">Date Taken</a>, <a href="/statistics/date_users_graphs.php" title="Hourly and Weekday Breakdown of User Signups">User Signups</a> and <a href="/statistics/date_forum_graphs.php" title="Hourly and Weekday Breakdown for Forum Posts">Forum Posts</a>.<br/><br/>
   Most in a day: <a href="/statistics/busyday.php?date=submitted">Submissions</a>, <a href="/statistics/busyday.php">Images Taken</a>, <a href="/statistics/busyday_users.php">Users</a> and <a href="/statistics/busyday_forum.php">Forum Posts</a> (<a href="/statistics/busyday_forum.php?users=1" title="Most in a day by user">Users</a>,<a href="/statistics/busyday_forum.php?threads=1" title="Most in a day by topic">Topics</a>).<br/><br/>
   Yearly Saturation: <a href="/statistics/years.php?date=submitted">Submissions</a>, <a href="/statistics/years.php">Images Taken</a> and <a href="/statistics/years_forum.php">Forum Posts</a>.<br/><br/>
   Forum Topic Leaderboards: <a href="/statistics/leaderthread_forum.php">Most Popular Threads</a> and
   <a href="/statistics/forum_image_breakdown.php">By Thumbnails Used</a><br/><br/></p>

    <form method="get" action="/statistics/breakdown.php">
    <p>View breakdown of images by 
    <select name="by">
    	{html_options options=$bys selected=$by}
    </select> in <select name="ri">
    	{html_options options=$references selected=$ri}
    </select> 
    
    {dynamic}
    {if $user->registered}
	<select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">Just for {$profile->realname}</option>
		{/if}
		<option value="{$user->user_id}">Just for {$user->realname}</option>
		<option value="" {if !$u} selected{/if}>For Everyone</option>
	</select>
    {else}
	{if $u}
	<select name="u">
		<option value="{$u}" selected>Just for {$profile->realname}</option>
		<option value="">For Everyone</option>
	</select>
	{/if}
    {/if}
    {/dynamic}
    <input type="submit" value="Go"/></p></form>

   <p><b>More Technical Database Stats:</b><br/>
   <a href="/statistics/pulse.php">Geograph Pulse</a>,
   <a href="/statistics/totals.php">Current Totals</a>, 
   {dynamic}
    {if $user->registered}
     <a href="/statistics/contributor.php?u={$user->user_id}">Your Totals</a>, 
    {/if}
   {/dynamic}
   <a href="/statistics/images.php">Classification Breakdown</a> and
   <a href="/statistics/estimate.php">Future Estimates</a>.</p>

{include file="_std_end.tpl"}
