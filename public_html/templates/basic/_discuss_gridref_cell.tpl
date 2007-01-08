
{dynamic}
 {if $place.distance}
 <h3 style="text-align:left; margin-top:0; padding-left:10px; font-weight:normal; color:silver;">{place place=$place}</h3>
 {/if}

  
		
		{foreach from=$images item=image}
		
		  <div class="photo33" style="float:left;width:200px;height:200px;padding:3px"><a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a>
		  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
		  
		  {if $is_admin}
			  <div class="statuscaption">status: {$image->moderation_status}
			  {if $image->ftf}(FTF){/if}
			  </div>
		  {/if}
		  
		 <div style="font-size:0.7em"><br/>Insert: <a href="javascript:paste_strinL('[[[{$image->gridimage_id}]]]',0)">Thumbnail</a> or <a href="javascript:paste_strinL('[[{$image->gridimage_id}]]',0)">Text Link</a></div>
		  
		  </div>
		  
		  
		{/foreach}
		
		<br style="clear:left;"/>&nbsp;
		

{/dynamic}

