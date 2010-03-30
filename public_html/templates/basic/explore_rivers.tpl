{include file="_std_begin.tpl"}

<h2>{$page_title}</h2>   
{if $start_info}
<p>{$start_info}</p>
{/if}    
    
    Rivers on this page: |
    {foreach from=$results item=row}
		<a href="#{$row.hash}">{$row.name|escape:'html'}</a> | 
    {/foreach}
    
    <hr/>
    
	{foreach from=$results item=row}
	
		<div style="padding:20px">
			<div class="interestBox" style="margin-bottom:5px">
				<div style="position:relative;float:right">
					<a href="/search.php?q={$row.q|escape:'url'}">More images of <b>{$row.name|escape:'html'}</b></a>
				</div>
				<a name="{$row.hash}"></a>
				<big>{$row.name|escape:'html'}</big>{if $row.county}, {$row.county|escape:'html'}{/if}
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


{if $extra_info}
<p>{$extra_info}</p>
{/if}

{include file="_std_end.tpl"}
