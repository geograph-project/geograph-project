
{dynamic}

  
		
		{foreach from=$images item=image}
		
		  <div style="float:left;" class="photo33"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
		  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
		  
		  {if $is_admin}
			  <div class="caption">status: {$image->moderation_status}
			  {if $image->ftf}(FTF){/if}
			  </div>
		  {/if}
		  
		  
		  </div>
		  
		  
		{/foreach}
		
		<br style="clear:left;"/>&nbsp;
		

{/dynamic}

