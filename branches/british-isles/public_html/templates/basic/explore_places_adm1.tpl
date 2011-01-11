{assign var="page_title" value="$adm1_name :: `$references.$ri` :: Geograph Gazetteer"}
{assign var="meta_description" value="Creative Commons licensed photos of $adm1_name, `$references.$ri`, we have many!"}
{include file="_std_begin.tpl"}
 
<h2><a href="/explore/places/">Places</a> &gt; <a href="/explore/places/{$ri}/">{$references.$ri}</a> &gt; <small>{$parttitle}</small> {$adm1_name}</h2>

<p>Below is a list of places we know about in {$adm1_name}, {$references.$ri}. <b>Click a name to run a search for images in that area</b>, the number shown is the approximate number of photographs surrounding the place based on a cross-section of the Geograph archive. If there is only one photograph identified you will be taken directly to the photo page. Note that not all places are shown - we try to pick bigger places, but this selection is approximate.</p>

<div style="position:relative; float:right" class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  Example photo near <b>{$image->placename}</b>: 
  <div class="img-shadow"><a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a></div>
  <div class="caption"><b>{$image->title|escape:'html'}</b><br/> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></div>
</div>

<table><tr>
{foreach name=repeat from=$counts key=pid item=line}
<td>&middot; {if $line.c == 1}<a href="/photo/{$line.gridimage_id}">{else}<a href="/search.php?placename={$pid}&amp;do=1">{/if}<b>{$line.full_name}</b></a> [{$line.c}]</td>
{if $smarty.foreach.repeat.iteration %3 == 0}</tr><tr>{/if}
{/foreach}
</tr></table>

<br style="clear:both"/>

{if $ri == 1} 
<br/><br/>
<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>
{/if}

{include file="_std_end.tpl"}
