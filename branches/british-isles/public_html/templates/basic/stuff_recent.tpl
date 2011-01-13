{assign var="page_title" value="Recent Photos"}
{include file="_std_begin.tpl"}

	<h2>Recently Viewed Photos <small><a href="{$script_name}">Reload</a></small></h2>

	<div class="interestBox">An image is only registered when viewing the photo page. This page will show up to 30 images.</div>
	<br/>
	{dynamic}
		{if $results}

			<div>
			{foreach from=$results item=image}
				<div style="float:left;position:relative; width:130px; height:130px">
				<div align="center">
				<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string}{if $image->count} - {$image->count|thousends} images in group{/if} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
				<div style="text-align:center; width:130px; font-size:0.7em"><a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
				</div>
			{/foreach}
			<br style="clear:both"/>
			</div>

				<div style="position:relative;clear:both"/>
				<br/><br/>
				<div class="interestBox" style="font-size:0.8em">
				<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
				<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
				&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
				<script>
				AttachEvent(window,'load',showMarkedImages,false);
				</script>
				</div>

			<br/>
			<div class="interestBox" style="font-size:0.8em">Note: This list is optimistic by nature, sometimes images aren't shown here, and/or the list may be periodically cleared. Just provided in the hope that it might be useful. Your browser history function should provide a more comprehensive list.</div>
		{else}
			<p>Nothing to see here.</p>
		{/if}
	{/dynamic}

{include file="_std_end.tpl"}
