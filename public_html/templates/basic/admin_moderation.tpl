{assign var="page_title" value="Moderation"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>

{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);
</script>{/literal}

<h2>{if $is_admin || $is_mod}<a title="Admin home page" href="/admin/index.php">Admin</a> : {/if}Moderation</h2>

	
	
{dynamic}
{if $remoderate}{literal}
<script type="text/javascript">
	remoderate = true;
</script>
{/literal}{/if}

{if $unmoderatedcount}

	<ul>

	{if $apply}
		<li>To get a feel for the moderation process, please make your suggestions for the images below. This is a dummy run, no actual moderations are taking place. Any change requests are created as normal. Make sure you click the 'Finish my application' when finished!</li>
	{elseif $review}
		<li>The following images have been recently moderated to be different to the status you previously selected, there is no need to change anything.</li>
	{elseif $moderator}
		<li>The following images have been recently moderated by the selected moderator. There is no need to do anything, but if you believe the original moderation was wrong just use the moderation buttons as normal.</li>
	{elseif $remoderate}
		<li><b>As a quick spotcheck you are asked to make a suggestion for these recently moderated images.</b></li>
	{else}
		<li>The following images have been submitted recently.</li>
	{/if}
 
	{if !$moderator && !$review}
		<li>Simply look at each image in turn and click the relevant button. The result of the action is displayed just below the button.</li>
	
		<li><b>Remember you absolutely MUST wait for confirmation after clicking the moderation button, before moving onto the next image.</b></li> 
	
		<li>Sometimes a button is grayed out, this is at the suggestion of the submitter themselves, and/or the system. Please moderate as you normally would, but you can take the suggestion into account. </li>
	
		{if !$apply}
			<li><span style="color:red">New!</span> Can now rate images at during moderation. '3' stars is average, and is the same as no vote.</li>
		{/if}
	{/if}

	</ul><br/>
	
	{foreach from=$unmoderated item=image}

	  <div class="photoguide" style="{if $image->user_status && $image->moderation_status != 'pending'}background-color:#eeeeee;{/if}font-size:0.8em;text-align:left;width:inherit">
	  
	  <div style="float:left;width:213px">
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
	  </div>
	  
	  <div style="margin-left:233px"> 
	  
	  square: <b><a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></b> ({$image->imagecount} images) &nbsp;&nbsp; category: <b><a href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:'url'}&amp;do=1">{$image->imageclass}</a></b><br/>
	  by: <b><a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></b> ({$image->images} images)<br/>
	  title: <b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> (<a href="/editimage.php?id={$image->gridimage_id}">edit</a>)<br/>
	  
	  {if $image->comment}
	  comments: <i style="font-size:0.8em">{$image->comment|escape:'html'|geographlinks}</i><br/>
	  {/if}
	  
	  <br/>
	  <span style="font-family:verdana, arial, sans serif; font-size:0.9em">
	  {if $image->nateastings}
	  	subject: <b>{getamap gridref=$image->getSubjectGridref(true) title="(`$image->subject_gridref_precision`m precision)"}</b>
	  {else}
	  	map: <b>{getamap gridref=$image->grid_reference title="(1000m precision)"}</b>
	  {/if}
	  {if $image->viewpoint_eastings}
	  	| photographer: <b>{getamap gridref=$image->getPhotographerGridref(true) title="(`$image->photographer_gridref_precision`m precision)"}</b>{if $image->different_square_true}(diff){/if}
	  	| <span{if $image->different_square} class="interestBox"{/if}>distance: <b><a>{$image->distance}</a></b>km</span>
	  {/if}
	  </span>
		{if $image->reopenmaptoken}
			<div style="float:right;position:relative"><a href="/submit_popup.php?t={$image->reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;" class="xml-geo">Map</a>&nbsp;</div>
		{/if}
	  <br/>
	  
	  <br/>
	  {if $image->sizestr}
	  	<div style="float:right; background-color:red; color:white; border:1px solid pink; padding:6px;">{$image->sizestr}</div>
	  {/if}
	  
	  <input class="accept" type="button" id="geograph{$image->gridimage_id}" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey;"{else}{if $image->different_square} style="color:lightgrey;"{/if}{/if}/>
	  <input class="accept" type="button" id="accept{$image->gridimage_id}" value="Supp" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey;"{/if}/>
	  <input class="reject" type="button" id="reject{$image->gridimage_id}" value="Reject" onClick="moderateImage({$image->gridimage_id}, 'rejected')"/>

{if !$apply}
<span id="votediv{$image->gridimage_id}"> : {votestars id=$image->gridimage_id type="mod"}</span>
{/if}

	  {if (!$remoderate && $image->user_status && $image->moderation_status != 'pending') || $moderator || $review}
	  	<br/>Current Classification: {$image->moderation_status} {if $image->mod_realname}, by {$image->mod_realname}{/if}
	  {/if}
	  {if $image->new_status}
	  	<br/><span{if $image->new_status != $image->moderation_status} style="border:1px solid red; padding:5px; line-height:3em;"{/if}>Suggested Classification: {$image->new_status} {if $image->ml_realname}, by {$image->ml_realname}{/if}</span>
	  {/if}
	  {if $image->user_status}<div class="caption" style="color:red">User: suggest {$image->user_status}</div>{else}{if $image->different_square}<div class="caption" style="color:red">System: suggest Accept</div>{/if}{/if}
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  </div>
	  
	  </div>


	{/foreach}

	<br style="clear:left;"/>&nbsp;
	
	{if $apply} 
		<div class="interestBox" style="padding-left:100px"><h2>Finish my application</h2>
		
		<form method="post" action="/admin/moderation.php?apply=2">
		<b>Comments:</b> (for example why do you want to become a moderator)<br/>
		<textarea name="comments" rows="8" cols="80"></textarea><br/>
		<input type="submit" value="Finish my application"/>
		</form>
		
		</div>
	{elseif !$moderator && !$remoderate}
		<div class="interestBox" style="padding-left:100px"><a href="/admin/moderation.php">Continue &gt;</a>
		or <a href="/admin/moderation.php?abandon=1">Finish</a> the current moderation session</div>
	{elseif $remoderate}
		<div class="interestBox" style="padding-left:100px"><a href="/admin/moderation.php">Continue &gt;</a></div>
	{/if}
{else}

	<p>There are no images available to moderate at this time!</p>

{/if}
	
    
{/dynamic}    
{include file="_std_end.tpl"}
