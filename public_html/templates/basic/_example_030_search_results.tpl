{include file="_std_begin.tpl"}



<h2>Search Results</h2>

<p>{$meta_description|escape:'html'}</p>


{if $images}
	<p><b>{$images} image{if $images == 1}{else}s{/if}{if $images > 20}. Preview shown below:{else}:{/if}</b></p>
{/if}

	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{foreachelse}
		<p><i>no images to display</i></p>
	{/foreach}
	<br style="clear:both"/>


{if $more}
	<ul class="explore">
		{if $query_id}
			<li><a href="/search.php?i={$query_id}&amp;page=2"><b>View next page</b></a></li>
		{/if}
	</ul>
{/if}

<br/>


{include file="_std_end.tpl"}