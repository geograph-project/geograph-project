{assign var="page_title" value="Grouping "}
{include file="_std_begin.tpl"}



	<h2>{$topic_title|escape:'html'}</h2>


{if $topic_title}
	

	{if $results}
		
		
		{foreach from=$results item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">
			  <div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			  </div>
			  <div style="float:left; position:relative; ">
				<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
				by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
				{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				<br/>
				
				{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
				{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

				{if $image->comment}
				<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
				{/if}
			  </div><br style="clear:both;"/>
			 </div>
		{/foreach}

	{/if}
	
	
	

{else}
	<p>Unable to load this search.</p>
{/if}


{include file="_std_end.tpl"}
