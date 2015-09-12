{assign var="page_title" value="Neue E-Mail-Adresse"}
{include file="_std_begin.tpl"}

<h2>Neue E-Mail-Adresse</h2>

{dynamic}

{if $confirmation_status eq "ok"}
	<p>Vielen Dank, ab jetzt wird die neue E-Mail-Adresse verwendet.</p>
	

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Diese E-Mail-Adresse wurde bereits bestätigt!</p>

{elseif $confirmation_status eq "expired"}
	<p>Die Adressänderung liegt zu lange zurück und kann aus Sicherheitsgründen nicht mehr
	durchgeführt werden. Selbstverständlich kann die E-Mail-Adresse erneut
	<a title="Nutzerprofil ändern" href="/profile.php?edit=1">im Profil</a> geändert werden.</p>

{else}
	<p>Bei der Bestätigung der neuen E-Mail-Adresse ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">Rückmeldung</a>.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
