{assign var="page_title" value="Send Message"}
{include file="_std_begin.tpl"}
{dynamic}
{if $throttle}
	<h2>Sorry</h2>
	<p>In order to prevent abuses of the contact page by spammers, we limit
	the number of messages you can send in any hour - please try again later.</p>

{else}

{if $recipient->registered}
	<h2>Send Message to {$recipient->realname|escape:'html'}</h2>

	{if $error}
		<h2>Sorry</h2>
		<p>Unable to send message - {$error}</p>
	{/if}
	
	{if $sent}
		<p>Thankyou - your message has been sent</p>
	{elseif $verification}
		<form method="post" action="/usermsg.php">
		
		<div class="interestBox">
		
			<p><b>We hate spam, we really do!</b><br/>
			So please take a moment to fill out the below to help prevent spamming of our members.</p>

			<input type="hidden" name="to" value="{$recipient->user_id|escape:'html'}">

			<input type="hidden" name="from_name" value="{$from_name|escape:'html'}"/>
			<input type="hidden" name="from_email"  value="{$from_email|escape:'html'}"/>
			<input type="hidden" name="sendcopy" value="{if $sendcopy}on{/if}"/>				
			<input type="hidden" name="verification" value="{$verification|escape:'html'}"/>				
			<textarea name="msg" style="display:none">{$msg|escape:'html'}</textarea>

			<br />

			<img src="/stuff/captcha.jpg.php?{$verification|escape:'html'}" style="padding:20px; border:1px solid silver"/><br />
			<br />

			<label for="verify">To continue, enter the <i>big letters and numbers</i> shown in the image above:</label><br/>
			<input type="text" name="verify" id="verify"/><br />
		</div>
		<br />
		
		<input type="submit" name="send" value="Send">
		</form>	
	{else}
		{if $invalid_email} 
		<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Warning" width="50" height="44" align="left" style="margin-right:10px"/>
			We don't have valid contact details on record for this user, you can fill out the form below,
			it will be sent to the Geograph Team, who might or might not be able to help with your query.
		</div>
		<br/><br/><br/>
		{/if}
		<form method="post" action="/usermsg.php">
		<div class="interestBox">
			<input type="hidden" name="to" value="{$recipient->user_id|escape:'html'}">

			<label for="from_name">Your Name</label><br />
			<input type="text" name="from_name" id="from_name" value="{$from_name|escape:'html'}"/>
			<span class="formerror">{$errors.from_name}</span>

			<br/><br/>
			<label for="from_email">Your Email</label><br />
			<input type="text" name="from_email" id="from_email" value="{$from_email|escape:'html'}"/>
			<span class="formerror">{$errors.from_email}</span>
			{if $user->registered}
				&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sendcopy" id="sendcopy" value="on" {if $sendcopy} checked="checked"{/if}/> <label for="sendcopy">Send myself a copy</label>
			{/if}

			<br/><br/>
			<label for="msg">Message</label><br />
			<textarea rows="10" cols="60" name="msg" id="msg"{if $user->message_sig} onfocus="if (this.value=='') {literal}{{/literal}this.value='{$user->message_sig|escape:'javascript'}';setCaretTo(this,0); {literal}}{/literal}"{/if}>{$msg|escape:'html'}</textarea>
			<br/>
			<span class="formerror">{$errors.msg}</span>

			<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="30" height="24" align="left" style="margin-right:10px"/>
			If you are sending this message in relation to a particular photo or location, please
			make sure you clearly state which. The contributor may have photographed
			many locations and may not know immediately to where you are referring.</div>
			<br/>
		</div>
		
		<div style="float:right; position:relative; vertical-align:top;">
			- <b>{external href="http://akismet.com/" text="Protected by Akismet"}</b> -
			<span style="font-size:0.8em">{external href="http://wordpress.com/" text="Blog with WordPress"}</span> -
		</div>
		
		<input type="submit" name="send" value="Send">
		</form>
	{/if}
{else}
	<h2>Sorry</h2>
	<p>Unable to send message - target user not recognized</p>
{/if}
{/if}

{/dynamic}
{include file="_std_end.tpl"}
