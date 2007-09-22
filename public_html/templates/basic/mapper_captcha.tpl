{include file="_std_begin.tpl"}
{dynamic}
	
	{if $error}
			<h2>Sorry</h2>
			<p>Unable to continue - {$error}</p>
	{/if}
	
	<form method="post" action="/mapper/captcha.php">

	<div class="interestBox">

		<p>Please take a moment to fill out the below to help prevent abuse of our resources.</p>

				
		<input type="hidden" name="verification" value="{$verification|escape:'html'}"/>				
		<input type="hidden" name="token" value="{$token|escape:'html'}"/>				
		
		<br />

		<img src="/stuff/captcha.jpg.php?{$verification|escape:'html'}" style="padding:20px; border:1px solid silver"/><br />
		<br />

		<label for="verify">Enter the main letters shown above:</label>
		<input type="text" name="verify" id="verify"/> (non-case sensitive)<br/>
		<br/>
		<input type="submit" name="send" value="Continue">
		
	</div>
	
	</form>	
{/dynamic}
{include file="_std_end.tpl"}
