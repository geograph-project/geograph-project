{assign var="page_title" value="$adm1_name :: `$references.$ri` :: Geograph Gazetteer"}
{include file="_std_begin.tpl"}
 
    <h2><a href="/explore/places/">Places</a> &gt; <a href="/explore/places/{$ri}/">{$references.$ri}</a> &gt; {$parttitle} {$adm1_name}</h2>
<table><tr>
{foreach name=repeat from=$counts key=pid item=line}
<td>&middot; {if $line.c == 1}<a href="/photo/{$line.gridimage_id}">{else}<a href="/explore/places.php?ri={$ri}&amp;adm1={$adm1}&amp;pid={$pid}">{/if}<b>{$line.full_name}</b></a> [{$line.c}]</td>
{if $smarty.foreach.repeat.iteration %3 == 0}</tr><tr>{/if}
{/foreach}
</tr></table>

{if $ri == 1} 
<br/><br/>
<div class="copyright">Great Britain locations based upon Ordnance Survey&reg 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright. Educational licence 100045616.</div>
{/if}

{include file="_std_end.tpl"}
