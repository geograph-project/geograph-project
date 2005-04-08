{include file="_std_begin.tpl"}
{dynamic}
{if $sent}

<h2>Reminder sent to {$email}</h2>
<p>You should receive your password reminder shortly. If you have
any problems, please <a title="Contact Us" href="contact.php">contact us</a></p>

{else}


<h2>Forgot your password?</h2>


<form action="/forgotten.php" method="post">
    
<p>Enter your email address below and we'll email you a reminder...</p>

<label for="reminder">Your email address</label><br/>
<input id="reminder" name="reminder" value="{$email|escape:'html'}"/>
<input type="submit" name="send" value="Remind me"/>
<span class="formerror">{$errors.email}</span>
</form>

{/if}
    
{/dynamic}    
{include file="_std_end.tpl"}
