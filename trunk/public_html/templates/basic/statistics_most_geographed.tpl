{assign var="page_title" value="Most Photographed Squares"}
{include file="_std_begin.tpl"}

<h2>Most Photographed Squares</h2>

<p>These are the 10km x 10km squares with the best coverage so far!</p>


<p>Last generated at {$generation_time|date_format:"%H:%M"}.</p>

<p>

<table class="report"> 
<thead><tr><td>Position</td><td>Square</td><td>Geographs</td></tr></thead>
<tbody>

{foreach from=$both key=id item=obj}
<tr><td align="right">{$obj.ordinal}</td><td><a title="View map for `$obj.tenk_square`" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right">{$obj.geograph_count}</td>

</tr>
{/foreach}

</tbody>
</table>

</p>

 		
{include file="_std_end.tpl"}
