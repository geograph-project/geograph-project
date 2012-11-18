{assign var="page_title" value="Bildinformationen �ndern"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Planquadrat {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->current_title|escape:'html'}</h2>

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>Dieses Bild wird gerade von {$locked_by_moderator} bearbeitet</b>, bitte momentan keine �nderungen vornehmen!
	</p>
{/if}

{if $error}
<h2><span class="formerror">�nderungen nicht angenommen - Bitte Fehlermeldungen beachten...</span></h2>
{/if}


<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	<div class="caption640" style="text-align:right;">
		<a href="/geonotes.php?id={$image->gridimage_id}">Beschriftungen bearbeiten</a>
	{if $image->original_width}
		| <a href="/more.php?id={$image->gridimage_id}">Andere Gr��en</a> | <a href="/resubmit.php?id={$image->gridimage_id}">Gr��ere Version hochladen</a>
	{elseif $user->user_id eq $image->user_id}
		| <a href="/resubmit.php?id={$image->gridimage_id}">Gr��ere Version hochladen</a>
	{/if}
	</div>
  {if $thumb}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=0" style="font-size:0.6em">In voller Gr��e zeigen</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a></div>
  {else}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" style="font-size:0.6em">Verkleinern</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getFull()}</a></div>
  {/if}
  <div class="caption"><b>{$image->current_title|escape:'html'}</b> von <a href="{$image->profile_link}">{$image->realname}</a>{if $isowner} (<a href="/licence.php?id={$image->gridimage_id}">anderen Fotografen angeben</a>){/if}</div>
  
  {if $image->comment}
  <div class="caption" style="border:1px dotted lightgrey;">{$image->current_comment|escape:'html'|geographlinks}</div>
  {/if}
  <div class="statuscaption">Klassifikation:
   {if $image->moderation_status eq "accepted"}Extrabild{elseif $image->moderation_status eq "geograph"}Geobild{elseif $image->moderation_status eq "rejected"}Abgelehnt{elseif $image->moderation_status eq "pending"}Noch nicht moderiert{/if}
   {if $image->mod_realname}(Moderator: <a href="/profile/{$image->moderator_id}">{$image->mod_realname}</a>){/if}</div>
</div>
{if $showfull}
  	{if $isowner and $image->moderation_status eq 'pending'}
  	  {* FIXME if $thankyou eq 'mod'}
	  	<h2 class="titlebar" style="background-color:lightgreen">Thank you</h2>
	  	<p>Your suggestion has been recorded, it will be taken into account during moderation. <a href="/photo/{$image->gridimage_id}">Return to the image page</a></p>
	  {elseif $thankyou eq 'modreply'}
	  	<h2 class="titlebar" style="background-color:lightgreen">Thank You</h2>
	  	<p>Your suggestion has been recorded, it will be taken into account during moderation, however please use the comment box below to explain the reason for the suggestion.</p>
	  {/if*}

  	  <form action="/moderation.php" method="post">
  	  <input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
  	  <h2 class="titlebar">Moderationsvorschlag</h2>
  	  <p>Ich schlage als Klassifikation vor:
  	  {if $image->user_status}
  	  <input class="accept" type="submit" id="geograph" name="user_status" value="Geobild"/>
  	  {/if}
  	  {if $image->user_status != 'accepted'}
  	  <input class="accept" type="submit" id="accept" name="user_status" value="Extrabild"/>
  	  {/if}
  	  {if $image->user_status != 'rejected'}
  	  <input class="reject" type="submit" id="reject" name="user_status" value="Ablehnen"/>
  	  {/if}
  	  {if $image->user_status}
	  <br/><small>[Aktueller Vorschlag: {if $image->user_status eq "accepted"}Extrabild{elseif $image->user_status eq "geograph"}Geobild{elseif $image->user_status eq "rejected"}Ablehnen{/if}</small>]
	  {/if}</p>
  	  <p style="font-size:0.8em">(Einen dieser Kn�pfe dr�cken als Hinweis an den Moderator)</p>
  	  </form>
  	{elseif $isadmin and $image->user_status}
  	  <h2 class="titlebar">Moderation Suggestion</h2>
  	   Suggestion: {if $image->user_status eq "accepted"}supplemental{else}{$image->user_status}{/if}
	{/if}
<br/>
<br/>
  {if $isadmin && $is_mod}
	  <form method="post">
	  <script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>
	  <h2 class="titlebar">Moderation</h2>
	  <p><input class="accept" type="button" id="geograph" value="Geobild" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Extrabild" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Ablehnen" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <span class="caption" id="modinfo{$image->gridimage_id}">Aktuelle Klassifikation:
	  {if $image->moderation_status eq "accepted"}Extrabild{elseif $image->moderation_status eq "geograph"}Geobild{elseif $image->moderation_status eq "rejected"}Abgelehnt{elseif $image->moderation_status eq "pending"}Noch nicht moderiert{/if}
	  {if $image->mod_realname}<abbr title="Datum der letzten Moderation etwa: {$image->moderated|date_format:"%a, %e. %b %Y"}"><small><small>, von <a href="/usermsg.php?to={$image->moderator_id}&amp;image={$image->gridimage_id}">{$image->mod_realname}</a></small></small></abbr>{/if}</span></p>
	  </form>
  {/if}


{if $thankyou eq 'pending'}
	<a name="form"></a>
	<h2>Danke!</h2>
	<p>Vielen Dank f�r die �nderungsvorschl�ge, wir werden per Mail �ber deren Bearbeitung informieren.</p>

	<p>Die Vorschl�ge sind unten aufgef�hrt. <a href="/photo/{$image->gridimage_id}">Dieser Link f�hrt zur�ck zur Foto-Seite.</a></p>
{/if}

{if $thankyou eq 'comment'}
	<a name="form"></a>
	<h2>Danke!</h2>
	<p>Vielen Dank f�r die Anmerkungen zum �nderungsvorschlag, die Moderatoren wurden benachrichtigt.</p>

	<p>Ausstehende �nderungsvorschl�ge sind unten aufgef�hrt. <a href="/photo/{$image->gridimage_id}">Dieser Link f�hrt zur�ck zur Foto-Seite.</a></p>
{/if}


{if $show_all_tickets eq 1}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/tickets.php" title="Ticketverwaltung">&lt;&lt;&lt;</a>{/if}
	Alle �nderungsvorschl�ge
	{if $isowner}<small>(<a href="/tickets.php" title="Ticketliste">Zur�ck zur Liste</a>)</small>{/if}
	</h2>
	
	{if $opentickets}	
	<p>Alle �nderungsvorschl�ge f�r dieses Bild sind unten aufgef�hrt. 
	<a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=0">Nur offene Anfragen anzeigen.</a></p>
	{else}
	<p>Es gibt keine �nderungsvorschl�ge f�r dieses Bild.</p>

	{/if}
{else}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/tickets.php" title="Ticketverwaltung">&lt;&lt;&lt;</a>{/if}
	Offene �nderungsvorschl�ge
	{if $isowner}<small>(<a href="/tickets.php" title="Ticketliste">Zur�ck zur Liste</a>)</small>{/if}
	</h2>
	{if $opentickets}	
	<p>Alle offenen �nderungsvorschl�ge f�r dieses Bild sind unten aufgef�hrt. 
	{else}

	<p>Es gibt keine offenen �nderungsvorschl�ge f�r dieses Bild. 
	{/if}
	Um auch �ltere, abgeschlossene Vorschl�ge zu sehen, <a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=1">hier klicken</a>.</p>
{/if}

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>Dieses Bild wird gerade von {$locked_by_moderator} bearbeitet</b>, bitte momentan keine �nderungen vornehmen!
	</p>
{/if}

{if $opentickets}

{foreach from=$opentickets item=ticket}
<form action="/editimage.php" method="post" name="ticket{$ticket->gridimage_ticket_id}">
<input type="hidden" name="gridimage_ticket_id" value="{$ticket->gridimage_ticket_id}"/>
<input type="hidden" name="id" value="{$ticket->gridimage_id}"/>

{if $lastdays ne $ticket->days}
<b>-zuletzt aktualisiert vor {$ticket->days}-</b>
{/if}
{assign var="lastdays" value=$ticket->days} 
<div class="ticket">
	

	<div class="ticketbasics">
	{if $ticket->type == 'minor'}
		<u>Kleinere �nderungen</u>, 
	{/if}
	{if $isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner') }
		{if $ticket->public ne 'everyone'}Anonym {if $ticket->public eq 'owner'}(f�r alle anderen) {/if}<b>vorgeschlagen</b>{else}<b>Vorgeschlagen</b>{/if}
		von {$ticket->suggester_name} 
		{if $ticket->user_id eq $image->user_id}
		  <b>(Einreicher des Fotos)</b>
		{/if}
	{elseif $ticket->user_id eq $image->user_id}
		Vorgeschlagen <b>vom Einreicher des Fotos</b>
	{elseif $ticket->user_id eq $user->user_id}
		<b>Selbst</b> vorgeschlagen
	{else}
		<b>Vorgeschlagen</b> von einem anonymen besucher der Seite
	{/if} 
	<b>am</b> {$ticket->suggested|date_format:"%a, %e %b %Y um %H:%M"} |

	{if $ticket->suggested ne $ticket->updated}

	<b>Aktualisiert</b> {$ticket->updated|date_format:"%a, %e %b %Y um %H:%M"} | 
	{/if}

	<i>({if $ticket->status eq 'open'}offen{elseif $ticket->status eq 'closed'}abgeschlossen{elseif $ticket->status eq 'pending'}unerledigt{/if})</i>
	
	{if $ticket->status ne "closed" && $isadmin && $ticket->moderator_id == $user->user_id}

		<input type="submit" name="disown" id="disown" value="Moderation abgeben"/>

	{/if}
	</div>
	

	

	{if $ticket->changes}

		<div class="ticketfields" style="padding-bottom:3px;margin-bottom:3px;border-bottom:1px solid gray">
		{foreach from=$ticket->changes item=item}
			<div>
			{assign var="editable" value=0}
			{if ($ticket->status eq "closed") or ($item.status eq 'immediate')}
				<input disabled="disabled" type="checkbox" {if ($item.status eq 'immediate') or ($item.status eq 'approved')}checked="checked"{/if}/>
				
			{else}
				{if $isadmin}
				<input type="checkbox" value="1" id="accept{$item.gridimage_ticket_item_id}" name="accepted[{$item.gridimage_ticket_item_id}]"/>
				{assign var="editable" value=1}
				{/if}
			{/if}
			<label for="accept{$item.gridimage_ticket_item_id}">
			{if $item.note_id}
				<a href="/geonotes.php?id={$ticket->gridimage_id}&amp;note_id={$item.note_id}" target="geonotepreview">Beschriftung Nr. {$item.note_id}</a>:
			{/if}
			�ndere
			{if $item.field eq 'grid_reference'}
			Motivposition
			{elseif $item.field eq 'photographer_gridref'}
			Aufnahmestandort
			{elseif $item.field eq 'view_direction'}
			Blickrichtung
			{elseif $item.field eq 'title1'}
			Titel
			{elseif $item.field eq 'title2'}
			englischen Titel
			{elseif $item.field eq 'comment1'}
			Beschreibung
			{elseif $item.field eq 'comment2'}
			englische Beschreibung
			{elseif $item.field eq 'imagetaken'}
			Aufnahmedatum
			{elseif $item.field eq 'imageclass'}
			Kategorie
			{else}
			{$item.field}
			{/if}
			von

			{if $item.note_id}
				{assign var="field" value="current_`$item.note_id`_`$item.field`"}
			{elseif $item.field eq "grid_reference"}
				{assign var="field" value="current_subject_gridref"}
			{else}
				{assign var="field" value="current_`$item.field`"}
			{/if}

			{if $item.note_id}
			  <span style="border:1px solid #dddddd{if $editable && $item.oldvalue != $image->$field}; text-decoration: line-through{/if}">{$item.oldvalue|escape:'html'|default:'blank'}</span>
			  zu 
			  <span style="border:1px solid #dddddd">{$item.newvalue|escape:'html'|default:'blank'}</span>
			{elseif $item.field eq "grid_reference" || $item.field eq "photographer_gridref"}

				<!--<span{if $editable && $item.oldvalue != $image->$field} style="text-decoration: line-through"{/if}>
					{getamap gridref=$item.oldvalue|default:'blank'}
				</span>
				to
				{getamap gridref=$item.newvalue|default:'blank'}-->
				<span{if $editable && $item.oldvalue != $image->$field} style="text-decoration: line-through"{/if}>
					{$item.oldvalue|escape:'html'|default:'blank'}
				</span>
				zu
				{$item.newvalue|escape:'html'|default:'blank'}

			{elseif $item.field eq "comment1" || $item.field eq "comment2"}
			  <br/>
			  <span style="border:1px solid #dddddd{if $editable && $item.oldvalue != $image->$field}; text-decoration: line-through{/if}">{$item.oldvalue|escape:'html'|default:'blank'}</span><br/>
			  zu<br/>
			  <span style="border:1px solid #dddddd">{$item.newvalue|escape:'html'|default:'blank'}</span>
			{else}
			  <span style="border:1px solid #dddddd{if $editable && $item.oldvalue != $image->$field}; text-decoration: line-through{/if}">{$item.oldvalue|escape:'html'|default:'blank'}</span>
			  zu 
			  <span style="border:1px solid #dddddd">{$item.newvalue|escape:'html'|default:'blank'}</span>
			{/if}

			{if $editable && $item.newvalue == $image->$field}
				<b>�nderungen bereits vorgenommen</b>
			{/if}

			</label>
			
			</div>
		{/foreach}
		{if $ticket->reopenmaptoken}
			<div style="text-align:right"><a href="/submit_popup.php?t={$ticket->reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Karte f�r diese <i>neuen</i> Werte �ffnen</a>&nbsp;&nbsp;&nbsp;</div>
		{/if}
		</div>
	{/if}
	
	{if ($isadmin or $isowner or ($ticket->user_id eq $user->user_id and $ticket->notify=='suggestor') )}
	<div class="ticketnotes">
		<div class="ticketnote">{$ticket->notes|escape:'html'|geographlinks|replace:'Auto-generated ticket, as a result of Moderation. Rejecting this image because:':'<span style="color:gray">Bei der Moderation automatisch erstelltes Ticket. Bild abgelehnt weil:</span><br/>'|replace:"Auto-generated ticket, as a result of Self Moderation. Please leave a comment (in the reply box just below this message) to explain the reason for suggesting &#039;Reject&#039;.":'<span style="color:gray">Bei der Selbstmoderation automatisch erstelltes Ticket. Bitte im Antwordfeld unten begr�nden, warum wir das Bild ablehnen sollen.</span><br/>'}</div>
	
		
		{if $ticket->comments}
			{if $isadmin or $isowner or ($user->user_id eq $ticket->user_id && $ticket->notify eq 'suggestor')}
				{foreach from=$ticket->comments item=comment}
				<div class="ticketnote">
					<div class="ticketnotehdr">
					{if $comment.user_id ne $ticket->user_id or ($isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner')) }
						{$comment.realname}
					{else}
						Ticketautor
					{/if} 
					{if $comment.user_id == $image->user_id}
						(Einreicher des Fotos)
					{elseif $comment.moderator}
						(Moderator)
					{/if}
					schrieb am {$comment.added|date_format:"%a, %e %b %Y um %H:%M"}</div>
					{$comment.comment|escape:'html'|geographlinks}

				</div>
				{/foreach}
			{else}
				{if ($user->user_id eq $ticket->user_id) and ($ticket->status eq "closed") && $ticket->lastcomment.moderator}
				<div class="ticketnote">
					<div class="ticketnotehdr">{$ticket->lastcomment.realname} {if $ticket->lastcomment.moderator}(Moderator){/if} schrieb am {$ticket->lastcomment.added|date_format:"%a, %e %b %Y um %H:%M"}</div>
					{$ticket->lastcomment.comment|escape:'html'|geographlinks}

				</div>
				{/if}
			{/if}
		{/if}
		
	

	</div>
	{/if}
	
	{if ($isadmin or $isowner or ($ticket->user_id eq $user->user_id and $ticket->notify=='suggestor') ) and ($ticket->status ne "closed")}
		{assign var="ticketsforcomments" value=1}
	<div class="ticketactions interestBox">
		<div>&nbsp;<b>Auf dieses Ticket antworten:</b></div>
		<textarea name="comment" rows="4" cols="70"></textarea><br/>
		
		<input type="submit" name="addcomment" value="Kommentar abschicken"/>
		
		{if $isadmin and $ticket->moderator_id > 0 and $ticket->moderator_id != $user->user_id}
			<input type="checkbox" name="claim" value="on" id="claim" checked="checked"/> <label for="claim" title="Ticketmoderation �bernehmen">Claim Ticket</label>
			&nbsp;&nbsp;&nbsp;
		{elseif $isadmin}
			<input type="hidden" name="claim" value="on"/>
		{/if}
		
		{if ($isowner || $isadmin) && $ticket->user_id ne $user->user_id}
			<input type="checkbox" name="notify" value="suggestor" id="notify_suggestor" {if $ticket->notify=='suggestor'}checked="checked"{/if}/> <label for="notify_suggestor">{if $isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner') }{$ticket->suggester_name}{else}Ticketautor{/if} diesen Kommentar schicken.</label>
			&nbsp;&nbsp;&nbsp;
		{/if}
		{if $isadmin}
		
			{if $ticket->changes}
		
			<input type="submit" name="accept" value="�nderungen annehmen und Ticket schlie�en" onclick="autoDisable(this)"/>

			{else}

			<input type="submit" name="close" value="Ticket schlie�en" onclick="autoDisable(this)"/>

			{/if} {$ticket->suggester_name} wird benachrichtigt.
			
			<input class="accept" type="button" id="defer" value="24 Stunden verschieben" onclick="deferTicket({$ticket->gridimage_ticket_id},24)"/>
	 		<input class="accept" type="button" id="defer" value="7 Tage verschieben" onclick="deferTicket({$ticket->gridimage_ticket_id},168)"/>
	 		<span class="caption" id="modinfo{$ticket->gridimage_ticket_id}"></span>
		{/if}
		
	</div>
	{/if}
	

</div>
</form>
{/foreach}


{/if}


<br/>
<br/>
{if !($opentickets && !$error && $isowner && $ticketsforcomments)}
<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=1" style="font-size:0.6em">Zur einfachen �nderungsseite wechseln</a>
{/if}
{else}
<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=0" style="font-size:0.6em">Zur vollen �nderungsseite wechseln</a>
{/if}

{if $opentickets && !$error && $isowner && $ticketsforcomments && $showfull}
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<ul>
		<li>Zustimmung zu den Vorschl�gen bitte im obigen <b>Antwortfeld</b> �u�ern.</li> 
		<li>Einw�nde gegen die Vorschl�ge bitte oben �u�ern, damit sie von den Moderatoren ber�cksichtigt werden k�nnen.</li>
		<li>Um jedoch die �nderungen sofort wirksam werden zu lassen{if $moderated.grid_reference} <span class="moderatedlabel">(Planquadratwechsel ausgenommen)</span>{/if}, oder um andere �nderungen vorzunehmen, bitte das <b>Formular zur <a href="/editimage.php?id={$image->gridimage_id}&amp;form">�nderung von Bilddetails</a></b> verwenden.</li>
		<li>Wenn im Ticket Probleme angesprochen aber die eigentlichen �nderungen nicht vorgenommen werden, ist es f�r uns hilfreich, wenn der Einreicher des Bilds selbst
		die entsprechenden �nderungen vornimmt.</li>
	</ul>
</div>
<br>


{else}

<h2 class="titlebar" style="margin-bottom:0px">Problem melden / Bildinformation �ndern <small><a href="/help/changes">[Hilfe]</a></small></h2>
{if $error}
<a name="form"></a>
<h2><span class="formerror">�nderungen nicht angenommen - Bitte Fehlermeldungen beachten...</span></h2>
{/if}

	{if $rastermap->enabled}
		<div class="rastermap" style="float:right;  width:350px;position:relative">
		
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		 
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					{/literal}{if $image->view_direction == -1}
						updateViewDirection();
					{/if}{literal}

				}
				AttachEvent(window,'load',updateMapMarkers,false);
				AttachEvent(window,'load',onChangeImageclass,false);
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
	
 		


<form method="post" action="/editimage.php#form" name="theForm" onsubmit="this.imageclass.disabled=false" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0; border-top:none">
<input type="hidden" name="id" value="{$image->gridimage_id}"/>

{if $moderated_count}

<div>
	{if $all_moderated}
		Alle �nderungsvorschl�ge
		werden von einem Moderator �berpr�ft.
		�ber den Fortgang der Moderation informieren wir per Mail.
	{else}
		Alle �nderungsvorschl�ge, die unten als "moderiert" beschrieben werden,
		werden von einem Moderator �berpr�ft.
		�ber den Fortgang der Moderation informieren wir per Mail.
	{/if}
</div>
{/if}

  <div style="float:right;  position:relative">
  <a title="In Google Earth �ffnen" href="/photo/{$image->gridimage_id}.kml" class="xml-kml">KML</a></div>

<p>
<label for="grid_reference"><b style="color:#0018F8">Koordinaten des Motivs</b> {if $moderated.grid_reference}<span class="moderatedlabel">(moderiert{if $isowner} bei Wechsel des Planquadrats{/if})</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="16" value="{$image->subject_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Markiert das Motiv" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Markiert das Motiv" width="20" height="34" align="middle"/>{/if}
<!--{getamap gridref="document.theForm.grid_reference.value" gridref2=$image->subject_gridref text="OS Get-a-map&trade;"}-->
<br/>
<span style="font-size:0.6em">
<a href="javascript:void(mapMarkerToCenter(document.theForm.grid_reference));void(updateMapMarker(document.theForm.photographer_gridref,false));">Marker zentrieren</a>
</span>
</p>

<p>
<label for="photographer_gridref"><b style="color:#002E73">Koordinaten des Fotografen</b> - Optional {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderiert)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="16" value="{$image->photographer_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Markiert den Aufnahmestandort" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Markiert den Aufnahmestandort" width="20" height="34" align="middle"/>{/if}
<!--{getamap gridref="document.theForm.photographer_gridref.value" gridref2=$image->photographer_gridref text="OS Get-a-map&trade;"}--><br/>
<span style="font-size:0.6em">
| <a href="javascript:void(copyGridRef());">Motivposition</a> | 
<a href="javascript:void(resetGridRefs());">Anfangswerte wiederherstellen</a> |<br/></span>

	{literal}
	<script type="text/javascript">
		function copyGridRef() {
			document.theForm.photographer_gridref.value = document.theForm.grid_reference.value;
			updateMapMarker(document.theForm.photographer_gridref,false);
			return false;
		}
		function resetGridRefs() {
			document.theForm.grid_reference.value = document.theForm.grid_reference.defaultValue;
			document.theForm.photographer_gridref.value = document.theForm.photographer_gridref.defaultValue;
			updateMapMarker(document.theForm.grid_reference,false);
			updateMapMarker(document.theForm.photographer_gridref,false);
			var ele = document.theForm.view_direction;
			for (var q=0;q<ele.options.length;q++) {
				if (ele.options[q].defaultSelected)
					ele.options[q].selected = true;
			}
			if (document.theForm.use6fig)
				document.theForm.use6fig.checked = document.theForm.use6fig.defaultChecked;
			return false;
		}
	</script>
	{/literal}


	<br/><input type="checkbox" name="use6fig" id="use6fig" {if $image->use6fig} checked="checked"{/if} value="1"/> <label for="use6fig">Nur 6-ziffrige Koordinaten anzeigen ({newwin href="/help/map_precision" text="Erl�uterung"})</label>
</p>


<p><label for="view_direction"><b>Blickrichtung</b>  {if $moderated.view_direction}<span class="moderatedlabel">(moderiert)</span>{/if}
</label> <small>(Fotograf schaut nach)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
	{foreach from=$dirs key=key item=value}
		<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$image->view_direction} selected="selected"{/if}>{$value}</option>
	{/foreach}
</select></p>

<span id="styleguidelink">({newwin href="/help/style" text="Style Guide �ffnen"})</span>

<p><label for="title"><b>Titel</b> {if $moderated.title}<span class="moderatedlabel">(moderiert)</span>{/if}</label> <br/>
 <span class="formerror" style="display:none" id="titlestyle">M�gliches Stilproblem. Siehe Style Guide. <span id="titlestylet" style="font-size:0.9em"></span><br/></span>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title1|escape:'html'}" title="Original: {$image->current_title1|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);" maxlength="128"/>
</p>
<p><label for="title2"><b>Englischer Titel</b> (optional) {if $moderated.title2}<span class="moderatedlabel">(moderiert)</span>{/if}</label> <br/>
 <span class="formerror" style="display:none" id="title2style">M�gliches Stilproblem. Siehe Style Guide. <span id="title2stylet" style="font-size:0.9em"></span><br/></span>
{if $error.title2}<span class="formerror">{$error.title2}</span><br/>{/if}
<input type="text" id="title2" name="title2" size="50" value="{$image->title2|escape:'html'}" title="Original: {$image->current_title2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title2',true);" onkeyup="checkstyle(this,'title2',false);" maxlength="128"/>
</p>


{if !$rastermap->enabled}
{literal}
<script type="text/javascript">

//rest loaded in geograph.js
AttachEvent(window,'load',onChangeImageclass,false);

</script>
{/literal}
{/if}
<p><label for="imageclass"><b>Geographische Kategorie</b> {if $moderated.imageclass}<span class="moderatedlabel">(moderiert)</span>{/if}</label><br />	
	{if $error.imageclass}
	<span class="formerror">{$error.imageclass}</span><br/>
	{/if}
	
	{if $error.imageclassother}
	<span class="formerror">{$error.imageclassother}</span><br/>
	{/if}
	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onmouseover="prePopulateImageclass()" disabled="disabled">
		<option value="">--bitte Kategorie w�hlen--</option>
		{if $image->imageclass}
			<option value="{$image->imageclass}" selected="selected">{$image->imageclass}</option>
		{/if}
		<option value="Other">Andere Kategorie...</option>
	</select><input type="button" name="imageclass_enable_button" value="change" onclick="prePopulateImageclass()"/>
	
	
	<span id="otherblock"><br/>
	<label for="imageclassother">Bitte Kategorie eingeben </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/></p>
	</span>
</p>	

{if $user->user_id eq $image->user_id || $isadmin}
	<p><label><b>Aufnahmedatum</b> {if $moderated.imagetaken}<span class="moderatedlabel">(moderiert)</span>{/if}</label> <br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	<br/><small>(Bitte so detailliert wie m�glich angeben. Wenn nur das Jahr oder der Monat bekannt ist, ist das auch in Ordnung)</small></p>
{else}
	<p><label><b>Aufnahmedatum</b></label> <span class="moderatedlabel">(�nderungen nur durch Einreicher)</span><br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m" all_extra="disabled"}</p>
{/if}


<p><label for="comment"><b>Beschreibung/Kommentar</b> {if $moderated.comment}<span class="moderatedlabel">(moderiert)</span>{/if}</label><br/>
 <span class="formerror" style="display:none" id="commentstyle">M�gliches Stilproblem. Siehe Style Guide. <span id="commentstylet"></span><br/></span>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="7" cols="80" title="Original: {$image->current_comment1|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$image->comment1|escape:'html'}</textarea>
</p>
<p>
<label for="comment2"><b>Englische Beschreibung/Kommentar</b> (optional) {if $moderated.comment2}<span class="moderatedlabel">(moderiert)</span>{/if}</label><br/>
 <span class="formerror" style="display:none" id="comment2style">M�gliches Stilproblem. Siehe Style Guide. <span id="comment2stylet"></span><br/></span>
{if $error.comment2}<span class="formerror">{$error.comment2}</span><br/>{/if}
<textarea id="comment2" name="comment2" rows="7" cols="80" title="Original: {$image->current_comment2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'comment2',true);" onkeyup="checkstyle(this,'comment2',false);">{$image->comment2|escape:'html'}</textarea>
<div style="font-size:0.7em">TIPP: Mit <span style="color:blue">[[TPT2769]]</span> oder <span style="color:blue">[[34]]</span>
kann man Planquadrate oder andere Bilder verlinken.<br/>Weblinks k�nnen direkt angegeben werden: <span style="color:blue">http://www.example.com</span></div>
</p>

<br/>
<div class="interestBox">
<p>
<label for="updatenote">&nbsp;<b>Beschreibung des Problems bzw. der �nderungen...</b></label><br/>

{if $error.updatenote}<br/><span class="formerror">{$error.updatenote}</span><br/>{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="5" cols="60"{if $user->message_sig} onfocus="if (this.value=='') {literal}{{/literal}this.value='{$user->message_sig|escape:'javascript'}';setCaretTo(this,0); {literal}}{/literal}"{/if}>{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
	Bitte so viele Informationen wie m�glich f�r die Moderatoren
	{if !$isowner}und den Einreicher des Fotos{/if} angeben.
	Eine Erkl�rung der Hintergr�nde f�r die �nderung ist f�r alle Beteiligten hilfreich.
</div>

</td></tr></table>

<div>
<input type="checkbox" name="type" value="minor" id="type_minor"/> <label for="type_minor">Ich best�tige, dass die �nderung klein ist, z.B. Korrektur von Grammatik oder Rechtschreibung</label>
</div>

<br style="clear:both"/>

{if $isadmin}
	<div>
	<input type="radio" name="mod" value="" id="mod_blank" checked="checked"/> <label for="mod_blank">Neues Ticket erstellen, das von einem anderen moderiert werden soll.</label><br/>
	<input type="radio" name="mod" value="assign" id="mod_assign"/> <label for="mod_assign">Offenes Ticket erstellen und mir zuordnen. (Dem Einreicher die M�glichkeit geben, zu antworten.)</label><br/>
	<input type="radio" name="mod" value="apply" id="mod_apply"/> <label for="mod_apply">�nderungen sofort durchf�hren und Ticket schlie�en. (Einreicher wird benachrichtigt.)</label></div>

	<br style="clear:both"/>
{else}
	{if $isowner} 
	<div>
		<input type="checkbox" name="mod" value="pending" id="mod_pending"{if $mod_pending} checked="checked"{/if}/> <label for="mod_pending">Moderator hinzuziehen (unabh�ngig von der Art der �nderungen).</label><br/><br/>
	</div>
	{/if}
{/if}

<input type="submit" name="save" value="�nderungen best�tigen" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Abbrechen" onclick="document.location='/photo/{$image->gridimage_id}';"/>

{if !$isowner && !$isadmin}
&nbsp;	<select name="public">
		<option value="no">Meinen Namen nicht offenlegen</option>
		<option value="owner" {if $user->ticket_public eq 'owner'} selected{/if}>Meinen Namen dem Einreicher des Fotos zeigen</option>
		<option value="everyone" {if $user->ticket_public eq 'everyone'} selected{/if}>Meinen Namen im �nderungsticket ver�ffentlichen</option>
	</select>
{/if}
</div>
</form>

{/if}

<script type="text/javascript" src="/categories.js.php"></script>
{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{literal}
	<script type="text/javascript">
	
	function releaseLock() {
		var myImage = new Image();
		myImage.src = "/editimage.php?id={/literal}{$image->gridimage_id}{literal}&unlock";
	}
	AttachEvent(window,'unload',releaseLock,false);
	</script>
{/literal}
{else}
	<h2>Bild nicht verf�gbar</h2>

	<p>{$error}</p>

	<p>Bei Fragen bitte das <a title="Kontaktformular" href="/contact.php">Kontaktformular</a> 
	verwenden.</p>
{/if}

{include file="_std_end.tpl"}
{/dynamic}
