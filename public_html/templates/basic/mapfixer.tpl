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

<p>In order for Geograph to get an accurate coverage figure, and to make pretty maps, we need to know which squares are one land and which are 'all at sea'. To this end, we built up a reasonable basemap using freely available data, but this isn't totally accuarate on our complicated coastline. If you beleive a square is wrongly classified then you can use this form to notify a moderator, in due cause a high resolution map will be checked.</p>

<p>Note: for the purposes of Geograph, the Mean LOW Water line is used in classifing squares. For Great Britain 1:25,000 mapping is used, however for Ireland such mapping isn't as available, so Google Earth or sometimes Discoverer or Discovery maps will be consulted.</p>

<!-- {$status} -->

{if $percent_land == -1} 
	<div class="interestBox"><b><a href="/gridref/">{$gridref|escape:html}</a></b> is in the queue for checking, it will be checked in due course.</div>

{elseif $percent_land > -1} 
	<div class="interestBox"><b><a href="/gridref/">{$gridref|escape:html}</a></b> has a land percentage of <b>{$percent_land}%</b>. Note this figure is very approximate.</div>
	<br/>
	{if $check_count}
		<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
		<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
		This square has already been manually verified by a moderator. However if you beleive this is still wrong you can request a recheck using the form below.</div>
	{/if}
{/if}

<form action="/mapfixer.php" method="get">
<p>{if $gridref_error}Try again:{else}{if $status}Repeat for another square:{/if}{/if} <br/>
4-fig Grid Reference: <input type="text" size=8"" name="gridref" value="{$gridref|escape:html}"/>
<input type="submit" name="save" value="This square needs checking"/>
</p>
</form>

{/dynamic}
{include file="_std_end.tpl"}
