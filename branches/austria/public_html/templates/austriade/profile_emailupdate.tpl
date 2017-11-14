{assign var="page_title" value="Neue E-Mail-Adresse"}
{include file="_std_begin.tpl"}

<h2>Neue E-Mail-Adresse</h2>

{dynamic}

{if $confirmation_status eq "ok"}
	<p>Vielen Dank, ab jetzt wird die neue E-Mail-Adresse verwendet.</p>
	

{elseif $confirmation_status eq "alreadycomplete"}
	<p>Diese E-Mail-Adresse wurde bereits best�tigt!</p>

{elseif $confirmation_status eq "expired"}
	<p>Die Adress�nderung liegt zu lange zur�ck und kann aus Sicherheitsgr�nden nicht mehr
	durchgef�hrt werden. Selbstverst�ndlich kann die E-Mail-Adresse erneut
	<a title="Nutzerprofil �ndern" href="/profile.php?edit=1">im Profil</a> ge�ndert werden.</p>

{else}
	<p>Bei der Best�tigung der neuen E-Mail-Adresse ist ein Problem aufgetreten.
	Wenn das Problem fortbesteht, bitten wir um <a href="contact.php">R�ckmeldung</a>.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
