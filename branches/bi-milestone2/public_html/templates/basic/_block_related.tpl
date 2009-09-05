<div id="right_block">
<div class="nav">

 {if $relatedcount}
  
  	{if $related_keywords}<h3 style="margin-bottom:0">Nearby and possibly related</h3>
  	<div><small>[<a href="/search.php?text={$related_keywords|escape:'url'}" title="View more related images">view more...</a>]</small></div><br/>
  	{else}
  	<h3>Nearby and Related</h3>
  	{/if}
  	
  	{foreach from=$related item=img}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$img->title|escape:'html'} - click to view full size image" href="/photo/{$img->gridimage_id}">{$img->getThumbnail(120,120)}</a>
  	  
  	  <div>
  	  <a title="view full size image" href="/photo/{$img->gridimage_id}">{$img->title|escape:'html'}</a>
  	  by <a title="view user profile" href="{$img->profile_link}">{$img->realname}</a>
  	  {if $img->grid_reference != $image->grid_reference}
	  for square <a title="view page for {$img->grid_reference}" href="/gridref/{$img->grid_reference}">{$img->grid_reference}</a>
	  {/if}
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}
  
</div> 
</div>
