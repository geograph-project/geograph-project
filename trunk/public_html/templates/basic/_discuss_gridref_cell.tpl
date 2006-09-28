
{dynamic}
 {if $place.distance}
 <h3 style="text-align:left; margin-top:0; padding-left:10px; font-weight:normal; color:silver;">{if $place.distance > 3}{$place.distance-0.01} km from{else}near to{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && ($place.adm1_name != $place.reference_name || $place.hist_county)}, <span{if $place.hist_county} title="Historic County: {$place.hist_county}"{/if}>{$place.adm1_name}</span>{/if}, {$place.reference_name}</i></small></h3>
 {/if}

  
		
		{foreach from=$images item=image}
		
		  <div style="float:left;" class="photo33"><a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a>
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

