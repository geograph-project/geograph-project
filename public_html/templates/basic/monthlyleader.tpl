{assign var="page_title" value="Monthly Leaderboard"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Geograph Monthly Leaderboard</h2>

<p>Listed below are the top contributors, by the number of photographed submited per month.</p>

<p>See also <a href="/leaderboard.php">all-time top 50 leaderboard</a> and <a href="/moversboard.php">weekly leaderboard</a>.</p>

<table class="report">
<thead><tr><td>Month</td><td>Contributor</td><td>Photos</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.month}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td>{$topuser.imgcount}</td></tr>
{/foreach}

</tbody>
</table>

 		
{include file="_std_end.tpl"}
