{assign var="page_title" value="Photographs"}
{include file="_std_begin.tpl"}

<h2>Photograph Listing</h2>

{foreach from=$squares item=square}

<h3>{$square.title}</h3>
<ul>		
    {foreach from=$square.images item=image}
    <li><a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->gridref}</a> {$image->title}</li>
    {/foreach}
</ul>		


{/foreach}
		
{include file="_std_end.tpl"}
