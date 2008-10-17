{include file="_std_begin.tpl"}
{dynamic}

<h2>Update Maps</h2>
<p>Use this page to let the system know that a map doesn't appear to be updating. You should normally wait at least 3 hours after submitting before visiting this page...</p>

{if $invalid_maps > 0}
	<p>There are currently <b>{$invalid_maps}</b> tiles in the process queue.</p>

{else}
	<p>There are currently <b>0</b> tiles in the process queue.</p>
{/if}

<p>If the number above is high (say above 100), then the site is busy, maps will be processed in due time, please be patient!</p> 

<p>Otherwise if showing a low number (or 0) then its possible that your map has been missed, it would probably be worth submitting the Grid-Reference below, thanks!</p>

<hr> 
	
{if ($invalid_maps < 500) || ($is_mod && ($invalid_maps < 2500))}
	<form method="post" action="recreatemaps.php">

	<p>You can use the following form to request an update for a particular square.</p>

	<p>* Only submit a square here if the queue is relatively empty (say below 150), and the map still hasn't updated (after at least 3 hours).</p>

	<p>{$errormsg}</p>

	<p>4-Figure Grid Reference: <input type="text" name="gridref" value="" size="10"/>

	<input type="submit" name="inv" value="Request Update"/></p>

	</form>

	<p>* Submitting the same square more than once will not help (but it doesn't matter if you do)</p>
{else}
	<p>The queue is quite full at the moment, if a map still hasn't updated later then please return.</p>
{/if}
{/dynamic}    



{include file="_std_end.tpl"}
