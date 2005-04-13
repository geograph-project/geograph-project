{assign var="page_title" value="MoversBoard"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Geograph MoversBoard</h2>

<p>Listed below are the top 50 contributors based on 
the most recent activity, found by comparing the number of 
geographs they have submitted in total to that of last week.</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>Last Week</td><td>This Week</td><td>% Inc</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.lastweek}</td>
<td align="right">{$topuser.imgcount}</td>
<td align="right">{$topuser.perc}</td></tr>
{/foreach}

</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0">Overall Status</h2>
<p>Here's a graph of photo submissions since we began...<br/>
<img src="/img/submission_graph.png" width="480" height="161"/>
</p>


 		
{include file="_std_end.tpl"}
