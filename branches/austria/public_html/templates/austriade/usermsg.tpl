{assign var="page_title" value="Nachricht"}
{include file="_std_begin.tpl"}
{dynamic}
{if $throttle}
	<h2>Entschuldigung</h2>
	<p>Um Missbrauch der Kontakt-Seite durch Spammer zu verhindern, ist die Zahl
	der Nachrichten, die in einer Stunde versandt werden k�nnen begrenzt. Wir bitten um etwas Geduld.</p>

{else}

{if $recipient->registered}
	<h2>Nachricht an {$recipient->realname|escape:'html'} senden</h2>

	{if $error}
		<h2>Entschuldigung</h2>
		<p>Die Nachricht konnte nicht versandt werden: {$error}</p>
	{/if}
	
	{if $sent}
		<p>Danke, die Nachricht wurde verschickt.</p>
		<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
		<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Achtung" width="30" height="24" align="left" style="margin-right:10px"/>
		Bitte beachten Sie, dass der Empf�nger die Nachricht unter Umst�nden <b>nicht</b> annimmt, falls Sie als Absender eine <b>Yahoo- oder AOL-</b>Adresse angeben,
		weil Yahoo und AOL eine diesbez�glich ung�nstige Mail-Konfiguration einsetzen.</div>
	{elseif $verification}
		<form method="post" action="/usermsg.php">
		
		<div class="interestBox">
		
			<p><b>Wir m�gen keinen Spam!</b><br/>
			Um die Teilnehmer vor Spam zu sch�tzen, ist es erforderlich, dieses Formular auszuf�llen:</p>

			<input type="hidden" name="to" value="{$recipient->user_id|escape:'html'}">

			<input type="hidden" name="from_name" value="{$from_name|escape:'html'}"/>
			<input type="hidden" name="from_email"  value="{$from_email|escape:'html'}"/>
			<input type="hidden" name="sendcopy" value="{if $sendcopy}on{/if}"/>				
			<input type="hidden" name="verification" value="{$verification|escape:'html'}"/>				
			<textarea name="msg" style="display:none">{$msg|escape:'html'}</textarea>

			<br />

			<img src="/stuff/captcha.jpg.php?{$verification|escape:'html'}" style="padding:20px; border:1px solid silver"/><br />
			<br />

			<label for="verify">Um fortzufahren bitte obige Buchstaben eingeben:</label>
			<input type="text" name="verify" id="verify"/><br />
		</div>
		<br />
		
		<input type="submit" name="send" value="Send">
		</form>	
	{else}
		{if $invalid_email} 
		<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Warnung" width="50" height="44" align="left" style="margin-right:10px"/>
			Wir haben keine g�ltigen Kontaktinformationen f�r diesen Teilnehmer. Die Nachricht wird daher
			an das Geograph-Deutschland-Team geschickt werden, das vielleicht weiterhelfen kann.
		</div>
		<br/><br/><br/>
		{/if}
		<form method="post" action="/usermsg.php">
		<div class="interestBox">
			<input type="hidden" name="to" value="{$recipient->user_id|escape:'html'}">

			<label for="from_name">Eigener Name</label><br />
			<input type="text" name="from_name" id="from_name" value="{$from_name|escape:'html'}"/>
			<span class="formerror">{$errors.from_name}</span>

			<br/><br/>
			<label for="from_email">Eigene Mail-Adresse</label><br />
			<input type="text" name="from_email" id="from_email" value="{$from_email|escape:'html'}"/>
			<span class="formerror">{$errors.from_email}</span>
			{if $user->registered}
				&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sendcopy" id="sendcopy" value="on" {if $sendcopy} checked="checked"{/if}/> <label for="sendcopy">Kopie an mich schicken</label>
			{/if}

			<br/><br/>
			<label for="msg">Nachricht</label><br />
			<textarea rows="10" cols="60" name="msg" id="msg"{if $user->message_sig} onfocus="if (this.value=='') {literal}{{/literal}this.value='{$user->message_sig|escape:'javascript'}';setCaretTo(this,0); {literal}}{/literal}"{/if}>{$msg|escape:'html'}</textarea>
			<br/>
			<span class="formerror">{$errors.msg}</span>

			<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Achtung" width="30" height="24" align="left" style="margin-right:10px"/>
			Wenn sich die Nachricht auf ein Bild bezieht, bitten wir darum, dieses Bild klar zu benennen:
			Der Teilnehmer kann an vielen Orten Bilder aufgenommen haben und daher nicht wissen um welches Bild es geht!</div>
			<br />
			<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Achtung" width="30" height="24" align="left" style="margin-right:10px"/>
			Bitte beachten Sie, dass der Empf�nger die Nachricht unter Umst�nden <b>nicht</b> annimmt, falls Sie als Absender eine <b>Yahoo- oder AOL-</b>Adresse angeben,
			weil Yahoo und AOL eine diesbez�glich ung�nstige Mail-Konfiguration einsetzen.</div>
			<br/>
		</div>
		
		<div style="float:right; position:relative; vertical-align:top;">
			- <b>{external href="http://akismet.com/" text="Protected by Akismet"}</b> -
			<span style="font-size:0.8em">{external href="http://wordpress.com/" text="Blog with WordPress"}</span> -
		</div>
		
		<input type="submit" name="send" value="Send">
		</form>
	{/if}
{else}
	<h2>Entschuldigung</h2>
	<p>Die Nachricht kann nicht versandt werden, weil der Empf�nger nicht bekannt ist.</p>
{/if}
{/if}

{/dynamic}
{include file="_std_end.tpl"}
