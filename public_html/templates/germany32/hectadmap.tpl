{assign var="page_title" value="Hectad Map"}
{include file="_std_begin.tpl"}
<style type="text/css">{literal}
#maptable {
	background-color:white;
	font-family:courier;
	font-size:0.7em;
}
#maptable a {
	text-decoration:none;
	color:black;
}
#maptable a:hover {
	text-decoration:underline;
	color:blue;
}
#maptable a:hover:visited {
	text-decoration:underline;
	color:purple;
}
{/literal}
</style>
<h2>Hectad Coverage Map{if $u} for {$profile->realname|escape:'html'}{/if}</h2>

<form method="get" action="{$script_name}">
<div class="interestBox">Colour squares by: 
{if $which eq 1}
	<b>Geograph Coverage</b>
{else}
	<a href="?w=1{if $profile}&amp;u={$profile->user_id}{/if}">Geograph Coverage</a>
{/if} |
{if $which eq 2}
	<b>Coverage Percentage</b>
{else}
	<a href="?w=2{if $profile}&amp;u={$profile->user_id}{/if}">Coverage Percentage</a>
{/if} |
{if $which eq 3}
	<b>Land Squares</b>
{else}
	<a href="?w=3">Land Squares</a>
{/if}
<input type="hidden" name="w" value="{$which}"/>
{dynamic}
    {if $user->registered}
	- <select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">Just for {$profile->realname|escape:'html'}</option>
		{/if}
		<option value="{$user->user_id}">Just for {$user->realname|escape:'html'}</option>
		<option value="" {if !$u} selected{/if}>For Everyone</option>
	</select>
	<input type="submit" value="Go">
    {else}
	{if $u}
	- <select name="u">
		<option value="{$u}" selected>Just for {$profile->realname|escape:'html'}</option>
		<option value="">For Everyone</option>
	</select>
	<input type="submit" value="Go">
	{/if}
    {/if}
    {/dynamic}
</div>
</form>    

<div style="position:absolute;height:0;width:0">
	<div style="position:relative;width:280px; color:brown; background-color:lightgreen;padding:10px;">
		Scroll left/down to see the map.<br/><br/>
		<small>Keyboard shortcuts:<br/>
		CTRL and - (minus) to decrease text-size<br/>
		CTRL and + (plus) to increase<br/>
		CTRL and 0 (zero) to return to normal<br/><br/>
		(or hold CTRL and use mouse scroll wheer)</small></div>
</div>

<table id="maptable" border=1 cellspacing=0 cellpadding=1 bordercolor="#f7f7f7"> <tbody>

	<tr>
	{section name=x loop=$x2 start=$x1 step=1}
		<th width="45">&nbsp;&nbsp;</th>
	{/section}
	</tr>

{section name=y loop=$y2 max=$h step=-1}
	{assign var="y" value=$smarty.section.y.index}
	<tr>
	{strip}{section name=x loop=$x2 start=$x1 step=1}
		{assign var="x" value=$smarty.section.x.index}
		{if $grid.$y.$x}{assign var="mapcell" value=$grid.$y.$x}
			<td bgcolor="#{$mapcell.$column|colerize}" title="{$mapcell.hectad}: {$mapcell.geosquares}/{$mapcell.landsquares}={$mapcell.percentage}%">
			<a href="/gridref/{$mapcell.hectad}">{if $mapcell.geosquares}<b>{$mapcell.digits}</b>{else}{$mapcell.digits}{/if}</a>
			</td>
		{else}
			<td>&nbsp;</td>
		{/if}
	{/section}{/strip}
	</tr>
{/section}
</tbody>
</table>
{*extreme hectads:
E UWS08 800
N UMG60 900
S TNT83  35
W UGS05   0
-> x: 10 90, y: 3 90
*}
{* smarty is great
<p>a: <!--7 8 9-->
{section name=x loop=10 start=-3 step=1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
<br >b: <!--3 4 5 6 7 8 9-->
{section name=x loop=10 start=3 step=1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
<br >c: <!--3 2 1 0-->
{section name=x loop=10 start=3 step=-1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
<br >d: <!--9 8 7 6 5 4 3 2 1 0-->
{section name=x loop=10 start=30 step=-1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
<br >e: <!--29 28 27 26 25 24 23 22 21 20-->
{section name=x loop=30 max=10 step=-1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
<br >f: <!--0 1 2 3 4 5 6 7 8 9-->
{section name=x loop=30 max=10 step=1}{assign var="x" value=$smarty.section.x.index}
{$x}
{/section}
</p>
*}

<p><i>Hover over square to see statistics</i></p>

{include file="_std_end.tpl"}

