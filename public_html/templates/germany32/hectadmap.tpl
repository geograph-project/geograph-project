{assign var="page_title" value="Hectad Map"}
{include file="_std_begin.tpl"}
<h2>{$page_title}</h2>

<p>Colour squares by: 
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
{/if}</p>

<table style="background-color:white;font-family:courier;font-size:0.7em" border=0 cellspacing=0 cellpadding=1> <tbody>

	<tr>
	{section name=x loop=$w start=$x1 step=1}
		<th width="90">&nbsp;&nbsp;&nbsp;&nbsp;</th>
	{/section}
	</tr>

{section name=y loop=$h start=$y2 step=-1}
	{assign var="y" value=$smarty.section.y.index}

	{section name=x loop=$w start=$x1 step=1}
		{assign var="x" value=$smarty.section.x.index}
		
		{if $grid.$y.$x}{assign var="mapcell" value=$grid.$y.$x}
			<td bgcolor="#{$mapcell.$column|colerize}" title="{$mapcell.geograph_count}/{$mapcell.land_count}={$mapcell.percentage}%">
			{if $mapcell.geograph_count}<b>{$mapcell.tenk_square}</b>{else}{$mapcell.tenk_square}{/if}
			</td>
		{else}
			<td>&nbsp;</td>
		{/if}
	{/section}
	
	</tr>
{/section}
</table>

<p><i>Hover over square to see statistics</i></p>

{include file="_std_end.tpl"}

