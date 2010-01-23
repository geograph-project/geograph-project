{assign var="page_title" value="Most Photographed Squares"}
{include file="_std_begin.tpl"}

<h2>Most Photographed Squares{if $myriad}, for Myriad {$myriad}{/if}</h2>

    <form method="get" action="{$script_name}">
	<div class="interestBox"> 
	<b>Grid Squares</b> |
	<a href="/statistics/most_geographed.php{if $ri}?ri={$ri}{if $myriad}&amp;myriad={$myriad}{/if}{/if}">Hectads :: 10km x 10km Squares</a> |
	<a href="/statistics/most_geographed_myriad.php">Myriads :: 100km x 100km Squares</a> |
	<span class="nowrap">-
	{if $references}<label for="ri">In</label> <select name="ri" id="ri">
	{html_options options=$references selected=$ri}
	</select>{/if}
	or <label for="myriad">Myriad</label> <input type="text" name="myriad" id="myriad" value="{$myriad|escape:'html'}" size="2" maxlength="3"/>
	<input type="submit" value="Go"></span></div>
    </form><br/>

<table class="report"> 
<thead><tr><td>Rank</td><td>Reference</td><td>Images</td></thead>
<tbody>

{foreach from=$most key=id item=obj}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td align="right">{$obj.ordinal}</td>
<td sortvalue="{$obj.grid_reference}" style="font-family:monospace;font-size:1.2em" align="right"><a title="View map for {$obj.hectad}" href="/gridref/{$obj.grid_reference}">{$obj.grid_reference}</a></td>
<td align="right">{$obj.imagecount|thousends}</td>
{/foreach}

</tbody>
</table>

{if $shown_rows}
	<p>Showing {$shown_rows|thousends} out of {$total_rows|thousends} squares. {if !$myriad && $shown_rows < $total_rows}Try using the filter at the top of the page to see alternative results.{/if}</p>
{/if}

<br style="clear:both"/>

{include file="_std_end.tpl"}
