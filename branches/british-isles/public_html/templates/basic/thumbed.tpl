{assign var="page_title" value="My Thumbed Images"}
{include file="_std_begin.tpl"}

<h2>My own images I've "Liked" {if $criteria}<small style="font-weight:normal">, noted at or before: {$criteria|escape:'html'}</small>{/if}</h2>

   <form method="get" action="{$script_name}" style="padding:10px">
    <p>Type: <select name="type" onchange="this.form.submit()">
    	{html_options options=$types selected=$type}
    </select> 
  <noscript>
    <input type="submit" value="Go"/></noscript></p></form>
    
	{foreach from=$images item=image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		{if $image->comment}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
		{/if}
		
		<div class="interestBox" style="font-size:0.7em;margin-top:7px;width:500px;padding:2px">Links: <a href="/kml.php?id={$image->gridimage_id}">Google Earth</a> <a href="/ecard.php?image={$image->gridimage_id}">eCard</a> {if $enable_forums}<a href="/discuss/index.php?gridref={$image->grid_reference}">Discuss Square</a>{/if} <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
		
	  </div><br style="clear:both;"/>
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
<div style="padding:10px">
<p>This page only notes your images you have noted. Such votes are excluded from public voting anyway.</p>

<p><small>Note: Page generated at 10 minute intervals, please don't refresh more often than that.</small></p> 
</div>

{include file="_std_end.tpl"}
