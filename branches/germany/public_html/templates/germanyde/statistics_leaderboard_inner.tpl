{assign var="page_title" value="Top $limit Leaderboard  :: $heading"}
{include file="_basic_begin.tpl"}
<base target="_parent"/>

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
 	
</body>
</html>

