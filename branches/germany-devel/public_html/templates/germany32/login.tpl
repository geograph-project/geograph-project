{assign var="page_title" value="Login"}
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
   <h2>Login Required</h2>
{else}
    <h2>Login</h2>
{/if}

{if $errors.csrf}
<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
Your input could not be processed due to <a href="/help/csrf">security reasons</a>. Please verify the below form and try again.
</div>
{/if}

{if $inline}
   <p>You must log in to access this page. 
{else}
    <p>Please log in with your email address and password. 
{/if}

If you haven't
registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</p>

<label for="email">Your email address or nickname</label><br/>
<input id="email" name="email" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span>

<br/><br/>

<label for="password">Your password (case sensitive)</label><br/>
<input size="12" type="password" id="password" name="password" value="{$password|escape:'html'}"/>
<span class="formerror">{$errors.password}{if $lock_seconds} - Login blocked for {$lock_seconds|format_seconds:120}; you may also log in by <a href="/forgotten.php?email={$email|escape:'url'}">resetting your password</a>.{/if}</span>
{if ! $lock_seconds}<a title="email forgotten password" href="/forgotten.php?email={$email|escape:'url'}">Forgot your password?</a>{/if}

<br/><br/>

<input type="checkbox" name="remember_me" id="remember_me" value="1" {if $remember_me}checked="checked"{/if}>
<label for="remember_me">Remember me - login automatically in future</label>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>

<input type="submit" name="login" value="Login" id="loginbutton"/>

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
	<div class="interestBox">Entering your details above should allow you to continue without loss of data. The exception is actually uploading an image, for example on Step 2 of the submission process, which will need to be resent. <br/><br/>
	It's highly recommended to use the 'Remember me' function to reduce the likelyhood of seeing this message, even on public computers; using the Logout function will also clear the 'Remember me' cookie.</div>
{/if}

</form>


{/dynamic}
{include file="_std_end.tpl"}
