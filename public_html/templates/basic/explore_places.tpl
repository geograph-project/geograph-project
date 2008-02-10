{assign var="page_title" value="Places :: Geograph Gazetteer"}
{include file="_std_begin.tpl"}
 
    <h2>Geograph British Isles</h2>

<p>Please choose country you wish to find images in:</p>

<ul>
{foreach from=$counts key=ri item=count}
<li><a href="/explore/places/{$ri}/"><b>{$references.$ri}</b></a> [{$count} images]</li>
{/foreach}
</ul>

{include file="_std_end.tpl"}
