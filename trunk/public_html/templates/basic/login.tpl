{assign var="page_title" value="Login"}
{include file="_std_begin.tpl"}



<form action="{$script_uri}" method="post">
    
{if $inline}
   <h2>Login Required</h2>
   <p>You must log in to access this page. 
{else}
    <h2>Login</h2>
    <p>Please log in with your email address and password. 
{/if}

If you haven't
registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</p>

<label for="email">Your email address</label><br/>
<input id="email" name="email" value="{$email|escape:'html'}"/>
<span class="formerror">{$errors.email}</span>

<br/><br/>

<label for="password">Your password</label><br/>
<input size="12" type="password" id="password" name="password" value="{$password|escape:'html'}"/>
<span class="formerror">{$errors.password}</span>
<a title="email forgotten password" href="forgotten.php?email={$email|escape:'url'}">Forgot your password?</a>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>

<input type="submit" name="login" value="Login"/>
</form>  

    
    
{include file="_std_end.tpl"}
