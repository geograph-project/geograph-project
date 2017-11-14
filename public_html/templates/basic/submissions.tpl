{assign var="page_title" value="My Submissions"}
{include file="_std_begin.tpl"}

<h2>My Submissions{if $criteria}<small style="font-weight:normal">, submitted at or before: {$criteria|escape:'html'}</small>{/if}</h2>

	<br/>

	{foreach from=$images item=image}
	 <div style="border-top: 2px solid lightgrey; padding-top:3px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" name="form{$image->gridimage_id}" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a><br/>
		<div class="caption">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
		<br/><div style="font-size:0.6em;">[[[{$image->gridimage_id}]]]</div>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title1|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a><br />
		<input type="text" name="title2" size="80" value="{$image->title2|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/>
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $image->realname} by <a title="view user profile" href="/profile/{$user->user_id}?a={$image->realname|escape:'url'}">{$image->realname}</a>{/if}<br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}


		<div><textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment1|escape:'html'}</textarea><br/><textarea name="comment2" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment2|escape:'html'}</textarea><input type="submit" name="create" value="Continue &gt;"/>{*if $image->moderation_status == 'pending'}<input type="submit" name="apply" value="Apply changes"/>{/if*}
		</div>
	  </div><br style="clear:both;"/>


	  </form><br/>
	 </div>
	{foreachelse}
	 	nothing to see here
	{/foreach}

	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="http://www.geograph.org.uk/article/The-Mark-facility" class="about">About</a></div>
	<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>


<br/><br/>
{if $prev || $next}
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
{/if}

<p><small>Note: Page generated at 10 minute intervals, please don't refresh more often than that.</small></p>

{include file="_std_end.tpl"}
