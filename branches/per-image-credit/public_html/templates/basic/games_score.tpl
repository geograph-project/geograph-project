{assign var="page_title" value="Save Score"}
{include file="_std_begin.tpl"}

{dynamic}

<h2>Save score</h2>

{if $user->registered}

	<p>To save your {$game->score} points to your account, <a href="{$script_name}?token={$game->getToken()}&amp;save=user">click here</a></p> 

{else}

	<p>To get your name on the scoreboard, with {$game->score} points, either:</p>
	
	<ol>
		<li><a href="{$script_name}?token={$game->getToken()}&amp;login=1">Login</a> to save your scores to your account
		
		<br/><br/><i>- or -</i><br/><br/></li>
		
		<li><form action="{$script_name}">
		<input type="hidden" name="token" value="{$game->getToken()}"/>
		<label for="username">Enter your name:</label> <input name="username" id="username" maxlength="64"/> <input type="submit" name="save" value="Save"/>
		<br/><br/>
		* Your name will be visible on the board for 48 hours
		</form></li>
	</ol>

{/if}


{/dynamic}

{include file="_std_end.tpl"}
