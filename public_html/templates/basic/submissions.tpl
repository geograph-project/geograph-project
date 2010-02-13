{assign var="page_title" value="My Submissions"}
{include file="_std_begin.tpl"}

<h2>My Submissions{if $criteria}<small style="font-weight:normal">, submitted at or before: {$criteria|escape:'html'}</small>{/if}</h2>
	
	<br/>
	
	{foreach from=$images item=image}
	 <div style="border-top: 2px solid lightgrey; padding-top:3px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a><br/>
		<div class="caption">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
		<br/><div class="interestBox"><small>[[[{$image->gridimage_id}]]]</small></div>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $image->realname} by <a title="view user profile" href="/profile/{$user->user_id}?a={$image->realname|escape:'url'}">{$image->realname}</a>{/if}<br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		
		<div><textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea><input type="submit" name="create" value="continue &gt;"/>{if $image->moderation_status == 'pending'}<input type="submit" name="apply" value="apply changes"/>{/if}{if $is_mod || $user->user_id == $image->user_id}
		<br/><a href="#" onclick="show_tree('share{$image->gridimage_id}'); document.getElementById('shareframe{$image->gridimage_id}').src='/submit_snippet.php?gridimage_id={$image->gridimage_id}&gr={$image->grid_reference}';return false;" id="hideshare{$image->gridimage_id}" style="font-size:0.7em">Open Shared Description Box</a>
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
	 	nothing to see here
	{/foreach}
	
	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em"><b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>


<br/><br/>
<div class="interestBox">Navigation: <b>|
{if $prev == 1}
	<a href="{$script_name}">Previous</a> |
{elseif $prev}
	<a href="{$script_name}?next={$prev|escape:'url'}">Previous</a> |
{/if}
{if $next}
	<a href="{$script_name}?next={$next|escape:'url'}">Next</a> |
{/if}</b>
</div>

<p><small>Note: Page generated at 10 minute intervals, please don't refresh more often than that.</small></p> 


{include file="_std_end.tpl"}
