{assign var="page_title" value="Bestätigungsmail"}
{include file="_std_begin.tpl"}
{dynamic}

{if $lock_seconds}
<script type="text/javascript">
//<![CDATA[
	AttachEvent(window,'load',function() {ldelim}buttontimer('submitbutton', {$lock_seconds});{rdelim},false);
//]]>
</script>
{/if}
<form method="post" action="/register.php">
<input type="hidden" name="CSRF_token" value="{$CSRF_token}" />
<input type="hidden" name="confirm" value="{$query_confirm|escape:'html'}" />
<input type="hidden" name="u" value="{$query_u|escape:'html'}" />


{if $confirmpass}
<h2>Passwortwechsel bestätigen</h2>
{elseif $confirmmail}
<h2>Adressänderung bestätigen</h2>
{else}{*$confirmreg*}
<h2>Registrierungsbestätigung</h2>
{/if}
{if $confirmation_status=='csrf'}
<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
Aus <a href="/help/csrf">Sicherheitsgründen</a> konnte der Vorgang nicht bearbeitet werden.
Wir bitten darum, die Eingaben zu überprüfen und erneut abzusenden.
</div>
{/if}
<p>Zur
{if $confirmpass}
Änderung des Passworts
{elseif $confirmmail}
Änderung der E-Mail-Adresse
{else}{*$confirmreg*}
Bestätigung der Registrierung
{/if}
ist die Eingabe des Passworts erforderlich.</p>

<label for="password">{if $confirmpass}Neues{else}Aktuelles{/if} Passwort:</label>
<br/>
<input id="password" name="password" type="password" value="{$query_pass|escape:'html'}" size="35"/>
{if $confirmation_status=='auth'}<span class="formerror">Falsches Passwort oder Zugangssperre{if $lock_seconds} &ndash; gesperrt für {$lock_seconds|format_seconds:120}{/if}</span>{/if}

<br/></br/>

<input type="submit" name="submit" id="submitbutton" value="Bestätigen"/>
</form>

{/dynamic}
{include file="_std_end.tpl"}
