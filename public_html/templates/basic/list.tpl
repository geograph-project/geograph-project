
{include file="_std_begin.tpl"}

<h2>{$page_title}</h2>

<ul>
{foreach from=$squares item=square}

<li><a title="List photographs in {$square.title}" href="/list.php?square={$square.prefix}#{$square.prefix}">{$square.title}</a> ({$square.imagecount} images)</li>
		
    {if $square.imagecount gt 0}
    <a name="{$square.prefix}"></a><ul>
    {foreach from=$square.images item=image}
    <li> <a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->grid_reference}</a> {$image->title}</li>
    {/foreach}
    </ul>
    {/if}	


{/foreach}
</ul>
		
{include file="_std_end.tpl"}
