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
<div style="position:absolute;top:{$marker_1->top-8}px;left:{$marker_1->left-8}px;"><img src="/templates/basic/img/crosshairs.gif" alt="Centre for {$place_1.reference_name}" width="16" height="16"/></div>
{/if}
{if $marker_2}
<div style="position:absolute;top:{$marker_2->top-8}px;left:{$marker_2->left-8}px;"><img src="/templates/basic/img/crosshairs.gif" alt="Centre for {$place_2.reference_name}" width="16" height="16"/></div>
{/if}
</div>
</div>
</div>
{/if}

<p>The <acronym title="the Centre of 'gravity' for all images submitted so far" style="text-decoration:underline">Geograph Centre</acronym> for images in the {$references.1} is <a href="/gridref/{$centergr_1}" title="view square {$centergr_1}">{$centergr_1}</a>, {if $place_1.distance > 3}{$place_1.distance} km from{else}near to{/if} <b>{$place_1.full_name}</b><small><i>{if $place_1.adm1_name && $place_1.adm1_name != $place_1.reference_name}, {$place_1.adm1_name}{/if}</i></small>.

And for {$references.2} is <a href="/gridref/{$centergr_2}" title="view square {$centergr_2}">{$centergr_2}</a>, {if $place_2.distance > 3}{$place_2.distance} km from{else}near to{/if} <b>{$place_2.full_name}</b><small><i>{if $place_2.adm1_name && $place_2.adm1_name != $place_2.reference_name}, {$place_2.adm1_name}{/if}</i></small>.</p>    
    
    <h3>More Statistics</h3>
    
   <p>Graphs: <a href="/moversboard.php#rate_graph">Weekly Submissions</a> and <a href="/leaderboard.php#submission_graph">Overall Submissions</a>.</p> 
   
   <p><b>Most <a href="/statistics/wordnet.php">Popular Words</a> in the last 7 days and all time.</b></p>
   
   <p>User leaderboards: <a href="/moversboard.php">Weekly</a>, <a href="/leaderboard.php">All Time</a>, <a href="/monthlyleader.php">By Month</a> and <a href="/statistics/busyday.php?users=1">Most in One Day</a>. (<a href="/statistics.php?by=user&amp;ri=1">Contributor List</a>)</p>

   <p><b>Not yet Geographed: <a href="/statistics/not_geographed.php">Hectads</a> (10<small>km</small> x 10<small>km</small> Squares) - shrinking all the time!</b></p>

   <p>Mostly Geographed: <a href="/statistics/most_geographed.php">Grid Squares</a>, 
   <a href="/statistics/most_geographed.php">Hectads</a> <small>(10 x 10 Squares)</small> and <a href="/statistics/most_geographed_myriad.php">Myriads</a> <small>(100 x 100 Squares)</small>.</p>
   
   <p><b>Fully Geographed: <a href="/statistics/fully_geographed.php">Hectads</a> <small>(10 x 10 Squares)</small> - including Large Mosaic!</b></p>
   
   <p>Centre Points: <a href="/statistics/counties.php">Ceremonial Counties</a>, <a href="/statistics/counties.php?type=capital">(Irish) County Capitals</a> and <a href="/statistics/cities.php">Cities</a> photographed so far.</p>

    <form method="get" action="/statistics/breakdown.php">
    <p><b>View breakdown of images by</b> 
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

   <p>Technical Database Stats:<br/>
   <a href="/statistics/totals.php">Current Totals</a>,
   <a href="/statistics/estimate.php">Future Estimates</a>,
   <a href="/statistics/overtime.php">Activity Breakdown</a> (<a href="/statistics/overtime.php?date=taken">by date taken</a>) (<a href="/statistics/overtime_forum.php">for Forum</a>),<br/>
   <a href="statistics/busyday.php">Most taken in a day</a> (<a href="statistics/busyday.php?date=submitted">submitted</a>) and 
   <a href="/statistics/forum_image_breakdown.php">Breakdown of Thumbnails used in Forum Topics</a>.</p>

{include file="_std_end.tpl"}
