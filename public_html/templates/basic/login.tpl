{assign var="page_title" value="Login"}
{include file="_std_begin.tpl"}
{dynamic}

<div style="max-width:640px">

<form action="{$script_uri}" method="post">

{if $forced}
   <h2>Please confirm new Terms of Service</h2>
   <p>Please login again with your username and password once you have read and completed this form

{elseif $inline}
   <h2>Login Required</h2>
   <p>You must log in to access this page. 
{else}
    <h2>Login</h2>
    <p>Please log in with your email address and password. 
{/if}

{if $forced}
	<div style="border:1px solid black; border-radius:20px;padding:20px;margin-bottom:20px">
	<h3 style="margin-top:0">Geograph Project Limited - updated Terms of Service (2026)</h3>

	<p>Before continuing to the website, you need to review and accept Geograph's revised Terms of Service <a href="/help/terms" onclick="showtickbox()"
	target="_blank">{$self_host}/help/terms</a>.  We have updated these in light of recent legislation, particularly the 
	Online Safety Act (2023) and to ensure they are current and accurate.  There have been no substantive changes to what we expect of you 
	or what you can expect of us. 

	{if $company}
		<p>We also ask to confirm that you wish to remain a company member. This is just to ensure we are keeping our member databasee updated. We will contact you if no longer wish to remain a member.
	{/if}

	<p>Here is a summary of what has changed:
	<ul>
		<li>The name has been changed from "Terms of Use" to "Terms of Service" 
                <li>The previous "quick version" has been removed
                <li>The first section has been rewritten as a "Welcome and definitions"
                <li>New terms "Personal Data" and "User Generated Content" have been introduced, to be consistent with current legislation
                <li>A statement has been added that users should be old enough to understand these Terms, or get a parent or guardian to explain them
                <li>The list of unacceptable content types has been updated to reflect the Online Safety Act, together with a statement that we moderate User Generated Content and reserve the right to remove unacceptable content
                <li>New sections have been added on how to contact us and how we handle complaints 
                <li>General updating and removal of inconsistencies, including use of simpler language where possible
	</ul>

	<div id="showbox">
		<input type=checkbox name=agree_terms required id=agree>
		<label for=agree> I accept the <a href="/help/terms" target="_blank" onclick="showtickbox()">Terms of Service</a></label> <i>(opens in new tab/window)</i>
	</div>
	<div id="hidebox" style="display:none">
		<b>Please open and review the <a href="/help/terms" target="_blank" onclick="showtickbox()">Terms of Service</a> document</b> <i>(opens in new tab/window)</i>
	</div>

	{if $company}
		<br>
		<input type=checkbox name=agree_company id=company>
		<label for=company> I wish to remain a Company Member of Geograph Project Limited</label>
	{/if}
	</div>
	<script>{literal}
		//hide with JS, it visible even without JS!
		function hidetickbox() {
			if ('BroadcastChannel' in window) {
				document.getElementById("showbox").style.display='none';
				document.getElementById("hidebox").style.display='';

				const channel = new BroadcastChannel('terms_viewer');
				channel.onmessage = (event) => {
				    if (event.data === 'terms_opened') {
					showtickbox();
				        console.log("User successfully opened the terms.");
				    }
				};
			}
		}

		AttachEvent(window,window.addEventListener?'DOMContentLoaded':'load',hidetickbox,false);
		function showtickbox() {
			document.getElementById("showbox").style.display='';
			document.getElementById("hidebox").style.display='none';
		}
	</script>{/literal}
{else}
	If you haven't registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</p>
{/if}

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

<input type="submit" name="login" value="Login" {if $forced} onclick="showtickbox()"{/if}/>

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
