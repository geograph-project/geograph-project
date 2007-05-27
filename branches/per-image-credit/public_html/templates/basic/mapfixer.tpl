{include file="_std_begin.tpl"}
{dynamic}

{if $gridref_error}
	<h2>Error</h2>

	<p style="color:#990000;font-weight:bold;">{$gridref_error}</p>
	<p><small>If you reached the browse page by using the N-E-S-W buttons, then you will need to enter the full Grid Reference below, (we hope to have a solution for this soon)</small></p>
{else}
	{if $status}
		<h2>Thank you for notification</h2>

		<p>The square has been flagged and will be checked by a moderator.</p>

		<p>Return to <a href="/gridref/{$gridref}">{$gridref}</a> or </p>
	{else}

		<h2>Notify us of a gridsquare that needs checking...</h2>
	{/if}
{/if}

<!-- {$status} -->

<form action="/mapfixer.php" method="get">
<p>{if $gridref_error}Try again:{else}{if $status}Repeat for another square:{/if}{/if} <br/>
4-fig Grid Reference: <input type="text" size=8"" name="gridref" value="{$gridref}"/>
<input type="submit" name="save" value="This square needs checking"/>
</p>
</form>

{/dynamic}
{include file="_std_end.tpl"}
