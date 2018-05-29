{assign var="page_title" value="Registrierung"}
{include file="_std_begin.tpl"}

<h2>Registrierung</h2>

{dynamic}

{if $registration_ok}

	<p>Vielen Dank f�r die Registrierung. Wir haben eine Best�tigungsmail verschickt,
	in der ein Best�tigungs-Link angegeben ist. Nach dem Aufrufen dieses Links ist die
	Registrierung vollst�ndig.</p>

        <p><b>Falls keine Best�tigungsmail kommt:</b> Die Mail k�nnte im SPAM- bzw. Junk-Ordner gelandet sein.
	Au�erdem kommt es immer wieder vor, dass die E-Mail-Adresse falsch angegeben wurde und die Best�tigungsmail
	dann nat�rlich nicht erfolgreich versandt werden konnte. Oft kommt es auch zu Verz�gerungen, weil
	manche Anbieter nur verz�gert E-Mails annehmen (z.B. wegen sog. Graylistings); insbesondere Yahoo f�llt
	diesbez�glich gelegentlich auf.</p>


{elseif $confirmation_status eq "ok"}
	<p>Herzlichen Gl�ckwunsch, die Registrierung ist abgeschlossen. Wir 
	w�nschen viel Spass!</p>
	
	<p>Auf der <a title="Profil anzeigen" href="/profile.php">Profilseite</a>
	sind nun weitere Einstellungen m�glich.
	</p>

{elseif $confirmation_status eq "expired"}
	<p>Die Registrierung liegt schon zu lange zur�ck und wurde aus Sicherheitsgr�nden verworfen.
	Daher ist eine erneute
	<a title="Hier registrieren" href="/register.php">Anmeldung</a> erforderlich.</p>

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Die Registrierung ist bereits abgeschlossen. Bitte mit Benutzernamen und Passwort
	<a title="Hier anmelden" href="/login.php">einloggen</a>.</p>

{elseif $confirmation_status eq "fail"}
	<p>Bei der Best�tigung der Registrierung ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">R�ckmeldung</a>.</p>
{else}

	<form action="register.php" method="post">
	<input type="hidden" name="CSRF_token" value="{$CSRF_token}" />

	{if $errors.csrf}
	<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
	Aus <a href="/help/csrf">Sicherheitsgr�nden</a> konnte die Registrierung nicht durchgef�hrt werden.
	Wir bitten darum, die Eingaben zu �berpr�fen und erneut abzusenden.
	</div>
	{/if}

	<p>Vor dem Einreichen von Bildern ist es erforderlich, sich zu registrieren. Die Registrierung ist aber
	schnell, schmerz- und nat�rlich kostenlos.</p>

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
	<br/><br/>
			<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
			<img src="//{$static_host}/templates/basic/img/icon_alert.gif" alt="Achtung" width="30" height="24" align="left" style="margin-right:10px"/>
			Bitte beachten Sie, dass wir die von Ihnen eingegebenen Daten in ihrem Profil speichern. W�hrend der Name nach au�en
			sichtbar ist, werden die anderen Eingaben nur intern verwendet. Zus�tzlich werden Ihnen beispielsweise bei R�ckfragen zu
			Ihren Beitr�gen E-Mails geschickt werden. Details hierzu finden Sie in der <a href="/help/privacy">Datenschutzerkl�rung</a>.</div>
			<input type="checkbox" id="confirmdata" name="confirmdata" value="1"{if $confirmdata} checked="checked"{/if} /> <label for="confirmdata">Ich habe verstanden, dass meine Daten gespeichert und wie beschriebenen verwendet werden!</label>
			<br/>
			<span class="formerror">{$errors.confirmdata}</span>
	<br/>
	<span class="formerror">{$errors.general}</span>
	<br/>

	<input type="submit" name="register" value="Register"/>
	</form>  

	<p>Selbstverst�ndlich werden wir die Daten nicht weitergeben!</p>

{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
