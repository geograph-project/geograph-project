{assign var="page_title" value="Random Thumbnail Map"}
{include file="_std_begin.tpl"}

<h2>Random Thumbnail Map</h2>

<p align="right" style="font-size:0.7em">Map last updated {$imageupdate|date_format:"%A, %B %e, %Y at %T"}</p>

<img src="{$map->getImageUrl()}" width="{$map->image_w}" height="{$map->image_h}" border="0" usemap="#imagemap"/>

{$imagemap}

{include file="_std_end.tpl"}
