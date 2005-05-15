{assign var="page_title" value="Statistics"}
{include file="_std_begin.tpl"}
 
    <h2>Overview Statistics for Geograph British Isles</h2>

<ul>
<li><b>{$users_submitted}</b> Users have Submitted Images</li>
<li><b>{$users_thisweek}</b> new Users in past 7 days</li>
<li>A total of <b>{$images_ftf}</b> Geograph Points have been awarded</p>
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
	<th>10km<sup>2</sup> Grid Squares</th>
	<td><b>{$tenk_submitted_1|thousends}</b><br/>/{$tenk_total_1|thousends}</td>
	<td><b>{$tenk_submitted_2|thousends}</b><br/>/{$tenk_total_2|thousends}</td>
	<td valign="top">{$tenk_submitted_both|thousends}<br/>/{$tenk_total_both|thousends}</td>
</tr>
<tr>
	<th>100km<sup>2</sup> Grid Squares</th>
	<td><b>{$grid_submitted_1}</b><br/>/{$grid_total_1}</td>
	<td><b>{$grid_submitted_2}</b><br/>/{$grid_total_2}</td>
	<td>{$grid_submitted_1+$grid_submitted_2}<br/>/{$grid_total_1+$grid_total_2}</td>
</tr>
</tbody>
</table>

<p>The <acronym title="the center of 'gravity' for all images submitted so far">Geograph Center</acronym> for images in the {$references.1} is <a href="/gridref/{$centergr_1}" title="view square {$centergr_1}">{$centergr_1}</a>, {if $place_1.distance > 3}{$place_1.distance} km from{else}near to{/if} <b>{$place_1.full_name}</b><small><i>{if $place_1.adm1_name && $place_1.adm1_name != $place_1.reference_name}, {$place_1.adm1_name}{/if}</i></small>.

And for {$references.2} is <a href="/gridref/{$centergr_2}" title="view square {$centergr_2}">{$centergr_2}</a>, {if $place_2.distance > 3}{$place_2.distance} km from{else}near to{/if} <b>{$place_2.full_name}</b><small><i>{if $place_2.adm1_name && $place_2.adm1_name != $place_2.reference_name}, {$place_2.adm1_name}{/if}</i></small>.</p>    
    
    <h3>More Statistics</h3>
    

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
    <input type="submit" value="Go"></p></form>
    <p><a href="/statistics/most_geographed.php">Most Geographed 10km x 10km Squares</a>, <a href="/statistics/wordnet.php">Popular Words</a>, and more coming soon.</p>

    
   <p align="center"><i>This page was last updated {$generation_time|date_format:"%H:%M"}</i>.</p>

{include file="_std_end.tpl"}
