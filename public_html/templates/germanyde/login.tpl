{assign var="page_title" value="Anmelden"}
{include file="_std_begin.tpl"}
{dynamic}

{if $lock_seconds}
<script type="text/javascript">
//<![CDATA[
	AttachEvent(window,'load',function() {ldelim}buttontimer('loginbutton', {$lock_seconds});{rdelim},false);
//]]>
</script>
{/if}
<form action="{$script_uri}" method="post">
<input type="hidden" name="CSRF_token" value="{$CSRF_token}" />

{if $inline}
   <h2>Bitte Einloggen</h2>
{else}
    <h2>Anmelden</h2>
{/if}

{if $errors.csrf}
<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
Aus <a href="/help/csrf">Sicherheitsgründen</a> konnte der Vorgang nicht bearbeitet werden.
Wir bitten darum, die Eingaben zu überprüfen und erneut abzusenden.
</div>
{/if}

{if $inline}
   <p>Der Zugriff auf diese Seite ist ohne Anmeldung nicht möglich.
{else}
    <p>Bitte mit E-Mail-Adresse und Passwort einloggen.
{/if}

Zum Anmelden ist eine Registrierung nötig.
Falls diese noch nicht erfolgt ist, <a title="registrieren" href="/register.php">bitte hier registrieren</a>, die Registrierung ist einfach, schnell und kostenlos!</p>

<label for="email">E-Mail-Adresse oder Benutzername</label><br/>
<input id="email" name="email" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span>

<br/><br/>

<label for="password">Passwort (Groß-/Kleinschreibung beachten)</label><br/>
<input size="12" type="password" id="password" name="password" value="{$password|escape:'html'}"/>
<span class="formerror">{$errors.password}{if $lock_seconds} &ndash; gesperrt für {$lock_seconds|format_seconds:120}; einloggen durch <a href="/forgotten.php?email={$email|escape:'url'}">zurücksetzen des Passworts</a>?{/if}</span>
{if ! $lock_seconds}<a title="vergessenes Passwort zurücksetzen" href="/forgotten.php?email={$email|escape:'url'}">Passwort vergessen?</a>{/if}

<br/><br/>

<input type="checkbox" name="remember_me" id="remember_me" value="1" {if $remember_me}checked="checked"{/if}>
<label for="remember_me">In Zukunft automatisch einloggen</label>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>

<input type="submit" name="login" value="Einloggen" id="loginbutton"/>

{foreach from=$_post key=key item=value}
	{if $key eq 'email' || $key eq 'password' || $key eq 'remember_me' || $key eq 'login' || $key eq 'CSRF_token'}
	{elseif strpos($value,"\n") !== false}
		<textarea name="{$key|escape:"html"}" style="display:none">{$value|escape:"html"}</textarea>
	{else}
		<input type="hidden" name="{$key|escape:"html"}" value="{$value|escape:"html"}"/>
	{/if}
{/foreach}
{if count($_post) && !$_post.login}
	<br/><br/>
	<div class="interestBox">Nach Eingabe der obigen Daten kann ohne Datenverlust fortgefahren werden; nur das Hochladen von Bildern muss wiederholt werden.<br/><br/>
	Wir empfehlen das &bdquo;automatische Einloggen&rdquo; um das erneute manuelle Einloggen zu vermeiden. Das ist auch auf einem öffentlich zugänglichen Computer möglich, da beim Ausloggen das &bdquo;Einlog-Cookie&rdquo; gelöscht wird.</div>
{/if}

</form>


{/dynamic}
{include file="_std_end.tpl"}
