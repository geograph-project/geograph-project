{assign var="page_title" value="Anmelden"}
{include file="_std_begin.tpl"}
{dynamic}


<form action="{$script_uri}" method="post">

{if $inline}
   <h2>Bitte Einloggen</h2>
   <p>Der Zugriff auf diese Seite ist ohne Anmeldung nicht möglich.
{else}
    <h2>Anmelden</h2>
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
<span class="formerror">{$errors.password}</span>
<a title="vergessenes Passwort zurücksetzen" href="/forgotten.php?email={$email|escape:'url'}">Passwort vergessen?</a>

<br/><br/>

<input type="checkbox" name="remember_me" id="remember_me" value="1" {if $remember_me}checked="checked"{/if}>
<label for="remember_me">In Zukunft automatisch einloggen</label>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>

<input type="submit" name="login" value="Einloggen"/>

{foreach from=$_post key=key item=value}
	{if $key eq 'email' || $key eq 'password' || $key eq 'remember_me' || $key eq 'login'}
	{elseif strpos($value,"\n") !== false}
		<textarea name="{$key|escape:"html"}" style="display:none">{$value|escape:"html"}</textarea>
	{else}
		<input type="hidden" name="{$key|escape:"html"}" value="{$value|escape:"html"}"/>
	{/if}
{/foreach}
{if count($_post) && !$_post.login}
	<br/><br/>
	<div class="interestBox">Nach Eingabe der obigen Daten kann ohne Datenverlust fortgefahren werden; nur das Hochladen von Bildern muss wiederholt werden.<br/><br/>
	Wir empfehlen das &bdquo;automatische Einloggen&rdquo; um das erneute manuelle Einloggen zu vermeiden. Das ist auch auf einem öffentlich zugänglichen Computer möglich, da beim Ausloggen das &bdquo;Einlogg-Cookie&rdquo; gelöscht wird.</div>
{/if}

</form>


{/dynamic}
{include file="_std_end.tpl"}
