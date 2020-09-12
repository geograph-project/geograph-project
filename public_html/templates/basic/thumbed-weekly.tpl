{assign var="page_title" value=$title|default:"Thumbed Images"}
{include file="_std_begin.tpl"}

<div style="float:right;background-color:lightgreen;padding:2px;position:relative">
	<a href="/help/voting">About voting</a>
</div>

<h2>{$title|default:'This weeks Popular images'}</h2>

{if $types}
 <div class="interestBox" style="margin:10px">
   <div style="float:right">
     Showing images receiving one or more 'Thumbs Up' clicks in the last week.
   </div>
   <form method="get" action="{$script_name}" style="display:inline">
    <select name="type" onchange="this.form.submit()">
    	{html_options options=$types selected=$type}
    </select>
  <noscript>
    <input type="submit" value="Update"/></noscript></form></div>
{else}
	<br/>
{/if}

	{foreach from=$images item=image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]<br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

		{if $image->comment}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
		{/if}

	  </div><br style="clear:both;"/>
	 </div>
	{foreachelse}
	 	<ul><li>No images match the selected options.</li></ul>
	{/foreach}

	{if $images}
	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em"><b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>
	{/if}

<br/><br/>

<div style="padding:10px">

<p><small>Note: Page generated at 1 hour intervals, please don't refresh more often than that.</small></p>
</div>

{include file="_std_end.tpl"}

