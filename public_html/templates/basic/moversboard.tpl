{assign var="page_title" value="Weekly Leaderboard"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Geograph Weekly Leaderboard</h2>

<p>Here is everyone who has contributed in the past 7 days, ordered by
number of geograph points awards. The "pending" column gives some idea of 
how much each person will climb when their pictures are moderated!</p>

<p>The <a href="/leaderboard.php">all-time top 50 leaderboard</a> is also available</p>

<p>Last generated at {$smarty.now|date_format:"%H:%M"} and covers all submissions since
{$cutoff_time|date_format:"%A, %d %b at %H:%M"}</p>

<table class="report"> 
<thead><tr><td>Position</td><td>Contributor</td><td>New<br/>Geograph<br/>Points</td><td>Pending</td></tr></thead>
<tbody>

{foreach from=$topusers key=topuser_id item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.geographs}</td>
<td align="right">{if $topuser.pending gt 0}<span style="font-size:0.8em">({$topuser.pending} pending)</span>{/if}</td>
</tr>
{/foreach}

</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0">Daily Submission Rate</h2>
<p>Here's a graph of average daily submissions for the last few weeks...<br/>
<img src="/img/rate.png" width="480" height="161" alt="daily submission rate graph"/>
</p>


 		
{include file="_std_end.tpl"}
