{assign var="page_title" value="Top-$limit-Rangliste  :: $heading"}
{include file="_basic_begin.tpl"}
<base target="_parent"/>

<table class="report">
<thead><tr><td>Rang</td><td>Teilnehmer</td><td>{$heading}</td>{if $points}<td>Punkte</td>{/if}{if $images}<td>Bilder</td>{/if}{if $topusers[0].depth}<td>Dichte</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="Profil anzeigen" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
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

