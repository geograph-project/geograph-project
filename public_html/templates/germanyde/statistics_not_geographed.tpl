{assign var="page_title" value="Non Geographed Hectad"}
{include file="_std_begin.tpl"}

<h2>Non Geographed Hectads</h2>

<p>These are the Hectad with the least coverage so far, that is without any photos yet, go on photograph one!</p>

{foreach from=$references_real item=ref key=ri}
<div style="float:left;position:relative;width:33%">
<h4>{$ref}</h4>
<table class="report"> 
<thead><tr><td>Square</td><td>Land Squares</td></tr></thead>
<tbody>

{foreach from=$most[$ri] key=id item=obj}
<tr><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right">{$obj.land_count}</td></tr>
{/foreach}

</tbody>
</table>

</div>
{/foreach}


<br style="clear:both"/>

 		
{include file="_std_end.tpl"}
