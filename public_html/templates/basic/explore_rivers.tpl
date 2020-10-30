{include file="_std_begin.tpl"}

<h2>{$page_title}</h2>   
{if $start_info}
<p>{$start_info}</p>
{/if}    

    
    <b>Beginning with</b>: 
    {foreach from=$stats key=alp item=count}
		{if $alp === $alpha}
			<b title="{$count} {$keyword}">{$alp|escape:'html'}</b>
		{else}
			<a href="?alpha={$alp|escape:'url'}" title="{$count} {$keyword}">{$alp|escape:'html'}</a> 
		{/if}
    {/foreach}
    
    <hr/>
    
    <b>{$keyword} on this page</b>:
    {foreach from=$results item=row}
		<a href="#{$row.hash}">{$row.name|escape:'html'}</a> |
    {/foreach}
    
    <hr/>
    
	{foreach from=$results item=row}
	
		<div style="padding:20px">
			<div class="interestBox" style="margin-bottom:5px">
				<div style="position:relative;float:right">
					<a href="/of/{$row.q|escape:'url'}">More images of <b>{$row.name|escape:'html'}</b></a>
				</div>
				<a name="{$row.hash}"></a>
				<big>{$row.name|escape:'html'}</big>{if $row.county}, {$row.county|escape:'html'}{/if} {if $row.count}[~{$row.count} images]{/if}
			</div>	
			{foreach from=$row.images item=image}
			  <div style="float:left;position:relative; width:130px; height:130px">
			  <div align="center">
			  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
			  </div>
			{foreachelse}
				<p><i>no images to display{if $images}, this could be because still pending and/or recently rejected{/if}</i></p>
			{/foreach}
			<br style="clear:both"/>
		</div>
	{/foreach}
	
	
<br style="clear:both;"/>

	<hr>

    <b>Beginning with</b>: 
    {foreach from=$stats key=alp item=count}
		{if $alp === $alpha}
			<b title="{$count} {$keyword}">{$alp|escape:'html'}</b>
		{else}
			<a href="?alpha={$alp|escape:'url'}" title="{$count} {$keyword}">{$alp|escape:'html'}</a> 
		{/if}
    {/foreach}


{if $extra_info}
<p>{$extra_info}</p>
{/if}

{include file="_std_end.tpl"}
