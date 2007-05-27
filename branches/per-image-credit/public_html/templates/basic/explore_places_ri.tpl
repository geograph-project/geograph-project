{assign var="page_title" value="`$references.$ri` :: Geograph Gazetteer"}
{include file="_std_begin.tpl"}
 
    <h2><a href="/explore/places/">Places</a> &gt; {$references.$ri}</h2>

{if $ri == 1}<h4>Local Authorities of {$references.$ri} in alphabetical order:</h4>{/if}

<ul>
{foreach from=$counts key=adm1 item=line}
<li>{if $line.places == 1}<a href="/explore/places.php?ri={$ri}&amp;adm1={$adm1}&amp;pid={$line.placename_id}" title="Place: {$line.full_name}">{else}<a href="/explore/places/{$ri}/{$adm1}/"  title="EXAMPLE Place: {$line.full_name}">{/if}<b>{$line.name}</b></a> [{$line.places} Places, {$line.images} Images]</li>
{/foreach}
</ul>

{if $ri == 1} 
<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>
{/if}

{include file="_std_end.tpl"}
