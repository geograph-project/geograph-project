{assign var="page_title" value="Non Geographed Hectad"}
{include file="_std_begin.tpl"}

<h2>Non Geographed Hectads</h2>

<p>These are the Hectad with the least coverage so far, that is without any photos yet, go on photograph one!</p>

<div style="float:left;position:relative;width:50%">
<h4>Great Britain</h4>
<table class="report"> 
<thead><tr><td>Square</td><td>Land Squares</td></tr></thead>
<tbody>

{foreach from=$most1 key=id item=obj}
<tr><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right">{$obj.land_count}</td></tr>
{/foreach}

</tbody>
</table>

</div>

<div style="float:left;position:relative;width:50%">
<h4>Ireland</h4>
<table class="report"> 
<thead><tr><td>Square</td><td>Land Squares</td></tr></thead>
<tbody>

{foreach from=$most2 key=id item=obj}
<tr><td><a title="View map for {$obj.tenk_square}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.tenk_square}</a></td>
<td align="right">{$obj.land_count}</td></tr>
{/foreach}

</tbody>
</table>

</div>



<br style="clear:both"/>

 		
{include file="_std_end.tpl"}
