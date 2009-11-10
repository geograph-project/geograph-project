{assign var="page_title" value="Passwortänderung"}
{include file="_std_begin.tpl"}

<h2>Passwortänderung</h2>

{dynamic}

{if $confirmation_status eq "ok"}
	<p>Vielen Dank, ab jetzt wird das neue Passwort verwendet.</p>
	

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Diese Passwortänderung wurde bereits bestätigt!</p>

{else}
	<p>Bei der Bestätigung der Passwortänderung ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">Rückmeldung</a>.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
