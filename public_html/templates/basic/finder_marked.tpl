{assign var="page_title" value="Marked Images"}
{include file="_std_begin.tpl"}

	<h2>Marked Images</h2>
	{dynamic}
				<div style="position:relative;clear:both"/>
				<br/>
				<div class="interestBox longLinks" style="font-size:0.8em">
				<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
				<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
				&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
				<script>
				AttachEvent(window,'load',showMarkedImages,false);
				</script>
				</div>

		<div style="padding:10px;">

		<p>
The marked list is a temporary list in your current browser session. To keep the current list long term can use the 'View as Search Result' above - which creates a permanent result page of these images. Can share that url with others to see the same images.
{if $user->registered}As logged in{else}If you were logged in{/if}, will also be saved in your profile, and available in the 'recent searches' list on <a href="/search.php">search homepage</a>.</p>

		<p>Note: You can also <a href="/browser/#!/marked=1">View marked images in the Browser function</a> (look for the 'Marked List' menu top right)</p>

		{if $results}
			<h3 style=color:black>Currently Marked Images - <small><a href="{$script_name}">Reload</a></small></h3>
			{if $count > 100}
				<p><i>Preview of 100 (of {$count}) images shown below. To view the full list, use the 'View as Search Result' above (although may still be split into pages), or use the Browser function</i></p>
			{/if}
			<div>
			{foreach from=$results item=image}
				<div style="float:left;position:relative; width:130px; height:130px">
				<div align="center">
				<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string}{if $image->count} - {$image->count|thousends} images in group{/if} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
				<div style="text-align:center; width:130px; font-size:0.7em">{if $image->user_id == $user->user_id}<a href="/editimage.php?id={$image->gridimage_id}">Edit</a>{/if} [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
				</div>
			{/foreach}
			<br style="clear:both"/>
			</div>


			<br/>
		{else}
			<p>You have no marked images. Click a [Mark] link on a photo page.</p>
		{/if}

		</div>
	{/dynamic}

{include file="_std_end.tpl"}
