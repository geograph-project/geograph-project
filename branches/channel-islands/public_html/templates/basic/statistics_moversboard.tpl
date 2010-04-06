{assign var="page_title" value="Weekly Leaderboard :: $heading"}
{assign var="meta_description" value="A list of all the contributors submitting images in the last week, ordered by `$heading`."}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Weekly Leaderboard :: {$heading}</h2>
	
<p><i>Variation</i>: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="/statistics/moversboard.php?type={$t}">{$t}</a>{/if}]
{/foreach} <a href="/help/sitemap#users">more...</a></p>

<p>Here is a list of contributors in the past 7 days, ordered by
number of {$desc} (see <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details). {if $pending}The "pending" column gives some idea of 
how much each person will climb when their pictures are moderated!{/if}</p>

<p>The <a href="/statistics/leaderboard.php{if $type != 'points'}?type={$type}{/if}">all-time top 150 leaderboard</a> is also available.</p>

<p>Last generated at {$smarty.now|date_format:"%H:%M"} and covers all submissions since
{$cutoff_time|date_format:"%A, %d %b at %H:%M"}</p>

<table class="report"> 
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td>{if $points}<td>Points</td>{/if}{if $pending}<td>Pending</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers key=topuser_id item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td><a title="View profile" href="/profile/{$topuser_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.geographs}</td>
{if $points}<td align="right">{$topuser.points}</td>{/if}
<td align="right">{if $topuser.pending gt 0}<span style="font-size:0.8em">({$topuser.pending} pending)</span>{/if}</td>
</tr>
{/foreach}


<tr class="totalrow"><th>&nbsp;</th><th>Totals</th><th align="right">{$geographs}</th>{if $points}<th align="right">{$points}</th>{/if}{if $pending}<th align="right" style="font-size:0.8em">({$pending} pending)</th>{/if}</tr></thead>
</tbody>
</table>


 		
{include file="_std_end.tpl"}
