{include file="_search_begin.tpl"}

{if $engine->resultCount}

	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		
		<div>{if $image->comment}<textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea>{/if}<input type="submit" name="create" value="continue &gt;"/>{if $is_mod || $user->user_id == $image->user_id}
		<br/><a href="#" onclick="show_tree('share{$image->gridimage_id}'); document.getElementById('shareframe{$image->gridimage_id}').src='/submit_snippet.php?gridimage_id={$image->gridimage_id}&gr={$image->grid_reference}';return false;" id="hideshare{$image->gridimage_id}" style="font-size:0.7em">Shared Descriptions</a>
		{/if}
		</div>
	  </div><br style="clear:both;"/>
		{if $is_mod || $user->user_id == $image->user_id} 
		  <div class="interestBox" id="showshare{$image->gridimage_id}" style="display:none">
			<iframe src="about:blank" height="400" width="100%" id="shareframe{$image->gridimage_id}">
			</iframe>
			<div><a href="#" onclick="hide_tree('share{$image->gridimage_id}');return false">- Close <i>Shared Descriptions</I> box</a> ({newwin href="/article/Shared-Descriptions" text="Article about Shared Descriptions"})</div>
		  </div>
		{/if}
	  </form><br/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	
	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em"><b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>
	
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
