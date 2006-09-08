{assign var="page_title" value="Places :: Geograph Gazetteer"}
{include file="_std_begin.tpl"}
 
    <h2>Geograph British Isles</h2>

<p>Use the links below to browse images by the city or town they are closest to.</p>

<ul>
{foreach from=$counts key=ri item=count}
<li><a href="/explore/places/{$ri}/"><b>{$references.$ri}</b></a> [{$count} images]</li>
{/foreach}
</ul>

{include file="_std_end.tpl"}
