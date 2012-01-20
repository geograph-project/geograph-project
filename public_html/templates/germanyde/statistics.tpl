{assign var="page_title" value="Statistik"}
{include file="_std_begin.tpl"}
 
    <h2>Statistik für Geograph Deutschland</h2>

<ul>
<li><b>{$users_submitted}</b> Teilnehmer haben Bilder eingereicht.</li>
<li><b>{$users_thisweek}</b> neue Teilnehmer in den letzten 7 Tagen.</li>
<li>Insgesamt wurden <b>{$images_ftf}</b> Geograph-Punkte erzielt.</li>
</ul>

<table border="1" cellpadding="4" cellspacing="0" class="statstable">
<thead><tr>
	<th>&nbsp;</th>
	{foreach from=$references_real item=ref key=ri}
	<th>{$ref}</th>
	{/foreach}
	<th><i>Gesamt</i></th>
</tr></thead>
<tbody><tr>
	<th>Eingereichte Bilder</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$images_total[$ri]|thousends}</b></td>
	{/foreach}
	<td>{$images_total[0]|thousends}</td>
</tr>
<tr>
	<th>&nbsp;[letzte 7 Tage]</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$images_thisweek[$ri]|thousends}</b></td>
	{/foreach}
	<td>{$images_thisweek[0]|thousends}</td>
</tr>
<tr>
	<th>1km<sup>2</sup>-Planquadrate</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$squares_submitted[$ri]|thousends}</b><br/>/{$squares_total[$ri]|thousends}</td>
	{/foreach}
	<td valign="top">{$squares_submitted[0]|thousends}<br/>/{$squares_total[0]|thousends}</td>
</tr>
<tr>
	<th>&nbsp;[Anteil]</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$percent[$ri]|floatformat:"%.3f"}%</b></td>
	{/foreach}
	<td>{$percent[0]|floatformat:"%.3f"}%</td>
</tr>
<tr>
	<th>&nbsp;[mit Geobildern]</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$geographs_submitted[$ri]|thousends}</b></td>
	{/foreach}
	<td>{$geographs_submitted[0]|thousends}</td>
</tr>
<tr>
	<th>Hectads<a href="/help/squares">?</a><br/>10km&times;10km-Quadrate</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$tenk_submitted[$ri]|thousends}</b><br/>/{$tenk_total[$ri]|thousends}</td>
	{/foreach}
	<td valign="top">{$tenk_submitted[0]|thousends}<br/>/{$tenk_total[0]|thousends}</td>
</tr>
<tr>
	<th>Myriads<a href="/help/squares">?</a><br/>100km&times;100km-Quadrate</th>
	{foreach from=$references_real item=ref key=ri}
	<td><b>{$grid_submitted[$ri]}</b><br/>/{$grid_total[$ri]}</td>
	{/foreach}
	<td>{$grid_submitted[0]}<br/>/{$grid_total[0]}</td>
</tr>
</tbody>
</table>

{if $overview}
<div style="float:right; width:{$overview_width+30}px; position:relative">

<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
	alt="Klickbare Karte" ismap="ismap" title="zum Vergrößern anklicken" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}

{foreach from=$references_real item=ref key=ri}
{if $marker[$ri]}
<div style="position:absolute;top:{$marker[$ri]->top-8}px;left:{$marker[$ri]->left-8}px;"><img src="http://{$static_host}/img/crosshairs.gif" alt="Centre for {$place[$ri].reference_name}" width="16" height="16"/></div>
{/if}
{/foreach}
</div>
</div>
</div>
{/if}

<p>Das <acronym title="der &bdquo;Schwerpunkt&ldquo; aller bis jetzt eingereichten Bilder" style="text-decoration:underline">Geograph-Zentrum</acronym> der Bilder ist:


{foreach from=$references_real item=ref key=ri}
In {$ref} <a href="/gridref/{$centergr[$ri]}" title="Planquadrat {$centergr[$ri]} ansehen">{$centergr[$ri]}</a>, {place place=$place[$ri]}.
{/foreach}
</p>

    
    <h3><a name="more"></a>Mehr Statistik</h3>

	{if $hasregions}
	<p><b><a href="/statistics/regions.php">Regionale Statistik</a>{if $regionlistlevel > -1} (<a href="/statistics/regions.php?level={$regionlistlevel}">lange Liste</a>){/if}</b></p>
	{/if}

   <p><b>Ranglisten</b>: <a href="/statistics/moversboard.php">Wöchentlich</a>, <a href="/statistics/leaderboard.php">Gesamt</a>, <a href="/statistics/monthlyleader.php">Nach Monat</a>, <a href="/statistics/leaderhectad.php">Hectads (bzgl. der ersten Geobilder)</a>,  <a href="/statistics/leaderallhectad.php">Hectads</a>, <a href="/statistics/leaderboard.php?type=images&when=1989&timerel=dbefore&date=taken">Historische Bilder</a> und <a href="/statistics/busyday.php?users=1">Bilder je Tag</a></p>

   <p><b>Abdeckung von Quadraten</b><a href="/help/squares">?</a>:<br/><br/>
   Ohne Bilder: <a href="/statistics/not_geographed.php">Hectads</a> (10km&times;10km-Quadrate)<br/><br/>
   Mit Bildern: <a href="/statistics/most_geographed.php">Planquadrate und Hectads</a> sowie <a href="/statistics/most_geographed_myriad.php">Myriads</a> <small>(100km&times;100km-Quadrate)</small>.<br/><br/>
   <b>Alle Planquadrate: <a href="/statistics/fully_geographed.php">Hectads</a> <small>(10km&times;10km-Quadrate)</small> &ndash; mit großem Mosaik!</b><br/><br/>
   Diagramm: Abeckung der <a href="/statistics/photos_per_square.php">Planquadrate</a> und <a href="/statistics/hectads.php">Hectads</a></p>

   <p><b>Aktivität</b><br/><br/>
   Diagramme: <a href="/statistics/moversboard.php#rate_graph">Wöchentliche Einreichungens</a>, <a href="/statistics/contributors.php">Teilnehmer</a>.<br/><br/>
   Monatliche aufgeschlüsselt: <a href="/statistics/overtime.php">Einreichungen</a>, <a href="/statistics/overtime.php?date=taken">Aufnahmedatum</a>, <a href="/statistics/overtime_users.php">Teilnehmer-Registrierungen</a>, <a href="/statistics/overtime_forum.php">Foren-Beiträge</a> und <a href="/statistics/overtime_tickets.php">Änderungsvorschläge</a>.<br/><br/>
   Aufgeschlüsselt nach Stunde, Wochentag und Monat: <a href="/statistics/date_graphs.php">Einreichungen</a>, <a href="/statistics/date_graphs.php?date=taken">Aufnahmedatum</a>, <a href="/statistics/date_users_graphs.php">Teilnehmer-Registrierungen</a> und <a href="/statistics/date_forum_graphs.php">Foren-Beiträge</a>.<br/><br/>
   Tage hoher Aktivität: <a href="/statistics/busyday.php?date=submitted">Einreichungen</a>, <a href="/statistics/busyday.php">aufgenommene Bilder</a>, <a href="/statistics/busyday_users.php">Teilnehmer-Registrierungen</a> und <a href="/statistics/busyday_forum.php">Foren-Beiträge</a> (<a href="/statistics/busyday_forum.php?users=1">Teilnehmer</a>,<a href="/statistics/busyday_forum.php?threads=1">Themen</a>).<br/><br/>
   Jährliche Aktivität: <a href="/statistics/years.php?date=submitted">Einreichungen</a>, <a href="/statistics/years.php">aufgenommene Bilder</a> und <a href="/statistics/years_forum.php">Foren-Beiträge</a>.<br/><br/>
   Foren-Aktivität: <a href="/statistics/leaderthread_forum.php">Beliebteste Themen</a> und
   <a href="/statistics/forum_image_breakdown.php">eingebundene Bilder</a><br/><br/></p>

    <form method="get" action="/statistics/breakdown.php">
    <p>Bilder nach
    <select name="by">
    	{html_options options=$bys selected=$by}
    </select> in <select name="ri">
    	{html_options options=$references selected=$ri}
    </select> 
    
    {dynamic}
    {if $user->registered}
	<select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">nur für {$profile->realname}</option>
		{/if}
		<option value="{$user->user_id}">nur für {$user->realname}</option>
		<option value="" {if !$u} selected{/if}>für alle</option>
	</select>
    {else}
	{if $u}
	<select name="u">
		<option value="{$u}" selected>nur für {$profile->realname}</option>
		<option value="">für alle</option>
	</select>
	{/if}
    {/if}
    {/dynamic}
    aufschlüsseln:
    <input type="submit" value="Los"/></p></form>

   <p><b>Technischere Datenbank-Statistiken:</b><br/>
   <a href="/statistics/pulse.php">Geograph-Puls</a>,
   <a href="/statistics/totals.php">Aktuelle Werte</a>, 
   {dynamic}
    {if $user->registered}
     <a href="/statistics/contributor.php?u={$user->user_id}">Eigene Teilnehmerstatistik</a>, 
    {/if}
   {/dynamic}
   <a href="/statistics/images.php">Moderationsstatus</a> und
   <a href="/statistics/estimate.php">Prognosen</a>.</p>

{include file="_std_end.tpl"}
