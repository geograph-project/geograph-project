{include file="_std_begin.tpl"}
{dynamic}

<h2>Update Maps</h2>
<p>Use this page to let the system know that a map doesn't appear to be updating. You should normally wait at least 3 hours after an image has been moderated before visiting this page.</p>

{if $invalid_maps > 0}
	<p>There are currently <b>{$invalid_maps}</b> tiles in the process queue.</p>

{else}
	<p>There are currently <b>0</b> tiles in the process queue.</p>
{/if}

<p>If the number above is high (say above 100), then the site is busy, maps will be processed in due time, please be patient!</p> 

<hr> 
	
{if ($invalid_maps < 500) || ($is_mod && ($invalid_maps < 2500))}
	<form method="post" action="recreatemaps.php">

	<p>You can use the following form to request an update for a particular square.</p>

	<p>{$errormsg}</p>

	<p>4-Figure Grid Reference: <input type="text" name="gridref" value="" size="10"/>

	<input type="submit" name="inv" value="Request Update"/></p>

	<p>Optional Geograph User ID: <input type="text" name="user_id" value="{$user->user_id}" size="3"/> (to also refresh Personalised Map)

	</form>

	<p>* Submitting the same square more than once will not help (but it doesn't matter if you do)</p>
{else}
	<p>The queue is quite full at the moment, if a map still hasn't updated later then please return.</p>
{/if}
{/dynamic}    



{include file="_std_end.tpl"}
