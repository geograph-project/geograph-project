{assign var="page_title" value="Moderation"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="/js/jquery.storage.js"></script>
{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);

	function moderateWrapper(gridimage_id, status) {
		//if the cross grid button submit tag (it may of been auto selected) 
		if (document.getElementById('cross'+gridimage_id).className.indexOf('on') > -1)
			submitModTag(gridimage_id,"type:Cross Grid",2);

		if (!status)
			status = getStatus(gridimage_id);

		//for now always submit this, to make sure the tag is created, or removed if change mind
		submitModTag(gridimage_id,"type:Geograph",(status == 'geograph')?2:0);

		moderateImage(gridimage_id, status, function(statusText) {
			$('#block'+gridimage_id).next().removeClass('modDisabled');
		});
	
		if ($('#autoScroll').get(0).checked) {
			var ele = $('#block'+gridimage_id);
			var nxt = ele.next();
			if (nxt.length && nxt.hasClass('photoguide')) {
				//window.scrollBy(0,nxt.height()+22);
				var diff = nxt.find('.modButtons').offset().top - ele.find('.modButtons').offset().top;
				$('html, body').animate({
				    scrollTop: '+='+(diff)
				}, 500);
			}
		}
	}

	$(function(){ 
		if ($.localStorage('admin_autoscroll')) {
			$('#autoScroll').get(0).checked = true;
		}
		$(".photoguide").first().removeClass('modDisabled');
	});

	function autoScrollUpdate() {
		$.localStorage('admin_autoscroll', $('#autoScroll').get(0).checked);
	}

</script>
{/literal}



<div style="float:right; width:350px">
	<input type=checkbox id="autoScroll" onclick="autoScrollUpdate()"> <label for=autoScroll style="font-weight:bold">Auto Scroll</label><sup style=color:red>new!</sup><br>
	<i>(disable the Greasemonkey<br> version if using this)</i>
</div>

<h2>{if $is_admin || $is_mod}<a title="Admin home page" href="/admin/index.php">Admin</a> : {/if}Moderation</h2>

{dynamic}

<br><br>
<div class="interestBox">NOTE: This is the experimental new style moderation screen. For more info see the forum, and <a href="/article/Image-Type-Tags">Image Type Tags</a> Article</div>
	

{$status_message}

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
		<li>Simply look at each image in turn and click the relevant button(s). The result of the action is displayed just below the buttons.</li>
	
		<li><b>Please wait</b> for confirmation after clicking the moderation button, <b>before moving onto the next image</b> (the next image will 'clear' to show this has happened)</li> 
	
		{if !$apply}
			<li>Can now rate images at during moderation. '3' stars is average, and is the same as no vote.</li>
		{/if}
	{/if}

	</ul><br/>
	
	{foreach from=$unmoderated item=image}

	  <div class="photoguide modDisabled" id="block{$image->gridimage_id}" style="font-size:0.85em;text-align:left;width:inherit">

	   {if $image->tags}
             <div style="float:right;font-size:0.7em">
             {foreach from=$image->tags item=tag}
		{$tag|escape:'html'}<br>
             {/foreach}
             </div>
           {/if}
	  
	  <div style="float:left;width:213px">
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
	  </div>
	  
	  <div style="margin-left:233px"> 
	  
	  square: <b><a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></b> ({$image->imagecount} images)<br/>
	  by: <b><a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></b> <span{if $image->images<11} style="background-color:yellow"{/if}>({$image->images} images)</span><br/>
	  title: <b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> (<a href="/editimage.php?id={$image->gridimage_id}">edit</a>)<br/>
	  
	  {if $image->comment}
	  <span style="font-size:0.9em">{$image->comment|escape:'html'|geographlinks}</span><br/>
	  {/if}
	  
	  <br/>
		{if $image->reopenmaptoken}
			<div style="float:left;position:relative"><a href="/submit_popup.php?t={$image->reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;" class="xml-geo">Map</a>&nbsp;</div>
		{/if}
	  <span style="font-family:verdana, arial, sans serif; font-size:0.9em">
	  {if $image->nateastings}
	  	subject: <b>{getamap gridref=$image->getSubjectGridref(true) title="(`$image->subject_gridref_precision`m precision)"}</b>
	  {else}
	  	map: <b>{getamap gridref=$image->grid_reference title="(1000m precision)"}</b>
	  {/if}
	  {if $image->viewpoint_eastings}
	  	| camera: <b>{getamap gridref=$image->getPhotographerGridref(true) title="(`$image->photographer_gridref_precision`m precision)"}</b>{if $image->different_square_true}(diff){/if}
	  	| <span{if $image->different_square} class="interestBox"{/if}>distance: <b><a>{$image->distance}</a></b>km</span>
	  {/if}
	  </span>
	  <br/>
	  
	  <br/>
	  {if $image->sizestr}
	  	<div style="float:right; background-color:red; color:white; border:1px solid pink; padding:6px;">{$image->sizestr}</div>
	  {/if}

	<div class="modButtons">
          {assign var="button" value="Geograph"}
	  <input class="toggle{if $image->different_square_true || in_array('type:Cross Grid',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" id="cross{$image->gridimage_id}" value="Cross Grid" onclick="return false; toggleButton(this)"/>
	  <input class="toggle{if in_array('type:Aerial',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Aerial" id="aerial{$image->gridimage_id}" onclick="toggleButton(this)"/>
	  <input class="toggle{if in_array('type:Inside',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Inside" id="inside{$image->gridimage_id}" onclick="toggleButton(this)"/>
	  <input class="toggle{if in_array('type:Detail',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Detail" id="detail{$image->gridimage_id}" onclick="toggleButton(this)"/>

	  <input class="accept" type="button" id="continue{$image->gridimage_id}" value="{$button}" onclick="moderateWrapper({$image->gridimage_id})" {if $image->user_status == 'rejected'} style="color:gray"{/if}/>
	  <input class="reject" type="button" id="reject{$image->gridimage_id}" value="Reject" onClick="moderateWrapper({$image->gridimage_id}, 'rejected')"/>
	  {if $image->user_status}
	  	(user suggests: {$image->user_status})
	  {/if}

		{if !$apply}
			<span id="votediv{$image->gridimage_id}"> &nbsp; {votestars id=$image->gridimage_id type="mod"}</span>
		{/if}

        </div>

	  {if (!$remoderate && $image->user_status && $image->moderation_status != 'pending') || $moderator || $review}
	  	<br/>Current Classification: {$image->moderation_status} {if $image->mod_realname}, by {$image->mod_realname}{/if}
	  {/if}
	  {if $image->new_status}
	  	<br/><span{if $image->new_status != $image->moderation_status} style="border:1px solid red; padding:5px; line-height:3em;"{/if}>Suggested Classification: {$image->new_status} {if $image->ml_realname}, by {$image->ml_realname}{/if}</span>
	  {/if}
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
	<form name="counter" style="display:none">
		<span style="width:584px; display:inline-block">
		Progress: <input type=text size=2 name="done" value="0" readonly />/
		<input type=text size=2 name="total" value="{$unmoderatedcount}" readonly />
		</span>
		<input id="continueButton" style="display:none" type=button value="Continue &gt;" onclick="window.location.href='/admin/moderation.php'">
	</form>
{else}

	<p>There are no images available to moderate at this time!</p>

{/if}
	
    
{/dynamic}    
<div style="height:500px;"></div>
{include file="_std_end.tpl"}
