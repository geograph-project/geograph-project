{assign var="page_title" value="Top $limit Leaderboard  :: $type"|capitalize}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Top {$limit} Leaderboard :: {$type|capitalize}</h2>

<p>Variation: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="/statistics/leaderboard.php?type={$t}{$extralink}">{$t}</a>{/if}]
{/foreach} <a href="/help/sitemap#users">more...</a></p>

<p>Listed below are the top {$limit} contributors based on number of
{$desc}, (see <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details).</p>

<p>A <a href="/statistics/moversboard.php?type={$type}">weekly leaderboard</a> is also available showing the 
top submitters this week.</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td>{if $points}<td>Points</td>{/if}{if $images}<td>Images</td>{/if}{if $topusers[0].depth}<td>Depth</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
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


<h2 style="margin-top:2em;margin-bottom:0"><a name="submission_graph"></a>Overall Status</h2>
<p>Here's a graph of photo submissions for the past 12 months...<br/>
<img src="/img/submission_graph.png" width="480" height="161" alt="submission graph"/>
</p>


 		
{include file="_std_end.tpl"}
