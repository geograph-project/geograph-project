{include file="_std_begin.tpl"}



<h2>{$title|escape:'html'|default:'Untitled'} <small>:: Swarm</small></h2>



<div style="text-align:center;position:relative;">By <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></div>



{if $images}
	<p><b>{$images} image{if $images == 1}{else}s{/if} in this swarm{if $images > 10}. Preview shown below:{else}:{/if}</b></p>
{/if}

	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{foreachelse}
		<p><i>no images to display{if $images}, this could be because still pending and/or recently rejected{/if}</i></p>
	{/foreach}
	<br style="clear:both"/>


<ul class="explore">
	{if $query_id}
		<li><a href="/search.php?i={$query_id}"><b>View all images</b> in this swarm</a></li>
	{/if}
	{if $grid_reference}
		<li><a href="/gridref/{$grid_reference}/links"><img src="http://{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" style="padding:2px;" alt="More Links for {$grid_reference}"/></a> <a href="/gridref/{$grid_reference}/links">Links for <b>{$grid_reference}</b></a> | <a href="/gridref/{$grid_reference}"><b>Photos</b> for {$grid_reference}</a></li>
	{/if}
	{if $title}<li><a href="/search.php?searchtext={$title|escape:'url'}&amp;gridref={$grid_reference}&amp;do=1">Find {if $grid_reference}nearby{/if} images <b>mentioning the words [ {$title|escape:'html'} ]</b></a></li>{/if}
</ul>

<br/>


{include file="_std_end.tpl"}