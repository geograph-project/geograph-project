{assign var="page_title" value="Top 50 Leaderboard  :: $type"|capitalize}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Top 50 Leaderboard :: {$type|capitalize}</h2>

<p>Variation: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="moversboard.php?type={$t}">{$t}</a>{/if}]
{/foreach}</p>

<p>Listed below are the top 50 contributors based on number of
{$desc}, (see <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
for details).</p>

<p>A <a href="/moversboard.php{if $type != 'points'}?type={$type}{/if}">weekly leaderboard</a> is also available showing the 
top submitters this week.</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.imgcount}</td></tr>
{/foreach}

</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0"><a name="submission_graph"></a>Overall Status</h2>
<p>Here's a graph of photo submissions since we began...<br/>
<img src="/img/submission_graph.png" width="480" height="161" alt="submission graph"/>
</p>


 		
{include file="_std_end.tpl"}
