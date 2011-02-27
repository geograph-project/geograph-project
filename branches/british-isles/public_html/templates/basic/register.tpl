{assign var="page_title" value="Register"}
{include file="_std_begin.tpl"}

<h2>Register</h2>

{dynamic}

{if $registration_ok}

	<p>Thanks for registering - we've sent you an email, simply
	follow the link contained in the email to confirm your 
	registration</p>

        <p><b>Hotmail users please note:</b> Check your "Junk E-Mail" folder as we've found
	Hotmail sometimes treats the confirmation mail as spam.</p>


{elseif $confirmation_status eq "ok"}
	<p>Congratulations - your registration is complete. We 
	hope you'll enjoy contributing!</p>
	
	<p>You may be interested in reading our <a href="/article/Geograph-Introductory-letter">Geograph Introductory letter</a>.</p>

{elseif $confirmation_status eq "alreadycomplete"}
	<p>You have already completed the registration confirmation - please
	<a title="Login in here" href="/login.php">log in</a> using your username and password</p>

{elseif $confirmation_status eq "fail"}
	<p>Sorry, there was a problem confirming your registration.
	Please <a href="/contact.php">contact us</a> if the problem persists.</p>
{else}

	<form action="register.php" method="post">

{if $empty_referer}
<div  class="interestBox" id="msgg">
	<h1>Important Notice</h1>
	<p>We do not tolerate spam - images are moderated, and all forum posts (particully from new users) are subject to moderation.</p>
	<p>Also note that we have instigated a new policy that new users are don't get functional links on their profile page (making the page invisble to search engines).</p>
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	<p style="color:red">This makes geograph a useless target for spammers attempting to use Geograph profile pages to get links to their site.</p>
</div>
<script type="text/javascript">
{literal}
function hide_message() {
	hide_tree(101);
	document.getElementById('msgg').style.width='350px';
	document.getElementById('msgg').style.float='right';
}
{/literal}
</script>
<a href="javascript:void(hide_message());" id="show101">close message</a>
<div id="hide101" style="display:none">
{else}
<div class="interestBox" style="width:350px;float:right">
	The websites:
	<ul>
		<li><b>Geograph Britain and Ireland</b><br/><br/></li>
		<li><b>Geograph Ireland</b><br/><br/></li>
	</ul>
	... share the same user/registration database. An account created here can be used right away on either site.
</div>
{/if}

	<p>You must register before you can upload photos, but it's quick
	and painless and free. </p>

	<label for="name">Your name</label><br/>
	<input id="name" name="name" value="{$name|escape:'html'}"/>
	<span class="formerror">{$errors.name}</span>

	<br/><br/>

	<label for="email">Your email address</label><br/>
	<input id="email" name="email" value="{$email|escape:'html'}"/>
	<span class="formerror">{$errors.email}</span>

	<br/><br/>

	<label for="password1">Choose a password</label><br/>
	<input size="12" type="password" id="password1" name="password1" value="{$password1|escape:'html'}"/>
	<span class="formerror">{$errors.password1}</span>

	<br/><br/>
	<label for="password2">Confirm password</label><br/>
	<input size="12" type="password" id="password2" name="password2" value="{$password2|escape:'html'}"/>
	<span class="formerror">{$errors.password2}</span>
	<br/>
	<span class="formerror">{$errors.general}</span>
	<br/>

	<input type="submit" name="register" value="Register"/>
</div>
	</form>  

	<p>We won't sell or distribute your
	email address, we hate spam, we really do.</p>
{/if}

{/dynamic}
    
{include file="_std_end.tpl"}
