{assign var="page_title" value="Login"}
{include file="_std_begin.tpl"}
{dynamic}

<div style="max-width:600px">

<form action="{$script_uri}" method="post">

{if $inline}
   <h2>Login Required</h2>
   <p>You must log in to access this page. 
{else}
    <h2>Login</h2>
    <p>Please log in with your email address and password. 
{/if}

If you haven't registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</p>

<label for="email">Your email address or nickname</label><br/>
<input size="20" id="email" name="email" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span>

<br/><br/>

<label for="password">Your password (case sensitive)</label><br/>
<input size="20" type="password" id="password" name="password" value="{$password|escape:'html'}"/>
<span class="formerror">{$errors.password}</span>
<a title="email forgotten password" href="/forgotten.php?email={$email|escape:'url'}">Forgot your password?</a>

<br/><br/>

<input type="checkbox" name="remember_me" id="remember_me" value="1" {if $remember_me}checked="checked"{/if}>
<label for="remember_me">Remember me - login automatically in future</label>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>

<input type="submit" name="login" value="Login"/>

<br/><br/>

<div class="interestBox">
	Our websites:
	<ul>
		<li><b>Geograph Britain and Ireland</b></li>
		<li><b>Geograph Ireland</b></li>
	</ul>
	... share the same database. You can use either login here.
</div>

<br style="clear:both"/>

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
	<div class="interestBox"><sup style="color:red">new!</sup> Entering your details above should allow you to continue without loss of data. The exception is actually uploading an image, for example on Step 2 of the submission process, which will need to be re-sent. <br/><br/>
	It's highly recommended to use the 'Remember me' function to reduce the likelihood of seeing this message, even on public computers; using the logout function will also clear the 'Remember me' cookie.</div>
{/if}

</form>

</div>
{/dynamic}
{include file="_std_end.tpl"}
