<div id="right_block">
<div class="nav">
 {if $recentcount}
  
  	<h3>Recent Photos</h3>
  	
  	{foreach from=$recent item=image}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$image->title|escape:'html'} - click to view full size image" href="/view.php?id={$image->gridimage_id}">{$image->getThumbnail(120,80)}</a>
  	  
  	  <div>
  	  <a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->title|escape:'html'}</a>
  	  by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>
	  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
	  
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}
  
</div> 
</div>