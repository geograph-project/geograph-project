{assign var="page_title" value="Top-$limit-Rangliste  :: $heading"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Rangliste der besten {$limit} :: {$heading}</h2>

<p>Typ: {foreach from=$types item=t key=key}
[{if $t == $type}<b>{$typenames.$key}</b>{else}<a href="/statistics/leaderboard.php?type={$t}{$extralink}">{$typenames.$key}</a>{/if}]
{/foreach} <a href="/help/sitemap#users">weitere...</a></p>

<p>Die Liste enthält die {$limit}  besten Teilnehmer bezüglich
{$desc}. Siehe <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
für Details.</p>

<p>Es gibt auch eine <a href="/statistics/moversboard.php?type={$type}">Wochen-Rangliste</a>, die die besten Teilnehmer für
diese Woche zeigt.</p>

<table class="report">
<thead><tr><td>Rang</td><td>Teilnehmer</td><td>{$heading}</td>{if $points}<td>Punkte</td>{/if}{if $images}<td>Bilder</td>{/if}{if $topusers[0].depth}<td>Dichte</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="Profil anzeigen" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
{if $isfloat}
<td align="right">{$topuser.imgcount|floatformat:"%.4f"}</td>
{else}
<td align="right">{$topuser.imgcount}</td>
{/if}
{if $points}<td align="right">{$topuser.points}</td>{/if}
{if $images}<td align="right">{$topuser.images}</td>{/if}
{if $topuser.depth}
<td align="right">{$topuser.depth|floatformat:"%.2f"}</td>
{/if}
</tr>
{/foreach}

</tbody>
</table>


 		
{include file="_std_end.tpl"}
