{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>First to Numberical Squares</h2>

<p>Listed below are the contributors being the first to claim a <a href="/discuss/index.php?action=vthread&amp;forum=2&amp;topic=1235">numberical square</a>.</p>



<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>Points</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td>{$topuser.imgcount}</td></tr>
{/foreach}

</tbody>
</table>
 		
{include file="_std_end.tpl"}
