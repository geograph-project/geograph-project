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
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;">{if $map_token}<a href="/mapbrowse.php?t={$map_token}">{/if}<img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/>{if $map_token}</a>{/if}</div>
{/if}
{/dynamic}
</div>
</div>
{/if}	

 {if $recentcount}
  
  	<h3 {if $overview} style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;"{/if}>Recent Photos <small><a href="/search.php?i=5165" title="Recent submissions in Ireland">more</a></small></h3>
  	
  	{foreach from=$recent item=image}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
  	  
  	  <div>
  	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
  	  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
	  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
	  
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}
  
</div> 
</div>