{assign var="page_title" value="Passwort�nderung"}
{include file="_std_begin.tpl"}

<h2>Passwort�nderung</h2>

{dynamic}

{if $confirmation_status eq "ok"}
	<p>Vielen Dank, ab jetzt wird das neue Passwort verwendet.</p>
	

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Diese Passwort�nderung wurde bereits best�tigt!</p>

{else}
	<p>Bei der Best�tigung der Passwort�nderung ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">R�ckmeldung</a>.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
