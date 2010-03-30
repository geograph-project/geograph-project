{if $credit_realname}
	{assign var="page_title" value="Profil für `$credit_realname`/`$profile->realname`"}
	{assign var="meta_description" value="Profil von `$credit_realname`/`$profile->realname`, Liste aktueller Bilder, Statistik, Links zu weiteren Informationen."}
{else}
	{assign var="page_title" value="Profil für `$profile->realname`"}
	{assign var="meta_description" value="Profil von `$profile->realname`, Liste aktueller Bilder, Statistik, Links zu weiteren Informationen."}
{/if}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}" type="text/javascript"></script>

{dynamic}
{if $credit_realname}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	Das zuvor betrachtete Bild wurde zwar vom unten genannten Teilnehmer eingereicht, als Fotograf wurde aber <b>{$credit_realname|escape:'html'}</b> angegeben.
	</div>
	<br/><br/>
{/if}

{if $profile->tickets}
	<div id="ticket_message">
		{if $profile->last_ticket_time}
			<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
			Momentan gibt es <b>{$profile->tickets}</b> Änderungsvorschläge zu Bildern. Wir bitten darum, diese auf der <a href="/tickets.php">persönlichen Ticket-Seite</a> zu prüfen.
			<small><br/><br/>Der Grund für diesen Hinweis sind Probleme mit den Benachrichtigungsmails. <a href="javascript:void(hide_message())">Ich habe das gelesen, bitte ausblenden!</a> </small>
			</div>
			<br/><br/>
		{else}
			<div style="text-align:center;color:gray">Momentan gibt es  <b>{$profile->tickets}</b> Änderungsvorschläge zu Bildern. Wir bitten darum, diese auf der <a href="/tickets.php">persönlichen Ticket-Seite</a> zu prüfen. <a href="javascript:void(hide_message())">ausblenden</a></div>
		{/if}
	</div>
	<script type="text/javascript">{literal}
	function hide_message() {
		document.getElementById('ticket_message').style.display= 'none';
		pic1= new Image(); 
		pic1.src="/profile.php?hide_message";
	}
	{/literal}</script>
{/if}
{/dynamic}

{if $overview}
  <div style="float:right; width:{$overview_width+30}px; position:relative">
  {include file="_overview.tpl"}
  </div>
{/if}



<h2><a name="top"></a><img src="http://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50" alt="{$profile->realname|escape:'html'}s Gravatar" style="vertical-align:middle;padding-right:10px"/>Profil für {$profile->realname|escape:'html'}</h2>

{if $profile->role}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Funktion bei Geograph</b>: {$profile->role}</div>
{else}
	{if strpos($profile->rights,'admin') > 0}
		<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Funktion bei Geograph</b>: Entwickler</div>
	{else}
		{if strpos($profile->rights,'moderator') > 0}
			<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Funktion bei Geograph</b>: Moderator</div>
		{/if}
	{/if}
{/if}

<ul>
	<li><b>Name</b>: {$profile->realname|escape:'html'}</li>

	<li><b>Kurzname (Nick)</b>: 
		{if $profile->nickname}
			{$profile->nickname|escape:'html'} 
		{else}
			<i>keiner</i>
		{/if}
	</li>

	<li><b>Website</b>: 
		{if $profile->website}
			{external href=$profile->website}
		{else}
			<i>keine</i>
		{/if}
	</li>
 
 	{if $profile->hasPerm('dormant',true)}
 		<!--<li><i>We do not hold contact details for this user.</i></li>-->
 	{elseif $user->user_id ne $profile->user_id}
		{if $profile->public_email eq 1}
			<li><b>E-Mail</b>: {mailto address=$profile->email encode="javascript"}</li>
		{/if}
		<li><a title="{$profile->realname|escape:'html'} kontaktieren" href="/usermsg.php?to={$profile->user_id}">Mitteilung an {$profile->realname|escape:'html'} senden</a></li>
	{else}
		<li><b>E-Mail</b>: {mailto address=$profile->email encode="javascript"}
		{if $profile->public_email ne 1} <em>(für andere nicht sichtbar)</em>{/if}
		</li>
	{/if}

	{if $profile->grid_reference}
		<li><b>Heimatquadrat</b>: 
		<a href="/gridref/{$profile->grid_reference|escape:'html'}">{$profile->grid_reference|escape:'html'}</a>
	{/if}
	
	<li><b>Mitglied seit</b>: 
		{$profile->signup_date|date_format:"%B %Y"}
	</li>
</ul>

{if $profile->about_yourself && $profile->public_about}
	<div class="caption" style="background-color:#dddddd; padding:10px;">
	<h3 style="margin-top:0px;margin-bottom:0px">Mehr über mich</h3>
	{$profile->about_yourself|nl2br|GeographLinks:true}</div>
{/if}

{if $user->user_id eq $profile->user_id}
	<p><a href="/profile.php?edit=1">Profil ändern</a>, falls gewünscht.</p> 	
{else}
	<br/><br/>
{/if}


{if $profile->stats.images gt 0}
 	<div style="background-color:#dddddd; padding:10px;">
 		{if $profile->stats.images > 2}
		<div style="float:right; position:relative; margin-top:0px; font-size:0.7em">Aufschlüsseln nach <a href="/statistics/breakdown.php?by=status&amp;u={$profile->user_id}" rel="nofollow">Klassifizierung</a>, <a href="/statistics/breakdown.php?by=takenyear&amp;u={$profile->user_id}" rel="nofollow">Aufnahmedatum</a> oder <a href="/statistics/breakdown.php?by=gridsq&amp;u={$profile->user_id}" rel="nofollow">100km&thinsp;&times;&thinsp;100km-Quadrat</a><sup><a href="/help/squares" title="Welche Quadrate gibt es?">?</a></sup>.</div>
		{/if}
		<h3 style="margin-top:0px;margin-bottom:0px">Meine Statistik</h3>
		<ul>
			{if $profile->stats.points}
				<li><b>{$profile->stats.points}</b> Geograph-Punkte <sup>(siehe <a title="Fragen und Antworten" href="/faq.php#points">FAQ</a>)</sup>
					{if $user->user_id eq $profile->user_id && $profile->stats.points_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Rang: <b>{$profile->stats.points_rank|ordinal}</b> {if $profile->stats.points_rank > 1}({$profile->stats.points_rise} mehr nötig um aufzusteigen){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			{if $profile->stats.geosquares}
				<li><b>{$profile->stats.geosquares}</b> persönliche Punkte (Planquadrat{if $profile->stats.geosquares ne 1}e{/if} mit <i>Geobildern</i>)
					{if $user->user_id eq $profile->user_id && $profile->stats.geo_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Rang: <b>{$profile->stats.geo_rank|ordinal}</b> {if $profile->stats.geo_rank > 1}({$profile->stats.geo_rise} mehr nötig um aufzusteigen){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			
			
			<li><b>{$profile->stats.images}</b> Bild{if $profile->stats.images ne 1}er{/if}
				{if $profile->stats.squares gt 1}
					<ul style="font-size:0.8em;margin-bottom:2px">
					<li><b>{$profile->stats.squares}</b> Planquadrat{if $profile->stats.squares ne 1}e{/if}
					ergeben eine Dichte von <b>{$profile->stats.depth|floatformat:"%.2f"}</b> <sup>(siehe <a title="Fragen und Antworten zur Statistik" href="/help/stats_faq">FAQ</a>)</sup>
					</li>
					{if $profile->stats.hectads > 1}
						<li>in <b>{$profile->stats.hectads}</b> verschiedenen 10km&thinsp;&times;&thinsp;10km-Quadraten und <b>{$profile->stats.myriads}</b> 100km&thinsp;&times;&thinsp;100km-Quadraten<sup><a href="/help/squares">?</a></sup>{if $profile->stats.days > 3}, aufgenommen an <b>{$profile->stats.days}</b> verschiedenen Tagen{/if}</li>
					{/if}
					</ul>
				{/if}
			</li>
			{if $profile->stats.content}
				<li style="margin-top:10px"><b>{$profile->stats.content}</b> <a href="/content/?user_id={$profile->user_id}" title="Beiträge von {$profile->realname|escape:'html'} betrachten">Benutzerbeiträge</a>
					{if $user->user_id eq $profile->user_id}
						[<a href="/article/?user_id={$profile->user_id}">Artikelliste</a>]
					{/if}
				</li>
			{/if}
		</ul>
		<div style="float:right;font-size:0.8em; color:gray; margin-top:-20px">Zuletzt aktualisiert: {$profile->stats.updated|date_format:"%H:%M"}</div>
	</div>
{else}
	<h3>Meine Statistik</h3>
	<ul>
		  <li>Keine Bilder eingereicht {if $userimages}(es kann einige Stunden nach den ersten Einreichungen dauern, bis die Statistik erscheint){/if}</li>
	</ul>
{/if}

{if $userimages}
	<div style="float:right; position:relative; font-size:0.7em; padding:10px"><a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1">Bilder von {$profile->realname|escape:'html'} suchen</a> (<a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbs">Nur Thumbnails</a>, <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=slide">Diashow</a>)<br/>
	<form action="/search.php" style="display:inline">
	<div>
	<label for="fq">Suche</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
	<input type="hidden" name="user_id" value="{$profile->user_id}"/>
	<input type="submit" value="Los"/>
	</div>
	</form></div>
	<h3 style="margin-bottom:0px">Bilder</h3>
	
	<p style="font-size:0.7em">Um die Sortierung zu ändern, bitte Spaltentitel anklicken.</p>
	
	{if $limit}
		<p>Diese Seite zeigt die letzten {$limit} Bilder, weitere sind <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=text&amp;resultsperpage=100&amp;page=2">über das Such-Interface</a> verfügbar.</p>
	{/if}
	<br style="clear:both"/>
	<table class="report sortable" id="photolist" style="font-size:8pt;">
	<thead><tr>
		<td><img title="Gibt es Forenbeiträge zum Planquadrat?" src="/templates/basic/img/discuss.gif" alt="" width="10" height="10" /> ?</td>
		<td>Quadrat</td>
		<td>Titel</td>
		<td sorted="desc">Eingereicht</td>
		<td>Klassifizierung</td>
	</tr></thead>
	<tbody>
	{foreach from=$userimages item=image}
		<tr>
		<td sortvalue="{$image->last_post}">{if $image->topic_id}<a title="Zur Diskussion - zuletzt aktualisiert am {$image->last_post|date_format:"%a, %e. %b %Y um %H:%M"}" href="/discuss/index.php?action=vthread&amp;forum={$image->forum_id}&amp;topic={$image->topic_id}" ><img src="/templates/basic/img/discuss.gif" width="10" height="10" alt="discussion indicator"></a>{/if}</td>
		<td sortvalue="{$image->grid_reference}"><a title="Bild betrachten" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
		<td>{$image->title}</td>
		<td sortvalue="{$image->gridimage_id}" class="nowrap">{$image->submitted|date_format:"%a, %e. %b %Y"}</td>
		<td class="nowrap">{if $image->moderation_status eq "accepted"}Extrabild{elseif $image->moderation_status eq "geograph"}Geobild{elseif $image->moderation_status eq "rejected"}Abgelehnt{elseif $image->moderation_status eq "pending"}Unmoderiert{else}{$image->moderation_status}{/if} {if $image->ftf}(erstes){/if}</td>
		</tr>
	{/foreach}
	</tbody></table>

	{if $limit}
		<p>Diese Seite zeigt die letzten {$limit} Bilder, weitere sind <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=text&amp;resultsperpage=100&amp;page=2">über das Such-Interface</a> verfügbar.</p>
	{/if}
	{if $profile->stats.images gt 100 && $limit == 100}
		{dynamic}
		{if $user->user_id eq $profile->user_id}
			<form method="get" action="/profile/{$profile->user_id}/more"><input type="submit" value="Längere Profilseite anzeigen"/></form>
		{/if}
		{/dynamic}
	{/if}
	<h3 style="margin-bottom:0px">In meinen Bildern stöbern</h3>

	<ul>
		
		<li><b>Karten</b>: {if $profile->stats.images gt 10}<a href="/profile/{$profile->user_id}/map" rel="nofollow">Persönliche Geograph-Karte</a> oder {/if} aktuelle Fotos auf <a href="http://maps.google.de/maps?q=http://{$http_host}/profile/{$profile->user_id}/feed/recent.kml&amp;ie=UTF8&amp;om=1">Google Maps</a></li>

		<li><b>Aktuelle Bilder</b>: <a title="Bilder von {$profile->realname} in Google Earth anschauen" href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;kml">als KML</a> oder <a title="RSS-Feed für Bilder von {$profile->realname}" href="/profile/{$profile->user_id}/feed/recent.rss" class="xml-rss">RSS</a> oder <a title="GPX-Datei für Bilder von {$profile->realname}" href="/profile/{$profile->user_id}/feed/recent.gpx" class="xml-gpx">GPX</a></li>
		{if $profile->stats.images gt 10}
			{dynamic}{if $user->registered}
				<li><b>Download</b>: Alle Bilder als
					<a title="CSV-Datei für Bilder von {$profile->realname}" href="/export.csv.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1{/if}">CSV</a>
					{if $user->user_id eq $profile->user_id},
						<a title="Excel-2003-XML-Datei für Bilder von {$profile->realname}" href="/export.excel.xml.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1{/if}">XML<small> für Excel <b>2003</b></small></a>
					{/if}</li>
			{/if}{/dynamic}
		{/if}
		{if $user->user_id eq $profile->user_id}
			<li><b>Wordle</b>: {external href="http://`$http_host`/stuff/make-wordle.php?u=`$profile->user_id`" text="Alle Bildtitel als &bdquo;Wordle&ldquo; anzeigen."}</li>
			<li><b>Änderungsvorschläge</b>: <a href="/tickets.php" rel="nofollow">Aktuelle Tickets zeigen.</a></li>
		{/if}
	</ul>
	{if $user->user_id eq $profile->user_id}
		<ul>
		<li><a href="/search.php?my_squares=1&amp;user_id={$profile->user_id}&amp;user_invert_ind=1&amp;submitted_startDay=30&amp;submitted_startYear">Einreichungen der letzten 30 Tage in von mir fotografierten Quadraten suchen.</a></li>
		</ul>
	{/if}
{/if}


<div style="text-align:right"><a href="#top">Nach oben</a></div>

{include file="_std_end.tpl"}
