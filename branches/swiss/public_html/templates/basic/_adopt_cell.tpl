<div style="text-align:center;height:120px;line-height:120px;vertical-align:middle;">

<a title="{$image->grid_reference} :: {$image->title|escape:'html'} by {$image->realname|escape:'html'}" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)|replace:"/>":" align='absmiddle' />"}</a>

</div>
{if $image->imagecount > 1}
	<div>
	&nbsp;<b>{$image->imagecount}</b> <a href="#" onclick="return showbox({$image->gridsquare_id},{$x},{$y})">images</a>
	</div>
{/if}	  