{include file="_std_begin.tpl"}

<h2>{$page_title}</h2>   
{if $start_info}
<p>{$start_info}</p>
{/if}    
    
	{foreach from=$results item=image}
	 <div>
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
	  	<b>{$image->county}</b><br/>
		<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a> <br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
	  </div><br style="clear:both;"/>
	 </div>
	{/foreach}
<br style="clear:both;"/>

<h3>Without images yet</h3>

<ul>
    {foreach from=$unfilled item=result}
    <li> <a href="/gridref/{$result.grid_reference}">{$result.grid_reference}</a> {$result.county}</li>
    {/foreach}
</ul>

{if $nonland}
<h4>The following are not on land</h4>
<p style="font-size: 0.7em">We may in the future pick the closest land square</p>

<ul>
    {foreach from=$nonland item=result}
    <li>{$result.county}</li>
    {/foreach}
</ul>
{/if}


{if $extra_info}
<p>{$extra_info}</p>
{/if}

{include file="_std_end.tpl"}
