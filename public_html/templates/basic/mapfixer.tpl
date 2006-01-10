{include file="_std_begin.tpl"}
{dynamic}
<h2>Thank you for notification</h2>

{if $gridref_error}
<p style="color:#990000;font-weight:bold;">{$gridref_error}</p>
{else}
<p>The square has been flagged and will be checked by a moderator.</p>
{/if}

<!-- {$status} -->

<form action="/mapfixer.php" method="get">
<p>{if $gridref_error}Try again:{else}Repeat for another square:{/if} <br/>
<input type="text" size=8"" name="gridref" value="{$gridref}"/>
<input type="submit" name="save" value="This square is All at Sea"/>
</p>
</form>

{/dynamic}
{include file="_std_end.tpl"}
