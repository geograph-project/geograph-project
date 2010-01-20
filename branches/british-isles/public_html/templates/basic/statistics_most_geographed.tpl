{assign var="page_title" value="Most Geographed Squares"}
{include file="_std_begin.tpl"}

<h2>Most Geographed Hectads{if $myriad}, for Myriad {$myriad}{/if}</h2>

    <form method="get" action="{$script_name}">
	<div class="interestBox"><a href="not_geographed.php{if $ri}?ri={$ri}{/if}">Not Geographed</a> | 
	<b>Mostly Geographed</b> | 
	<a href="fully_geographed.php{if $ri}?ri={$ri}{if $myriad}&amp;myriad={$myriad}{/if}{/if}">Fully Geographed</a> Hectads -
	<a href="hectads.php{if $ri}?ri={$ri}{/if}">Completion Progress</a>  -
	{if $references}In <select name="ri">
	{html_options options=$references selected=$ri}
	</select>{/if}
	<input type="submit" value="Go"></div>
    </form>

<p>These are the squares with the best coverage so far! This page only counts First Geographs.</p>

<p>See also <a href="/statistics/most_geographed_myriad.php">100km x 100km Squares</a>.</p>


<table class="report"> 
<thead><tr><td>Rank</td><td>Hectad</td><td colspan="3">Squares</td><td align="right" style="width:40px">%</td><td>Contributors</td><td>Last Submission</td></tr></thead>
<tbody>

{foreach from=$most key=id item=obj}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td align="right">{$obj.ordinal}</td><td sortvalue="{$obj.hectad}" style="font-family:monospace;font-size:1.2em" align="right"><a title="View map for {$obj.hectad}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.hectad}</a></td>
<td align="right">{$obj.geosquares}</td>
<td align="right">/</td>
<td align="right">{$obj.landsquares}</td>
<td align="right">{$obj.percentage|thousends}</td>
<td align="right">{$obj.users|thousends}</td>
<td align="right" sortvalue="{$obj.last_submitted}">{$obj.last_submitted|date_format:"%A, %e %B, %Y"}</td></tr>
{/foreach}

</tbody>
</table>


<br style="clear:both"/>

{include file="_std_end.tpl"}
