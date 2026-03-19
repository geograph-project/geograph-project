{assign var="page_title" value="Register"}
{include file="_std_begin.tpl"}

<div style="max-width:60em">
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

	<form action="register.php" method="post" id="register_form">

{if $empty_referer}
	<div class="interestBox" id="msgg">
		<h1>Important Notice</h1>
		<p>We do not tolerate spam - images are moderated, and all forum posts (particularly from new users) are subject to moderation.</p>
		<p>Also note that we have instigated a new policy that new users don't get functional links on their profile page (making the page invisible to search engines).</p>
		<img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
		<p style="color:red">This makes Geograph a useless target for spammers attempting to use Geograph profile pages to get links to their site.</p>
	</div>
	<script type="text/javascript">
	{literal}
	function hide_message() {
		hide_tree(101);
		document.getElementById('msgg').style.display = 'none'; //maybe best just to hide
		document.getElementById('msgg').style.width='350px';
		document.getElementById('msgg').style.float='right';
	}
	{/literal}
	</script>
	<a href="javascript:void(hide_message());" id="show101">close message</a>
	<div id="hide101" style="display:none">
{else}
	<div class="interestBox">
	Our websites:
	<ul>
		<li><b>Geograph Britain and Ireland</b></li>
		<li><b>Geograph Ireland</b></li>
	</ul>
	... share the same database. <a href="/login.php">Login</a> if already have an account.
	</div>
{/if}

	<p>You need to register before you can upload photos or use the forums. Registration is simple, quick
	and free. </p>

	<ul>
		<li>Please read our <a href="/help/terms" target="_blank">Terms of Service</a> - you will be required to accept these before you can create a new account.<br><br>

		<li>We hope you will submit your own photos, but we use this <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons licence</a>, 
		which means that others will be able to re-use your photos for any purpose as long as they acknowledge you as the photographer.<br><br>

		<li>You are welcome to register, whether or not you intend to submit photos. 
		Please note that we moderate all user generated content and do not accept any harmful, illegal or spam/advertising material. <br><br>

		<li>We will send you a one-off email, to confirm your registration.</li>
	</ul>

	<hr><br>

	<label for="name">Your name (will be used as credit for any images submit)</label><br/>
	<input size="25" id="name" name="name" value="{$name|escape:'html'}" required/>
	<span class="formerror">{$errors.name}</span>

	<br/><br/>

	<label for="email">Your email address</label><br/>
	<input size="45" id="email" name="email" value="{$email|escape:'html'}" style="max-width:100%" required/>
	<span class="formerror">{$errors.email}</span>

	<br/><br/>
	<label for="age_diff">Current Age</label><br/>
	<input type=number id="age_diff" name="age_diff" value="{$age_diff|escape:'html'}" style="width:5em">
	{if $errors.age_diff}
		<span class="formerror">{$errors.age_diff}</span>
	{else}
		<span style="color:gray;display:inline-block">Please provide your current age in years. 
		This information will only ever be used by Geograph for internal demographic analysis and to help us fulfil our obligations under the Online Safety Act (2024)
		</span>
	{/if}

	<br/><br/>

        <div class="interestBox" style="position:absolute;left:-1000px;top:-200px">
        <label for="email2">leave this box blank</label><br/>
        <input size="15" id="email2" name="email2" value="" autocomplete="no"/>

        <br/><br/>
        </div>

	<label for="password1">Choose a password</label><br/>
	<input size="15" type="password" id="password1" name="password1" value="{$password1|escape:'html'}" required/>
	<span class="formerror">{$errors.password1}</span>

	<br/><br/>
	<label for="password2">Confirm password</label><br/>
	<input size="15" type="password" id="password2" name="password2" value="{$password2|escape:'html'}" required/>
	<span class="formerror">{$errors.password2}</span>
	<br/>
	<span class="formerror">{$errors.general}</span>
	<br/>

	{if $recaptcha}
		<p>Please enter/solve the following...
		 {$recaptcha}
		<br>
		<span class="formerror">{$errors.captcha}</span>
		<br>
	{/if}

	<input type=checkbox name=agree_terms required id=agree>
	<label for="agree">I agree to the Geograph Website <a href="/help/terms" target="_blank">Terms of Service</a></label> (<i>opens in new window/tab</i>)
	<br/>
	<br/>

	<input type=hidden name="http_referer" value="{$http_referer|escape:'html'}">
	<input type=hidden id="register_timing" name="register_timing" value="-1">
	<input type="submit" name="register" value="Register"/>
</div>
	</form>  

	<p>We won't sell or distribute your email address, we hate spam, we really do.</p>
{/if}

{/dynamic}
</div>

<script>{literal}
const startTime = performance.now();
const form = document.getElementById('register_form');
form.addEventListener('submit', function(event) {
    const endTime = performance.now();
    let timeInput = document.getElementById('register_timing');   
    timeInput.value = (endTime - startTime) / 1000; 
});
</script>{/literal}
    
{include file="_std_end.tpl"}
