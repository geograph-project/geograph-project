{assign var="page_title" value="Send Message"}
{include file="_std_begin.tpl"}
{dynamic}
{if $throttle}
	<h2>Sorry</h2>
	<p>In order to prevent abuses of the contact page by spammers, we limit
	the number of messages you can send in any hour - please try again later.</p>

{else}

{if $recipient->registered}
	<h2>Send message to {$recipient->realname|escape:'html'}</h2>

	{if $error}
		<h2>Sorry</h2>
		<p>Unable to send message - {$error}</p>
	{/if}

	{if $sent}
		<p>Thank you - your message has been sent</p>
	{elseif $verification}
		<form method="post" action="/usermsg.php">

			<input type="hidden" name="to" value="{$recipient->user_id|escape:'html'}">



			<input type="hidden" name="from_name" value="{$from_name|escape:'html'}"/>
			<input type="hidden" name="from_email"  value="{$from_email|escape:'html'}"/>
			<input type="hidden" name="sendcopy" value="{if $sendcopy}on{/if}"/>
			<input type="hidden" name="verification" value="{$verification|escape:'html'}"/>
			<textarea name="msg" style="display:none">{$msg|escape:'html'}</textarea>
			{if $mention}
				{foreach from=$mention item=image}
						<input type="hidden" name="mention[]" value="{$image|escape:'html'}" value="1"/>
				{/foreach}
			{/if}
			{if $encoded}
				<h4 style="text-align:center;color:red">Do not close this window!</h4>
				<div class="interestBox">
				<h3>Confirmation code sent to <tt>{$from_email|escape:'html'}</tt></h3>
				<input type="hidden" name="encoded" value="{$encoded|escape:'html'}"/>
				<label for="confirmcode">Please enter the code contained in the email, in the box below:</label><br />
				<input type="text" name="confirmcode" id="confirmcode" size="50"/>
				<br/>
				</div><br/>
				<small>Alternatively just click send without entering anything above, to have another go at the Captcha. </small>
				<br/>
				<input type="submit" name="send" value="Send">
			{else}
				<div class="interestBox">
					<b>In order to help prevent spamming our members we ask you to take a moment to fill out the following. </b>
				</div>

				{if $recaptcha}
					<p>Please enter the two words contained in the big white box, alternatively click the speaker icon to listen to a audio version.
					{$recaptcha}
				{else}
					<p>So please take a moment to fill out the below to help prevent spamming of our members.</p>
					<br/>
					<img src="/stuff/captcha.jpg.php?{$verification|escape:'html'}" style="padding:20px; border:1px solid silver"/><br />
					<br />

					<label for="verify">To continue, enter the <i>big letters and numbers</i> shown in the image above:</label><br/>
					<input type="text" name="verify" id="verify"/><br />
				{/if}
				<br/><br/>
				<input type="submit" name="send" value="Send">
				<br/><br/><br/><br/><br/><br/><br/><br/>

				<span id="hidemore"><a href="#" onclick="show_tree('more');return false">I'm having trouble using the Captcha above</a>.</span>
				<blockquote style="border:1px solid pink; padding:10px; display:none" id="showmore">
					... or if you are unable to enter the two words above, and listening to the audio version didn't work, <br/>
					then you can instead <input type="submit" name="sendcode" value="request confirmation code by email"/> <br/>

					<small>We will email a code to <tt>{$from_email|escape:'html'}</tt> which <b>you must enter on the next page</b>.</small>
				</blockquote>
			{/if}
			<br />

		</form>
	{else}
		{if $invalid_email}
		<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
			<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Warning" width="50" height="44" align="left" style="margin-right:10px"/>
			We don't have valid contact details on record for this user, you can fill out the form below,
			it will be sent to the Geograph team, who might or might not be able to help with your query.
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
			make sure you clearly state which one. The contributor may have photographed
			many locations and may not know immediately to what you are referring.</div>
			<br/>

			{if $mention}
				{foreach from=$mention item=image}
						<input type="hidden" name="mention[]" value="{$image|escape:'html'}" value="1"/>
				{/foreach}
			{elseif $images}
				Automatically include a link to the following image(s) in your message:<br/>
				{foreach from=$images item=image}
					<div id="g{$image.gridimage_id}" style="background-color:white;padding:2px">
						&middot; <input type="checkbox" name="mention[]" value="{$image.gridimage_id}" checked style="border:red" onclick="document.getElementById('g{$image.gridimage_id}').style.backgroundColor=this.checked?'white':'';"/>
						{newwin href="/photo/`$image.gridimage_id`" text="`$image.grid_reference` :: `$image.title`"|escape:'html'} by {$image.realname|escape:'html'}
					</div>
				{/foreach}
				<small>(untick any don't want to include)</small><br/>
			{/if}


		</div>

		<div style="float:right; position:relative; vertical-align:top;">
			- <b>{external href="http://recaptcha.net/" text="Protected by reCAPTCHA"}</b></span> -
		</div>

		<input type="submit" name="send" value="Send message to {$recipient->realname|escape:'html'}">
		</form>
	{/if}
{else}
	<h2>Sorry</h2>
	<p>Unable to send message - target user not recognized</p>
{/if}
{/if}

{/dynamic}
{include file="_std_end.tpl"}
