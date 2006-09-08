{assign var="page_title" value="`$references.$ri` :: Geograph Gazetteer"}
{include file="_std_begin.tpl"}
 
    <h2><a href="/explore/places/">Places</a> &gt; {$references.$ri}</h2>

<ul>
{foreach from=$counts key=adm1 item=line}
<li><a href="/explore/places/{$ri}/{$adm1}/"><b>{$line.name}</b></a> [{$line.places} Places, {$line.images} Images]</li>
{/foreach}
</ul>

{if $ri == 1} 
<div class="copyright">Great Britain locations based upon Ordnance Survey&reg 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright. Educational licence 100045616.</div>
{/if}

{include file="_std_end.tpl"}
