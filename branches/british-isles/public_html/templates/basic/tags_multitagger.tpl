{assign var="page_title" value="Multi Tagger"}
{include file="_std_begin.tpl"}


<h2>Multi Tagger</h2>

<p>This page allows you to run a keyword search to find images, and then add tags to the first 50 results in one go. The 50 limit may be removed later.

{literal}
  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<div class="interestBox">
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/><br/>
			<label for="onlymine">Only your images?</label> <input type="checkbox" name="onlymine" id="onlymine" {if $onlymine}checked{/if}/>
		</div>
	</form>




{if $images}






		{foreach from=$images item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">

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

	<p>Displaying {$imagecount} of {$totalcount|thousends} matches</p>

	{if $imagecount eq 50}
		<p>
			<small>&middot; To refine the results simply add more keywords</small>
		</p>
	{/if}


	{if $used}
		Your tags on these images:
		<ul>
			{foreach from=$used item=item}
				<li><span class="tag">
						<a href="/tags/?tag={if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}&amp;photo={$image->gridimage_id}" class="taglink">{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</a>
				</span> used on <b>{$item.images} images</b>. <small><a href="" onclick="alert('todo - not functional yet'); return false;">Delete from all</a> | <a href="" onclick="alert('todo - not functional yet'); return false;">Add as Public tag to all</a> | <a href="" onclick="alert('todo - not functional yet'); return false;">Add as Private tag to all</a></small></li>
			{/foreach}
		</ul>
	{/if}



	<div>Tagging box for all these images. Any tag added here, will be added to all images above. {if !$onlymine}If an image isn't yours and you set the tag to public, it will be default to a private tag.{/if}</div>
			<iframe src="/tags/tagger.php?ids={$idlist}" height="200" width="100%" id="tagframe"></iframe>


{/if}


{include file="_std_end.tpl"}

