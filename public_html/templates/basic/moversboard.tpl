{assign var="page_title" value="MoversBoard"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Geograph MoversBoard</h2>

<p>Listed below are the top 50 contributors based on 
the most recent activity, found by the number of 
geographs submitted in the last week.</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>New Images</td><td style="color:silver">Pending</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.newcount}</td>
<td align="right" style="color:silver">{$topuser.pending}</td></tr>
{/foreach}

</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0">Overall Status</h2>
<p>Here's a graph of photo submissions since we began...<br/>
<img src="/img/submission_graph.png" width="480" height="161"/>
</p>


 		
{include file="_std_end.tpl"}
