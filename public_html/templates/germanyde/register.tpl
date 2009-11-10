{assign var="page_title" value="Registrierung"}
{include file="_std_begin.tpl"}

<h2>Registrierung</h2>

{dynamic}

{if $registration_ok}

	<p>Vielen Dank für die Registrierung. Wir haben eine Bestätigungsmail verschickt,
	in der ein Bestätigungs-Link angegeben ist. Nach dem Aufrufen dieses Links ist die
	Registrierung vollständig.</p>

        <p><b>Falls keine Bestätigungsmail kommt:</b> Die Mail könnte im SPAM- bzw. Junk-Ordner gelandet sein.
	Außerdem kommt es immer wieder vor, dass die E-Mail-Adresse falsch angegeben wurde und die Bestätigungsmail
	dann natürlich nicht erfolgreich versandt werden konnte. Oft kommt es auch zu Verzögerungen, weil
	manche Anbieter nur verzögert E-Mails annehmen (z.B. wegen sog. Graylistings); insbesondere Yahoo fällt
	diesbezüglich gelegentlich auf.</p>


{elseif $confirmation_status eq "ok"}
	<p>Herzlichen Glückwunsch, die Registrierung ist abgeschlossen. Wir 
	wünschen viel Spass!</p>
	
	<p>Auf der <a title="Profil anzeigen" href="/profile.php">Profilseite</a>
	sind nun weitere Einstellungen möglich.
	</p>

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Die Registrierung ist bereits abgeschlossen. Bitte mit Benutzernamen und Passwort
	<a title="Hier anmelden" href="/login.php">einloggen</a>.</p>

{elseif $confirmation_status eq "fail"}
	<p>Bei der Bestätigung der Registrierung ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">Rückmeldung</a>.</p>
{else}

	<form action="register.php" method="post">

	<p>Vor dem Einreichen von Bildern ist es erforderlich, sich zu registrieren. Die Registrierung ist aber
	schnell, schmerz- und natürlich kostenlos.</p>

	<label for="name">Name</label><br/>
	<input id="name" name="name" value="{$name|escape:'html'}"/>
	<span class="formerror">{$errors.name}</span>

	<br/><br/>

	<label for="email">E-Mail-Adresse</label><br/>
	<input id="email" name="email" value="{$email|escape:'html'}"/>
	<span class="formerror">{$errors.email}</span>

	<br/><br/>

	<label for="password1">Passwort</label><br/>
	<input size="12" type="password" id="password1" name="password1" value="{$password1|escape:'html'}"/>
	<span class="formerror">{$errors.password1}</span>

	<br/><br/>
	<label for="password2">Passwort wiederholen</label><br/>
	<input size="12" type="password" id="password2" name="password2" value="{$password2|escape:'html'}"/>
	<span class="formerror">{$errors.password2}</span>
	<br/>
	<span class="formerror">{$errors.general}</span>
	<br/>

	<input type="submit" name="register" value="Register"/>
	</form>  

	<p>Selbstverständlich werden wir die Daten nicht weitergeben!</p>

{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
