{assign var="page_title" value="Multi Tagger"}
{include file="_std_begin.tpl"}

{literal}
<style>
 input[type="checkbox"].tag + label {
    color:red;
 }
 input[type="checkbox"].tag:checked + label {
    font-weight:bold;
    color:darkgreen;
 }
</style>
<script>
	function clear_form(ele) {
		if (ele.value != ele.defaultValue && document.getElementById("resultForm") && document.getElementById("resultForm").remove) 
			document.getElementById("resultForm").remove();
	}
</script>
{/literal}

{dynamic}
<h2>Multi Tagger</h2>

<p>This page allows you to run a keyword search to find images, and then <b>add tags</b> to the first 50 results in one go. The 50 limit may be removed later.

	<form action="{$script_name}" method="get">
		<input type=hidden name=simple value=1>
		<div class="interestBox">
			<label for="tag">Tag</label>:  <input type="text" name="tag" id="tag" size="40"{if $thetag} value="{$thetag|escape:'html'}"{/if} oninput="clear_form(this)"/>
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{if $q} value="{$q|escape:'html'}"{/if} oninput="clear_form(this)" title="Tip: if trying to exclude false matches, use negative phrases, not words. E.g. if searching [drone], but getting lots of [Drone Hill] results that not interested in; Then search [drone -&quot;drone hill&quot;] rather than just [drone -hill], so you get other mentions of drone, so can still get [Drone flying on Hoad Hill]"/>
			<input type="submit" value="Search"/><br/>
			<label for="onlymine"><b>Only your images?</b></label> <input type="checkbox" name="onlymine" id="onlymine" {if $onlymine}checked{/if}/> (can only add tags to your own images anyway)
			<label for="exclude"><b>Exclude already tagged?</b></label> <input type="checkbox" name="exclude" id="exclude" {if $exclude}checked{/if}/> (may not exclude recently tagged images) 
		</div>
	</form>

{if $images}
	<br><br>
	Tick the box(es) against the image(s) you wish to add the [{$thetag|escape:'html'}] tag to. Tick as few or as many as apply.
	<form method="post" id="resultForm">
		{foreach from=$images item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">

			  <div style="float:left; position:relative; width:100px; text-align:center">
				<input type="checkbox" class=tag name="yes[]" value="{$image->gridimage_id}" id="c{$image->gridimage_id}"
					{if $done && in_array($image->gridimage_id, $done)} checked readonly disabled{/if}
					><label for="c{$image->gridimage_id}">Yes</label>
			  </div>

			  <div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			  </div>
			  <div style="float:left; position:relative; ">
				<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
				by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
				{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				<br/>

				{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
				{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

				{if $image->comment}
				<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
				{/if}
			  </div><br style="clear:both;"/>
			 </div>
		{/foreach}
		<input type="submit" value="Save Ticked">

		<p>Displaying {$imagecount} of {$totalcount|thousends} matches</p>

		{if $imagecount eq 50}
			<p>
				<small>&middot; To refine the results simply add more keywords</small>
			</p>
		{/if}
	</form>
{/if}

{/dynamic}

{include file="_std_end.tpl"}

