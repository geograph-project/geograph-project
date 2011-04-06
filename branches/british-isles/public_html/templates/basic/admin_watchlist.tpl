{assign var="page_title" value="Watch List"}
{include file="_std_begin.tpl"}

<h2>Watch List</h2>

<p>These are images, have have been flagged against the watches words. Once muted a image wont reappear until its edited - and the watch word is still present. You should make any alterations to the image required before pressing the mute button.</p>

{dynamic}

	{foreach from=$images item=image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="div{$image->gridimage_id}">
	 	<div style="float:right">
	 		{$image->word|escape:'html'}
			<input type="button" value="Mute" onclick="mute({$image->gridimage_id});"/>
	 	</div>
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative; width:80%">
		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
		[<a title="view edit page" href="/editimage.php?id={$image->gridimage_id}">Edit</a>]
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]<br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

		{if $image->comment}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;padding-bottom:7px">{$image->comment|escape:'html'|geographlinks}</div>
		{/if}

	  </div><br style="clear:both;"/>
	 </div>
	{foreachelse}
	 	<ul><li>No images match the selected options.</li></ul>
	{/foreach}

{if $images}
	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>


	<br/><br/>

	<div style="padding:10px" class="interestBox">
		<a href="?">Continue...</a>
	</div>
{/if}
{/dynamic}

<script type="text/javascript">{literal}
function mute(id) {

	var i=new Image();
	id = encodeURIComponent(id);
	i.src= "/admin/watchlist.php?id="+id;
	document.getElementById("div"+id).innerHTML = "<div style='padding:20px;text-align:center'>Thank you!</div>";
	setTimeout(function() {
		document.getElementById("div"+id).style.display='none';
	},1000);

}
{/literal}</script>

{include file="_std_end.tpl"}
