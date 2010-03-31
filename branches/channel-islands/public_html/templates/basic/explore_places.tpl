{assign var="page_title" value="Places :: Geograph Gazetteer"}
{assign var="meta_description" value="Geograph has many photos and images of the Channel Islands, find them here."}
{include file="_std_begin.tpl"}
 
    <h2>Geograph Channel Islands</h2>

<p>Please choose country you wish to find images in:</p>

<ul>
{foreach from=$counts key=ri item=count}
<li><a href="/explore/places/{$ri}/"><b>{$references.$ri}</b></a> [{$count} images]</li>
{/foreach}
</ul>

{include file="_std_end.tpl"}
