{assign var="page_title" value="Wochen-Rangliste :: $heading"}
{assign var="meta_description" value="Liste aller Teilnehmer, die letzte Woche Bilder eingereicht haben, sortiert nach `$heading`."}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Wochen-Rangliste :: {$heading}</h2>
	
<p><i>Typ</i>: {foreach from=$types item=t key=key}
[{if $t == $type}<b>{$typenames.$key}</b>{else}<a href="/statistics/moversboard.php?type={$t}">{$typenames.$key}</a>{/if}]
{/foreach} <a href="/help/sitemap#users">weitere...</a></p>

<p>Liste aller Teilnehmer, die in den letzten sieben Tagen Bilder eingereicht haben, sortiert nach
{$desc} (siehe <a title="Frequently Asked Questions" href="/help/stats_faq">FAQ</a> 
für Details). {if $pending}Die Spalte "Unmoderiert" ist ein Anhaltspunkt, wie weit ein Teilnehmer nach der Moderation
aufsteigen kann.{/if}</p>

<p>Es gibt auch eine <a href="/statistics/leaderboard.php{if $type != 'points'}?type={$type}{/if}">Gesamt-Rangliste</a>.</p>

<p>Die Liste wurde zuletzt um {$smarty.now|date_format:"%H:%M"} erstellt und enthält alle Einreichungen seit
{$cutoff_time|date_format:"%A, %d.%m. um %H:%M"}</p>

<table class="report"> 
<thead><tr><td>Rang</td><td>Teilnehmer</td><td>{$heading}</td>{if $points}<td>Punkte</td>{/if}{if $pending}<td>Unmoderiert</td>{/if}</tr></thead>
<tbody>

{foreach from=$topusers key=topuser_id item=topuser}
<tr><td align="right">{$topuser.ordinal}</td><td><a title="Profil anzeigen" href="/profile/{$topuser_id}">{$topuser.realname}</a></td>
{if $isfloat}
<td align="right">{$topuser.geographs|floatformat:"%.4f"}</td>
{else}
<td align="right">{$topuser.geographs}</td>
{/if}
{if $points}<td align="right">{$topuser.points}</td>{/if}
<td align="right">{if $topuser.pending gt 0}<span style="font-size:0.8em">({$topuser.pending} unmoderiert)</span>{/if}</td>
</tr>
{/foreach}


<tr class="totalrow"><th>&nbsp;</th><th>Gesamt</th><th align="right">
{if $isfloat}
{$geographs|floatformat:"%.4f"}
{else}
{$geographs}
{/if}
</th>{if $points}<th align="right">{$points}</th>{/if}{if $pending}<th align="right" style="font-size:0.8em">({$pending} unmoderiert)</th>{/if}</tr>
</tbody>
</table>


<h2 style="margin-top:2em;margin-bottom:0"><a name="rate_graph"></a>Einreichungen pro Tag</h2>
<p>Schaubild der durchschnittlichen Einreichungen pro Tag für die letzten Wochen:<br/>
<img src="/img/rate.png" width="480" height="161" alt="Schaubild der Einreichungen pro Tag"/>
</p>


 		
{include file="_std_end.tpl"}