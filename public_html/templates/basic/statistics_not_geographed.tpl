{assign var="page_title" value="Non Geographed Hectad"}
{include file="_std_begin.tpl"}

<h2>Non Geographed Hectads</h2>

    <form method="get" action="{$script_name}">
	<div class="interestBox"><b>Not Geographed</b> | 
	<a href="most_geographed.php{if $ri}?ri={$ri}{/if}">Mostly Geographed</a> | 
	<a href="fully_geographed.php{if $ri}?ri={$ri}{/if}">Fully Geographed</a> Hectads -
	{if $references}In <select name="ri">
	{html_options options=$references selected=$ri}
	</select>{/if}
	<input type="submit" value="Go"></div>
    </form>

<p>These are the Hectad with the least coverage so far, that is without any photos yet, go on photograph one!</p>


<table class="report"> 
<thead><tr><td>Square</td><td>Land Squares</td></tr></thead>
<tbody>

{foreach from=$most key=id item=obj}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td sortvalue="{$obj.hectad}" style="font-family:monospace" align="right"><a href="/gridref/{$obj.hectad}">{$obj.hectad}</a></td>
<td align="right">{$obj.landsquares}</td></tr>
{foreachelse}
	<p>None, Wow!
{/foreach}

</tbody>
</table>


{if $shown_rows}
	<p>Showing {$shown_rows|thousends} out of {$total_rows|thousends} hectads. {if !$myriad && $shown_rows < $total_rows}Try using the filter at the top of the page to see alternative results.{/if}</p>
{/if}

<br style="clear:both"/>

 		
{include file="_std_end.tpl"}
