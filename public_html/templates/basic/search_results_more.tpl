{include file="_search_begin.tpl"}

{if $engine->resultCount}

	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  {if $image->count}
	  	<div style="float:right;position:relative;width:130px;font-size:small;text-align:right">
	  		{$image->count|thousends} images in group
	  	</div>
	  {/if}
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a href="/location.php?gridref={$image->grid_reference}"><img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/></a> <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

		{if $image->comment}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
		{/if}

		<div class="interestBox" style="font-size:0.7em;margin-top:7px;width:500px;padding:2px">Links: <a href="/kml.php?id={$image->gridimage_id}">Google Earth</a> <a href="/ecard.php?image={$image->gridimage_id}">eCard</a> {if $enable_forums}<a href="/discuss/index.php?gridref={$image->grid_reference}">Discuss</a>{/if} <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact Contributor</a> <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>] <span id="hideside{$image->gridimage_id}" onmouseover="show_tree('side{$image->gridimage_id}');refreshMainList({$image->gridimage_id});">[Thumb &amp; Buckets]</div>


	<div style="float:right;position:relative">
	<div style="position:absolute;left:-210px;top:-20px;width:220px;padding:10px;display:none;text-align:left;z-index:1000" id="showside{$image->gridimage_id}" onmouseout="hide_tree('side{$image->gridimage_id}')">
		<div class="interestBox" onmousemove="event.cancelBubble = true" onmouseout="event.cancelBubble = true">
			<img src="http://{$static_host}/img/thumbs.png" width="20" height="20" style="float:left;padding:4px"/>
			<div id="votediv{$image->gridimage_id}img"><a href="javascript:void(record_vote('img',{$image->gridimage_id},5,'img'));" title="I like this image! - click to agree">I like this image!</a></div>
			{if $image->comment}
				<div id="votediv{$image->gridimage_id}desc"><a href="javascript:void(record_vote('desc',{$image->gridimage_id},5,'desc'));" title="I like this description! - click to agree">I like this description!</a></div>
			{/if}
			<br style="clear:both"/>
			<b>Buckets</b><br/>
			{foreach from=$buckets item=item}
					<label id="{$image->gridimage_id}label{$item|escape:'html'}" for="{$image->gridimage_id}check{$item|escape:'html'}" style="color:gray">
					<input type=checkbox id="{$image->gridimage_id}check{$item|escape:'html'}" onclick="submitBucket({$image->gridimage_id},'{$item|escape:'html'}',this.checked?1:0);"> {$item|escape:'html'}
					</label><br/>

			{/foreach}<br/>
			<small>IMPORTANT: Please read the {newwin href="/article/Image-Buckets" title="Article about Buckets" text="Buckets Article"} before picking from this list</small>


		</div>
	</div>
	</div>


	  </div><br style="clear:both;"/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}

<script>//<![CDATA[
{literal}


	function submitBucket(gridimage_id,bucket,status) {
		var data = new Object;
		data['tag'] = "bucket:"+bucket;
		data['status'] = status;
		data['gridimage_id'] = gridimage_id;

		$.ajax({
			url: "/tags/tagger.json.php",
			data: data
		});

		if (document.getElementById(gridimage_id+'label'+bucket)) {
			document.getElementById(gridimage_id+'label'+bucket).style.color = status>0?'':'gray';
			document.getElementById(gridimage_id+'label'+bucket).style.fontWeight = status>0?'bold':'';
		}
	}
	var loadedBuckets = new Array();

	function refreshMainList(gridimage_id) {
		if (gridimage_id && !loadedBuckets[gridimage_id]) {

			var url = '/tags/tags.json.php?gridimage_id='+encodeURIComponent(gridimage_id);

			$.getJSON(url+"&callback=?",
				// on completion, process the results
				function (data) {
					if (data) {
						for(var tag_id in data) {
							if (data[tag_id].prefix == 'bucket' && document.getElementById(gridimage_id+'label'+data[tag_id].tag)) {
								document.getElementById(gridimage_id+'check'+data[tag_id].tag).checked = true;
								document.getElementById(gridimage_id+'label'+data[tag_id].tag).style.color = '';
								document.getElementById(gridimage_id+'label'+data[tag_id].tag).style.fontWeight = 'bold';
							}
						}
					}
				});

			loadedBuckets[gridimage_id] = true;
		}
	}



{/literal}
 //]]></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>



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
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
