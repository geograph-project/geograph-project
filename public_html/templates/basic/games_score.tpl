{assign var="page_title" value="Save Score"}
{include file="_std_begin.tpl"}

{dynamic}

<h2>Save score</h2>

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $user->registered}

	<p>To save your {$game->score} points to your account, <a href="{$script_name}?save=user">click here</a></p> 

{else}

	<p>To save your {$game->score} points, either:</p>
	
	<ol>
		<li><a href="{$script_name}?login=1">Login</a> to save your scores to your account
		
		<br/><br/><i>- or -</i><br/><br/></li>
		
		<li><form action="{$script_name}">
		<label for="username">Enter your name:</label> <input name="username" id="username" maxlength="64" value="{$username|escape:'html'}"/> <input type="submit" name="save" value="Save"/>
		<br/><br/>
		* Names are moderated and unsuitable ones are rejected.
		</form></li>
	</ol>

{/if}


{/dynamic}

{include file="_std_end.tpl"}
