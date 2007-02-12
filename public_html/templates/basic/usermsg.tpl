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
	{else}
		<form method="post" action="/usermsg.php">
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
		<textarea rows="10" cols="60" name="msg" id="msg">{$msg|escape:'html'}</textarea>
		<br/>
		<span class="formerror">{$errors.msg}</span>

		<br/>
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
