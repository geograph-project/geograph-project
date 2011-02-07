{include file="_std_begin.tpl"}
{dynamic}
{if $sent}

<h2>Confirmation sent to {$email}</h2>
<p>You should receive your a confirmation message shortly, once you have clicked the link in the message, your password will be changed.
If you have any problems, please <a title="Contact Us" href="contact.php">contact us</a></p>

{else}


<h2>Forgot your password?</h2>


<form action="/forgotten.php" method="post">

<p>Enter your email address and your new password below and we'll email you a confirmation mail...</p>

<label for="reminder">Your email address</label><br/>
<input id="reminder" name="reminder" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span><br/>
<label for="password1">Your new password</label><br/>
<input id="password1" type="password" name="password1" value="{$password1|escape:'html'}"/>
<span class="formerror">{$errors.password1}</span><br/>
<label for="password2">Confirm the new password</label><br/>
<input id="password2" type="password" name="password2" value="{$password2|escape:'html'}"/>
<span class="formerror">{$errors.password2}</span><br/><br/>
<input type="submit" name="send" value="Change password"/>
</form>

{/if}

{/dynamic}
{include file="_std_end.tpl"}
