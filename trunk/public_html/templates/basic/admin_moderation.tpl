{assign var="page_title" value="Moderation"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="/admin/moderation.js"></script>


<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Moderation</h2>

{dynamic}
{if $remoderate}{literal}
<script type="text/javascript">
	remoderate = true;
</script>
{/literal}{/if}

{if $unmoderatedcount}

	<p>{if moderator}
		The following images have been recently moderated by the selected moderator.

	{else}
		{if $remoderate}
			As a quick spotcheck you are asked to make a suggestion for these recently moderated images.
		{else}
			The following images have been submitted recently.
		{/if}
	{/if}
	Click an image to view fullsize.</p>
	
	{foreach from=$unmoderated item=image}

	  <div class="photoguide" {if $image->user_status && $image->moderation_status != 'pending'}style="background-color:#eeeeee"{/if} style="width:730px;">
	  
	  <div style="float:left;width:213px">
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
	  </div>
	  
	  <div style="float:left;font-size:0.8em; padding-left:10px; text-align:left;">
	  
	  Square: <b><a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></b> ({$image->imagecount} current images)<br/>
	  by: <b><a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a></b><br/>
	  title: <b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> (<a href="/editimage.php?id={$image->gridimage_id}">edit</a>)<br/>
	  
	  {if $image->comment}
	  comments: <i style="font-size:0.8em">{$image->comment|escape:'html'|geographlinks}</i><br/>
	  {/if}
	  
	  <br/>
	  
	  {if $image->nateastings}
	  	subject: <b>{getamap gridref=$image->getSubjectGridref()}</b>
	  {/if}
	  {if $image->viewpoint_eastings}
	  	photographer: <b>{getamap gridref=$image->getPhotographerGridref()}</b>
	  	<span{if $image->different_square} style="background-color:yellow"{/if}>distance: <b>{$image->distance}</b>km</span>
	  {/if}
	  <br/>
	  
	  <br/>
	  <input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{else}{if $image->different_square} style="color:lightgrey"{/if}{/if}/>
	  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Reject" onClick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  {if (!$remoderate && $image->user_status && $image->moderation_status != 'pending') || $moderator}
	  	<br/>Current Status: {$image->moderation_status}
	  {/if}
	  {if $image->new_status}
	  	<br/><span{if $image->new_status != $image->moderation_status} style="background-color:red"{/if}>Suggested Status: {$image->new_status}</span>
	  {/if}
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  </div>
	  <br style="clear:both"/>
	  </div>


	{/foreach}

	<br style="clear:left;"/>&nbsp;
		
	<div class="interestBox" style="padding-left:100px"><a href="/admin/moderation.php">Next page &gt;</a></div>
{else}

	<p>There are no images awaiting moderation!</p>

{/if}
	
    
{/dynamic}    
{include file="_std_end.tpl"}
