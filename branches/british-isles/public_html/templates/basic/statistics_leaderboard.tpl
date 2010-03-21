{assign var="page_title" value="Top $limit Leaderboard  :: $heading"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}


<div class="tabHolder" style="margin-top:3px">
	{foreach from=$types item=t}
	{if $t == $type}
	<span class="tabSelected">{$t}</span>
	{else}
	<a class="tab nowrap" href="/statistics/leaderboard.php?type={$t}{$extralink}">{$t}</a>
	{/if}
	{/foreach}
	<a href="/help/sitemap#users">more...</a> &nbsp;
	
	<span class="tabSelected">all time</span>
	
	<a class="tab" href="/statistics/moversboard.php?type={$type}">weekly</a> leaderboard
</div>
<div class="interestBox">
<h2>Top {$limit} Leaderboard :: {$heading|replace:'<br/>':' '}</h2>

<p>Listed below are the top {$limit} contributors based on number of<br/>
<big>{$desc}</big> (see this <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details).</p>
</div>
<br/>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td>{if $points}<td>First Points</td>{/if}{if $images}<td>Images</td>{/if}{if $topusers[0].depth}<td>Depth</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr {if $u && $topuser.user_id == $u} style="background-color:yellow"{/if}><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.imgcount}</td>
{if $points}<td align="right">{$topuser.points}</td>{/if}
{if $images}<td align="right">{$topuser.images}</td>{/if}
{if $topuser.depth}
<td align="right">{$topuser.depth}</td>
{/if}
</tr>
{/foreach}

</tbody>
</table>

<hr/>
 		
{include file="_std_end.tpl"}
