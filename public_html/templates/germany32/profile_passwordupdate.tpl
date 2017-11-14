{assign var="page_title" value="Password Change"}
{include file="_std_begin.tpl"}

<h2>Password Change</h2>

{dynamic}

{if $confirmation_status eq "ok"}
	<p>Thankyou, your profile will now use your new password.</p>
	

{elseif $confirmation_status eq "alreadycomplete"}
	<p>You have already confirmed this password change.</p>

{elseif $confirmation_status eq "expired"}
	<p>Your password change request has been expired for security reasons.
	Please
	<a title="Change password" href="/forgotten.php">change your password</a> again.</p>

{else}
	<p>Sorry, there was a problem confirming your password changes.
	Please <a href="contact.php">contact us</a> if the problem persists.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
