{include file="_search_begin.tpl"}

{if $engine->resultCount}

	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	 <div style="border-top: 2px solid lightgrey; padding-top:3px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" name="form{$image->gridimage_id}" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
		<br/><div style="font-size:0.7em;">[[[{$image->gridimage_id}]]]
		{if $image->count}
			<br/><br/>
			{$image->count|thousends} images in group
		{/if}
		</div>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}


		<div><textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea><input type="submit" name="create" value="continue &gt;"/>{if $is_mod || $user->user_id == $image->user_id}
		<br/><span id="hideshare{$image->gridimage_id}" style="font-size:0.8em">&middot; <a href="#" onclick="return open_shared({$image->gridimage_id},'{$image->grid_reference}','');">Open <b>Shared Description<span id="c{$image->gridimage_id}"></span></b> Box</a> [ <a href="#" onclick="return open_shared({$image->gridimage_id},'{$image->grid_reference}','&tab=recent');">Recent</a> | <a href="#" onclick="return open_shared({$image->gridimage_id},'{$image->grid_reference}','&tab=suggestions');">Suggestions</a> | <a href="#" onclick="return open_shared({$image->gridimage_id},'{$image->grid_reference}','&create=true');">Quick Create</a> ]</span>
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

		<span id="hidetag{$image->gridimage_id}" style="font-size:0.8em">Tags: <span id="tags{$image->gridimage_id}"></span> &middot; <a href="#" onclick="return open_tagging({$image->gridimage_id},'{$image->grid_reference}','');">Open <b>Tagging</b> Box</a></span>

		  <div class="interestBox" id="showtag{$image->gridimage_id}" style="display:none">
				<iframe src="about:blank" height="300" width="100%" id="tagframe{$image->gridimage_id}">
				</iframe>
				<div><a href="#" onclick="hide_tree('tag{$image->gridimage_id}');return false">- Close <i>Tagging</I> box</a> ({newwin href="/article/Tags" text="Article about Tags"})</div>
		  </div>


	  </form><br/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}

	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}

<script type="text/javascript">
{literal}
function open_shared(gid,gr,extra) {
	show_tree('share'+gid);

	if (extra == '&tab=suggestions') {
		var thatForm = document.forms['form'+gid];

		if (thatForm.elements['title']) {
			str = thatForm.elements['title'].value;
		}
		if (thatForm.elements['comment']) {
			str = str + ' '+ thatForm.elements['comment'].value;
		}
		if (thatForm.elements['imageclass']) {
			str = str + ' '+ thatForm.elements['imageclass'].value;
		}

		extra= extra + "&corpus="+encodeURIComponent(str.replace(/[\r\n]+/,' '));
	}


	document.getElementById('shareframe'+gid).src='/submit_snippet.php?gridimage_id='+gid+'&gr='+gr+extra;
	return false;
}
function open_tagging(gid,gr,extra) {
	show_tree('tag'+gid);


	document.getElementById('tagframe'+gid).src='/tags/tagger.php?gridimage_id='+gid+'&gr='+gr+extra;
	return false;
}
{/literal}
</script>
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
