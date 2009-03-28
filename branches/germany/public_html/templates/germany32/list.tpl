
{include file="_std_begin.tpl"}

<h2>{$page_title}</h2>

<ul>
{foreach from=$squares item=square}

		
{if $square.imagecount gt 0}
	{if $square.images}
	  <li><b>{$square.title} ({$square.imagecount} images)</b>
	   <a name="{$square.prefix}"></a><ul>
	    {foreach from=$square.images item=image}
	    <li> <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a> {$image->title}</li>
	    {/foreach}
	    </ul></li>
	{else}
	 <li><a title="List photographs in {$square.title}" href="/list/{$square.prefix}#{$square.prefix}">{$square.title}</a> ({$square.imagecount} images)</li>
	{/if}
{/if}


{/foreach}
</ul>
		
{include file="_std_end.tpl"}
