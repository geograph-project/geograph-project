{include file="_search_begin.tpl"}
<script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>
			  
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"html"}">Submit Yours Now</a>]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
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
		
		<small style="font-size:0.7em;border-top:1px solid lightgrey; background-color:#eeeeee; margin-top:7px;width:500px;">Links: <a href="/kml.php?id={$image->gridimage_id}">Google Earth</a> <a href="/ecard.php?image={$image->gridimage_id}">eCard</a> {if $enable_forums}<a href="/discuss/index.php?gridref={$image->grid_reference}">Discuss</a>{/if} <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact Contributor</a> <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</small>
		  {if $is_mod}
			  <form method="post">
			  <p><input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
			  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
			  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
			  <span class="caption" id="modinfo{$image->gridimage_id}">Current Classification: {$image->moderation_status}{if $image->mod_realname}<small><small>, by <a href="/usermsg.php?to={$image->moderator_id}&amp;image={$image->gridimage_id}">{$image->mod_realname}</a></small></small>{/if}</span></p>
			  </form>
 		 {/if}
	  </div><br style="clear:both;"/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	
	<div style="position:relative">
	<br/><br/>
	<small style="padding:10px; background-color:#eeeeee;">Marked Images<span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)</small></div>
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
