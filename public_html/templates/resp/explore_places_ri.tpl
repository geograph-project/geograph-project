{assign var="page_title" value="`$references.$ri` Photos"}
{assign var="meta_description" value="Geograph page for finding Creative Commons licensed images of `$references.$ri`"}
{include file="_std_begin.tpl"}
 
<h2><a href="/explore/places/">Places Directory</a> &gt; {$references.$ri}</h2>

<p>{if $ri == 2}At this point we don't have data to be able to offer a breakdown for Northern Ireland, so it appears as one entry. {/if}If there is only one place in the division you will be taken direct to an image search, otherwise click to view a list of places</p>

{if $ri == 1}<h4>Local Authorities of {$references.$ri} in alphabetical order:</h4>{/if}

{if $image}
<div style="position:relative; float:left; clear:both" class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  Example photo in <b>{$image->county}</b>: 
  <div class="img-shadow"><a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a></div>
  <div class="caption"><b>{$image->title|escape:'html'}</b><br/> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></div>
</div>
{/if}
<br style="clear:both"/>
{assign var="last" value="--"}
{foreach from=$counts key=adm1 item=line}
	{if $last != $line.country}
		{if $line.country}
			</ul>
			<h3>{$line.country}</h3>
		{/if}
		{assign var="last" value=$line.country}
		<ul>
	{/if}
	<li>{if $line.places == 1}<a href="/search.php?placename={$line.placename_id}&amp;do=1" title="Place: {$line.full_name}">{else}<a href="/explore/places/{$ri}/{$adm1}/"  title="EXAMPLE Place: {$line.full_name}">{/if}<b>{$line.name}</b></a> [{$line.places} Places, {$line.images} Images]</li>
{/foreach}
</ul>

<br style="clear:both"/>

{if $ri == 1} 
<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>
{/if}

{include file="_std_end.tpl"}