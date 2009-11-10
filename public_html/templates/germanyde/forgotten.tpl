{include file="_std_begin.tpl"}
{dynamic}
{if $sent}

<h2>Bestätigungsmail an {$email} wurde versandt</h2>
<p>Nach dem Aufrufen des in der Bestätigungsmail enthaltenen Links wird das neue Passwort aktiviert. Falls dabei Probleme
auftreten, bitten wir um <a title="Kontaktformular" href="contact.php">Rückmeldung</a>.</p>

{else}


<h2>Passwort vergessen?</h2>


<form action="/forgotten.php" method="post">
    
<p>Bitte E-Mail-Adresse und ein neues Passwort eingeben. Nach der Eingabe werden wir eine Bestätigungsmail versenden.</p>

<label for="reminder">E-Mail-Adresse</label><br/>
<input id="reminder" name="reminder" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span><br/>
<label for="password1">Neues Passwort</label><br/>
<input id="password1" type="password" name="password1" value="{$password1|escape:'html'}"/>
<span class="formerror">{$errors.password1}</span><br/>
<label for="password2">Passwort wiederholen</label><br/>
<input id="password2" type="password" name="password2" value="{$password2|escape:'html'}"/>
<span class="formerror">{$errors.password2}</span><br/><br/>
<input type="submit" name="send" value="Passwort ändern"/>
</form>

{/if}
    
{/dynamic}    
{include file="_std_end.tpl"}
