{assign var="page_title" value="Most Geographed Squares"}
{include file="_std_begin.tpl"}

<h2>Most Geographed Hectads{if $myriad}, for Myriad {$myriad}{/if}</h2>

    <form method="get" action="{$script_name}">
	<div class="interestBox"><a href="not_geographed.php{if $ri}?ri={$ri}{/if}">Not Geographed</a> | 
	<b>Mostly Geographed</b> | 
	<a href="fully_geographed.php{if $ri}?ri={$ri}{if $myriad}&amp;myriad={$myriad}{/if}{/if}">Fully Geographed</a> Hectads -
	<a href="hectads.php{if $ri}?ri={$ri}{/if}">Completion Progress</a> 
	<span class="nowrap">-
	{if $references}<label for="ri">In</label> <select name="ri" id="ri">
	{html_options options=$references selected=$ri}
	</select>{/if}
	or <label for="myriad">Myriad</label> <input type="text" name="myriad" id="myriad" value="{$myriad|escape:'html'}" size="2" maxlength="3"/>
	<input type="submit" value="Go"></span><br/>
	
	See also | <a href="/statistics/most_geographed_gridsquare.php{if $ri}?ri={$ri}{if $myriad}&amp;myriad={$myriad}{/if}{/if}">Grid Squares{if $myriad} in {$myriad}{/if}</a> |
	<a href="/statistics/most_geographed_myriad.php">Myriads :: 100km x 100km Squares</a> | 
	</div>
    </form>

<p>These are the hectad<a href="/help/squares">?</a>/squares with the best coverage so far! This page only counts First Geographs.</p>

<table class="report"> 
<thead><tr><td>Rank</td><td>Hectad</td><td colspan="3">Squares</td><td align="right" style="width:40px">%</td><td>Contributors</td><td>Last Submission</td><td>Map</td></tr></thead>
<tbody>

{foreach from=$most key=id item=obj}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td align="right">{$obj.ordinal}</td><td sortvalue="{$obj.hectad}" style="font-family:monospace;font-size:1.2em" align="right"><a href="/gridref/{$obj.hectad}">{$obj.hectad}</a></td>
<td align="right">{$obj.geosquares}</td>
<td align="right">/</td>
<td align="right">{$obj.landsquares}</td>
<td align="right">{$obj.percentage|thousends}</td>
<td align="right">{$obj.users|thousends}</td>
<td align="right" sortvalue="{$obj.last_submitted}">{$obj.last_submitted|date_format:"%A, %e %B, %Y"}</td>
<td><a href="/mapbrowse.php?t={$obj.map_token}">Map</a></td></tr>
{/foreach}

</tbody>
</table>

{if $shown_rows}
	<p>Showing {$shown_rows|thousends} out of {$total_rows|thousends} hectads. {if !$myriad && $shown_rows < $total_rows}Try using the filter at the top of the page to see alternative results.{/if}</p>
{/if}

<br style="clear:both"/>

{include file="_std_end.tpl"}
