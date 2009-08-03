{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Tetrad Contributions</h2>

<p>Listed below are the contributors by the number of <a href="/discuss/index.php?&action=vthread&forum=4&topic=1562">Tetrad's they contributed most to</a>. Basically counts the number of Tetrads that that user has been the top contributor.</p>



<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>Tetrads</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
<td>{$topuser.sqcount}</td></tr>
{/foreach}

</tbody>
</table>
 		
{include file="_std_end.tpl"}
