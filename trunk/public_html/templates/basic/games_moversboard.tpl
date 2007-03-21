{assign var="page_title" value="Games Leaderboard"}

{include file="_std_begin.tpl"}

<h2>Games Leaderboard</h2>
	

<p>Last generated at {$smarty.now|date_format:"%H:%M"} and covers games since
{$cutoff_time|date_format:"%A, %d %b at %H:%M"}</p>

<table class="report"> 
<thead><tr><td>Position</td><td>Name</td><td>Pineapples</td></tr></thead>
<tbody>

{foreach from=$topusers key=topuser_id item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td>{if $topuser.user_id}<a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a>{else}{$topuser.username}{/if}</td>
<td align="right">{$topuser.score}</td>
</tr>
{/foreach}


<tr class="totalrow"><th>&nbsp;</th><th>Totals</th><th align="right">{$score}</th></tr></thead>
</tbody>
</table>



 		
{include file="_std_end.tpl"}
