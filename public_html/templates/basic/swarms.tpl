{assign var="page_title" value="Swarms"}
{include file="_std_begin.tpl"}

<h2>Image Swarms</h2>



<p>
	Sample list of automatically detected 'groups' of images... 
</p>





{foreach from=$results item=item}
	
	<div style="border-top: 1px solid gray">
		

		<b><a href="/swarm.php?id={$item.swarm_id}" class="text">{$item.title|escape:'html'|default:'Untitled'}</a></b> {if $item.grid_reference != $grid_reference} :: {$item.grid_reference} {/if}{if $item.distance}(Distance {$item.distance}km){/if}<br/>
		<div style="font-size:0.7em;color:gray;margin-left:10px;">		
		Found {$item.images|thousends} images, <a href="/search.php?{if $item.query_id}i={$item.query_id}&amp;temp_displayclass{else}marked=1&markedImages={$item.ids}&amp;displayclass{/if}=spelling">Display in Spelling Utility</a></div>
		
		<br style="clear:both"/>
	</div>

{foreachelse}
	<p><i>no swarms found</i></p>
{/foreach}

{if $query_info}
	<p><i>{$query_info}</i></p>
{/if}


{include file="_std_end.tpl"}

