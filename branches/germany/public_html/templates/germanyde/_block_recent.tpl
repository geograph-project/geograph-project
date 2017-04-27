<div id="right_block">
<div class="nav">
{if $overview}

<h3>Overview Map</h3>
<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img 
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
{dynamic}
{if $marker}
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;">{if $map_token}<a href="/mapbrowse.php?t={$map_token}">{/if}<img src="//{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/>{if $map_token}</a>{/if}</div>
{/if}
{/dynamic}
</div>
</div>
{/if}	

 {if $recentcount}
  
	<div style="{if $overview} padding-top:15px; border-top: 2px solid black; margin-top: 15px;{/if} float:right">
	<p style="margin:0">
	<small style="white-space:nowrap">[<a href="{if $recentsearch}/results/{$recentsearch}{else}/search.php?displayclass=full&amp;orderby=submitted&amp;breakby=submitted&amp;reverse_order_ind=1&amp;resultsperpage=15&amp;do=1{/if}" title="Neu hochgeladene Bilder zeigen">mehr</a>|<a href="{if $recentsearchcur}/results/{$recentsearchcur}{else}/search.php?displayclass=full&amp;orderby=imagetaken&amp;breakby=imagetaken&amp;reverse_order_ind=1&amp;resultsperpage=15&amp;do=1{/if}" title="Aktuelle Fotos zeigen">neu</a>]</small>
	</p></div>
  	<h3 {if $overview} style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;"{/if}>Letzte Bilder</h3>
  	
  	{foreach from=$recent item=image}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$image->title|escape:'html'} - Zum Vergr��ern anklicken" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,false,'src',3)}</a>
  	  
  	  <div>
  	  <a title="Vollbild" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
  	  von <a title="Profil anzeigen" href="{$image->profile_link}">{$image->realname}</a>
	  f�r Planquadrat <a title="Seite f�r {$image->grid_reference} anzeigen" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
	  
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}
  
</div> 
</div>
