{assign var="page_title" value="Leaderboard"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Geograph Leaderboard</h2>

<p>Listed below are the top 50 contributors based on number of
geograph points, earned by being the first to submit a geograph for
a particular grid square (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a> 
for details)</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>Points</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</td>
<td>{$topuser.imgcount}</td></tr>
{/foreach}

</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0">Overall Status</h2>
<p>Here's a graph of photo submissions since we began...<br/>
<img src="/img/submission_graph.png" width="480" height="161"/>
</p>


 		
{include file="_std_end.tpl"}
