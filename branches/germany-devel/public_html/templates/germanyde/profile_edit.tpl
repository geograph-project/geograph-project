{include file="_std_begin.tpl"}
{dynamic}

<form class="simpleform" method="post" action="/profile.php">
<input type="hidden" name="edit" value="1"/>

{if $errors.general}
<div class="formerror">{$errors.general}</div>
{/if}

 
<fieldset>
<legend>Allgemeine Informationen</legend>

<div class="field">
	{if $errors.realname}<div class="formerror"><p class="error">{$errors.realname}</p>{/if}
	 
	<label for="realname">Realname:</label>
	<input type="text" id="realname" name="realname" value="{$profile->realname|escape:'html'}"/>
	
	<div class="fieldnotes">Der Realname wird benötigt, damit wir
	den Urheber der Bilder angeben können.</div>
	
	{if $errors.realname}</div>{/if}
</div>

 
<div class="field">

	{if $errors.nickname}<div class="formerror"><p class="error">{$errors.nickname}</p>{/if}
	
	<label for="nickname">Kurzname (Nick):</label>
	<input type="text" id="nickname" name="nickname" value="{$profile->nickname|escape:'html'}"/>
	
	<div class="fieldnotes">Der Kurzname kann zum Einloggen verwendet werden und
	wird in Forendiskussionen angezeigt.</div>
	
	{if $errors.nickname}</div>{/if}
</div>
 




<div class="field">
 
	{if $errors.email}<div class="formerror"><p class="error">{$errors.email}</p>{/if}
	
	<label for="email">E-Mail:</label>
	<input type="text" id="email" name="email" value="{$profile->email|escape:'html'}" size="35"/>
	<script type="text/javascript">{literal}
		// really ugly 'fix' for http://code.google.com/p/chromium/issues/detail?id=1854
		// the last text box before the first password field are assumed to be a username,
		//   but the saved username COULD be a nickname OR email...
		// ... so we take away those text boxes!
		if (navigator && navigator.userAgent && navigator.userAgent.search(/Chrome/) != -1) {
			document.getElementById('realname').disabled = true;
			document.getElementById('nickname').disabled = true;
			document.getElementById('email').disabled = true;
			AttachEvent(window,'load',reEnableTextBoxes1,false);
		}
		function reEnableTextBoxes1() {
			//autofill happens jsut after 'onload'
			setTimeout("reEnableTextBoxes2()",400);
		} 
		function reEnableTextBoxes2() {
			document.getElementById('realname').disabled = false;
			document.getElementById('nickname').disabled = false;
			document.getElementById('email').disabled = false;
		}
	{/literal}</script>
	  <div class="fieldnotes">Die E-Mail-Adresse wird für Benachrichtigungen benötigt,
	  wenn es Änderungswünsche zu Bildern gibt. Außerdem erlaubt sie Leuten, die an den
	  Bildern interessiert sind, die Kontaktaufnahme; ob die Adresse öffentlich sichtbar sein soll,
	  kann eingestellt werden.</div>
	
	{if $errors.email}</div>{/if}
	
    <fieldset>
    <legend>E-Mail: Privatsphäre</legend>
    
	    <input {if $profile->public_email eq 0}checked{/if} type="radio" name="public_email" id="public_email_no" value="0">
	    <label for="public_email_no">E-Mail-Adresse verstecken
	    (Die Kontaktaufnahme über die Seite ist noch immer möglich, die E-Mail-Adresse wird aber nicht angezeigt; Achtung:
	    Mit einer <b>Antwort</b>-Mail auf die empfangene Kontaktmail gibt man natürlich seine E-Mail-Adresse preis!)
	    </label>
	    
	    <br/>
	    
	    <input {if $profile->public_email eq 1}checked{/if} type="radio" name="public_email" id="public_email_yes" value="1">
	    <label for="public_email_yes">E-Mail-Adresse anzeigen 
	    </label>
   
    
    </fieldset>
    
  

</div>

<div class="field">

	<label for="gravatar">Gravatar:</label>
	<img src="http://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50" align="left" alt="{$profile->realname|escape:'html'}'s Gravatar" style="padding-right:10px"/>
	
	<div class="fieldnotes">Um ein Avatar einzurichten oder zu ändern, bitte {external href="http://www.gravatar.com" text="gravatar.com" target="_blank"} besuchen und dabei die oben angegebene E-Mail-Adresse verwenden.</div>
	
</div>

</fieldset>

<fieldset>
<legend>Passwort ändern</legend>
<p style="color:green">Dieser Abschnitt muss nur ausgefüllt werden, wenn das Passwort geändert werden soll.</p>

<div class="field">
	{if $errors.oldpassword}<div class="formerror"><p class="error">{$errors.oldpassword}</p>{/if}
		<label for="oldpassword">Aktuelles Passwort:</label>
		<input id="oldpassword" name="oldpassword" type="password" value="{$profile->oldpassword|escape:'html'}" size="35"/>
		<div class="fieldnotes">Bitte das alte, zu ändernde Passwort eingeben.</div>
	{if $errors.oldpassword}</div>{/if}
</div>
<div class="field">
	{if $errors.password1}<div class="formerror"><p class="error">{$errors.password1}</p>{/if}
		<label for="password1">Neues Passwort:</label>
		<input id="password1" name="password1" type="password" value="{$profile->password1|escape:'html'}" size="35"/>
		<div class="fieldnotes">Bitte hier neues Passwort eingeben. Um das alte Passwort beizubehalten, bitte leer lassen.</div>
	{if $errors.password1}</div>{/if}
</div>
<div class="field">
	{if $errors.password2}<div class="formerror"><p class="error">{$errors.password2}</p>{/if}
		<label for="password2">Neues Passwort wiederholen:</label>
		<input id="password2" name="password2" type="password" value="{$profile->password2|escape:'html'}" size="35"/>
		<div class="fieldnotes">Um Tippfehler auszuschließen, bitte das neue Passwort erneut eingeben.</div>
	{if $errors.password2}</div>{/if}
</div>

</fieldset>

<fieldset>
<legend>Persönliche Angaben</legend>


<div class="field">
	
	{if $errors.website}<div class="formerror"><p class="error">{$errors.website}</p>{/if}
	
	
	<label for="website" class="nowrap">Website:</label>
	<input type="text" id="website" name="website" value="{$profile->website|escape:'html'}" size="50"/>

	<div class="fieldnotes">Hier kann die Adresse einer persönlichen Website oder Blogs o.ä. angegeben werden.
	Auf der Profilseite wird dann ein Link darauf gesetzt.</div>
	
	
	{if $errors.website}</div>{/if}
</div>


<div class="field">

	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	
	<label for="grid_reference">Heimatquadrat:</label>
	<input type="text" id="grid_reference" name="grid_reference" value="{$profile->grid_reference|escape:'html'}" size="8" />
	
	<div class="fieldnotes">Hier können die Herkunftskoordinaten eingetragen werden.</div>

	{if $errors.grid_reference}</div>{/if}
</div>





<div class="field">

	<label for="about_yourself">Persönliche Anmerkungen für die Profilseite:</label>
	
	 
	<textarea name="about_yourself" id="about_yourself" rows="10" cols="85">{$profile->about_yourself|escape:'html'}</textarea>

	<div class="fieldnotes"><span style="color:red">Anmerkung: HTML-Code wird entfernt,
	Adressen/URLs werden aber in Links umgewandelt.</span><br/>
	Tipp: Mit <span style="color:blue">[[TPT2769]]</span> oder
	<span style="color:blue">[[34]]</span> können Planquadrate oder Bilder verlinkt werden.
	</div>


</div>


<!--div class="field">


	<label for="age_group">Altersgruppe:</label>

	<select name="age_group" id="age_group"> 
	<option value=""></option>
	<option value="11" {if $profile->age_group == 11} selected="selected"{/if}>11 oder jünger</option>
	<option value="18" {if $profile->age_group == 18} selected="selected"{/if}>12-18</option>
	<option value="25" {if $profile->age_group == 25} selected="selected"{/if}>19-25</option>
	<option value="50" {if $profile->age_group == 50} selected="selected"{/if}>26-50</option>
	<option value="70" {if $profile->age_group == 70} selected="selected"{/if}>51-70</option>
	<option value="90" {if $profile->age_group == 90} selected="selected"{/if}>71 oder älter</option>
	</select>
	
	<div class="fieldnotes">Diese Information ist nicht öffentlich sichtbar, sondern
	hilft uns abzuschätzen, welche Neuerungen gewünscht sein könnten.</div>
	
</div--> 


</fieldset>

<fieldset>
<legend>Einstellungen</legend>
 

{if $largeimages}
<div class="field"> 
	<label for="upload_size" class="nowrap">Standard-Bildgröße</label>
	
	<select name="upload_size" id="upload_size"> 
		<option value="{$stdsize}" {if $profile->upload_size == $stdsize} selected="selected"{/if}>{$stdsize} x {$stdsize} (Normalgröße)</a>
		{foreach item=cursize from=$sizes}
		<option value="{$cursize}" {if $profile->upload_size == $cursize} selected="selected"{/if}>{$cursize} x {$cursize}</a>
		{/foreach}
		{if $showorig}
		<option value="65536" {if $profile->upload_size > 65530} selected="selected"{/if}>Wie hochgeladen</a>
		{/if}
	</select>

	 
	<div class="fieldnotes">Hier kann die Standardgröße beim Hochladen von Bildern angegeben werden. Abweichende Größen können auch noch beim Hochladen eingestellt werden.</div>
</div>
{/if}

{if $canclearexif}
<div class="field"> 
	<label for="clear_exif" class="nowrap">EXIF-Daten löschen</label>
	
	<input type="checkbox" name="clear_exif" id="clear_exif" {if $profile->clear_exif}checked{/if} value="1"/><!--br/-->
	 
	<div class="fieldnotes">Beim Einreichen Metadaten (wie Aufnahmezeit oder Kameratyp) der Bilder löschen. Hier kann die Standardeinstellung geändert werden, abweichende Einstellungen können noch beim Hochladen von Bildern vorgenommen werden..</div>
</div>
{/if}


<div class="field"> 
	<label for="message_sig">Signatur für Mitteilungen:</label>
	
	<textarea name="message_sig" id="message_sig" rows="4" cols="60">{$profile->message_sig|escape:'html'}</textarea>

	 
	<div class="fieldnotes">Diesen Text automatisch an Mitteilungen anhängen, die über die Seite verschickt werden.<br/>
	(bis zu 250 Zeichen) 
	<input type="button" value="Signatur vorschlagen" onclick="this.form.message_sig.value='-- '+this.form.realname.value+' http://{$http_host}/profile/{$user->user_id}'"/></div>
</div>


<div class="field"> 
	<label for="ticket_public">Anonymität in Tickets:</label>
	
	<select name="ticket_public" id="ticket_public">
		<option value="no">Meinen Namen nicht anzeigen.</option>
		<option value="owner" {if $profile->ticket_public eq 'owner'} selected{/if}>Meinen Namen nur dem Einreicher des Bilds mitteilen.</option>
		<option value="everyone" {if $profile->ticket_public eq 'everyone'} selected{/if}>Meinen Namen anzeigen.</option>
	</select>
	 
	<div class="fieldnotes">Hier kann eingestellt werden, ob bei Änderungsvorschlägen von nun an der Name genannt werden soll.</div>
</div>


<div class="field"> 
	<label for="ticket_public_change">Anonymität in alten Tickets:</label>
	<select name="ticket_public_change" id="ticket_public_change">
		<option value="">Keine Änderung: Alte Tickets nicht ändern.</option>
		<option value="no">Meinen Namen nicht anzeigen.</option>
		<option value="owner">Meinen Namen nur dem Einreicher des Bilds mitteilen.</option>
		<option value="everyone">Meinen Namen anzeigen.</option>
	</select>
	 
	<div class="fieldnotes">Dieser Kasten kann verwendet werden, um die Anonymitäts-Einstellungen aller früheren Änderungsvorschläge zu ändern.</div>
</div>


<div class="field"> 
	<label for="ticket_option">Benachrichtigungs-Mails für Tickets:</label>
	<select name="ticket_option" id="ticket_option" size="1">
		<option value="all"{if $profile->ticket_option eq 'all'} selected{/if}>Benachrichtigungen für alle Änderungsvorschläge</option>
		<option value="major"{if $profile->ticket_option eq 'major'} selected{/if}>Nur größere Änderungen</option>
		<!--option value="digest"{if $profile->ticket_option eq 'digest'} selected{/if}>Receive Digest emails Once per Day</option-->
		<option value="none"{if $profile->ticket_option eq 'none'} selected{/if}>Keine Anfangsbenachrichtigung</option>
	</select>
	 
	<div class="fieldnotes">Hier kann der Empfang von Benachrichtigungs-Mails für bestimmte Änderungsvorschläge deaktiviert werden. Allerdings
	werden bei Kommentaren und beim Schließen von Tickets weiterhin Mails versandt (für den Fall, dass ein Moderator Informationen benötigt).</div>
</div>


<div class="field"> 
	<label for="sortBy" class="nowrap">Foren-Sortierung:</label>
	
	<select name="sortBy" id="sortBy" size="1">
	 	<option value="0">Neue Beiträge</option>
	 	<option value="1" {if $profile->getForumSortOrder() eq 1}selected{/if}>Neue Themen</option>
	 </select>
	 
	 <div class="fieldnotes">In der Standardeinstellung werden neue Themen angezeigt.</div>
</div>

<div class="field"> 
	<label for="search_results" class="nowrap">Suchergebnisse:</label>
	<select name="search_results" id="search_results" style="text-align:right" size="1"> 
		{html_options values=$pagesizes output=$pagesizes selected=$profile->search_results}
	</select> pro Seite
	
	<div class="fieldnotes">Voreinstellung der Anzahl von Suchergebnissen je Seite.</div>
</div>
  
  
<div class="field"> 
  
	<label for="slideshow_delay" class="nowrap">Dia-Zeit:</label>
	
	<select name="slideshow_delay" id="slideshow_delay" style="text-align:right" size="1">
		{html_options values=$delays output=$delays selected=$profile->slideshow_delay}
	</select> Sekunden
	
	<div class="fieldnotes">Wie lang soll ein Bild im Dia-Show-Modus angezeigt werden?</div>
</div>


</fieldset>



 	<input type="submit" name="savechanges" value="Änderungen speichern"/>
 	<input type="submit" name="cancel" value="Abbrechen"/>

{if ($profile->stats.squares gt 20) || ($profile->rights && $profile->rights ne 'basic')}
	<br/><br/><br/><br/><br/><br/>
	<fieldset>
	<legend>Funktionen bei Geograph</legend>


	<div class="field"> 

		<label for="moderator" class="nowrap">Moderator</label>
		{if strpos($profile->rights,'moderator') > 0}
			<input type="button" value="Moderatorenrechte abgeben" onclick="location.href = '/admin/moderation.php?relinquish=1';"/>

			<div class="fieldnotes">Wenn es nicht mehr möglich ist, uns bei der Moderation zu unterstützen, bitte obigen Knopf drücken.
			Um danach wieder Moderator zu werden, ist wieder eine "Bewerbung" erforderlich.</div>  
		{else}
			{if strpos($profile->rights,'traineemod') > 0}
				<input type="button" value="Demo-Moderations-Seite besuchen" onclick="location.href = '/admin/moderation.php?apply=1';"/>
			{else}
				<input type="button" value="Ich möchte Moderator werden" onclick="location.href = '/admin/moderation.php?apply=1';"/>
			{/if}

			<div class="fieldnotes">
			{if strpos($profile->rights,'traineemod') > 0}
				oder <input type="button" value="Bewerbung abbrechen" onclick="location.href = '/admin/moderation.php?relinqush=1';"/><br/><br/>
			{/if}

			Falls Interesse daran besteht, uns als Moderator zu unterstützen, bitte obigen Knopf drücken um eine Demo-Moderation durchzuführen.</div>
		{/if}
	</div>

	{if strpos($profile->rights,'ticketmod') > 0}
	<div class="field"> 

		<label for="moderator" class="nowrap">Tickets</label>
			<input type="button" value="Ticket-Moderatorenrechte abgeben" onclick="location.href = '/admin/tickets.php?relinqush=1';"/>

	</div>
	{/if}

	</fieldset>
{/if}


 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
