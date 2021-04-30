{assign var="page_title" value="Moderation"}
{assign var="extra_meta" value="<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' />"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="{"/js/jquery.storage.js"|revision}"></script>
{literal}
<style>
@media only screen and (max-width: 1024px)  {
	#maincontent_block {
		margin-left:0;
	}
}

#maincontent div.photoguide {
	min-width: 920px;	
	font-size:0.85em;text-align:left;width:inherit;
}
.photoleft {
	float:left;text-align:right;
	max-width:45vw;
	min-width:45vw;
	display:table;
}
.photoleft img {
	max-width:50vw;
	height:inherit;
}
.photoright {
	padding-left:10px;
	display:table-cell;
	text-align:left;
	max-width:45vw;
}
.textleft {
	height:252px;
	overflow:auto;
}
.textleft div {
	margin-top:9px;
	font-size:0.9em;
}
.mapright {
	float:right;
}

@media only screen and (max-width: 1200px)  {
	.mapright {
		overflow:hidden;
	}
	.mapright iframe {
		margin:-42px;
	}
	.textleft {
		height:175px;
	}
}

.modButtons {
	line-height:3em;
}
.votediv {
	background-color:#88aa88;
        padding:3px;
}
.votediv a {
        padding:3px;
}

#popupMsg {
	position: fixed; top: 20vh; bottom: 20vh; left: 20vw; right: 20vw; 
	z-index: 10000;
	background-color: white; padding: 20px; border: 3px solid red;
	display: none;
}
#popupMsg li {
	padding-bottom:10px;
}	
</style>

<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);

	var mapOpened = false;

	var blockList = {};
	
	function unloadMess() {
		var blocked = false;
		for (var key in blockList) {
 			if (blockList.hasOwnProperty(key))
				if (blockList[key] > 0)
					blocked = true;
		}
		if (blocked) {
		        return "Please wait until replies.\n\n**************************\n";
		}
                return;
	}	
	//this is unreliable with AttachEvent
	window.onbeforeunload=unloadMess;

	function moderateWrapper(gridimage_id, status) {
		var gridref = $('#block'+gridimage_id).find('.gridref').text();

		var blocked = false;
		if (typeof blockList[gridref] !== 'undefined') {
			if (blockList[gridref] > 0)
				blocked = true;
			blockList[gridref]++;
		} else {
			blockList[gridref] = 1;
		}
		
		//if the cross grid button submit tag (it may of been auto selected) 
		if (document.getElementById('cross'+gridimage_id).className.indexOf('on') > -1)
			submitModTag(gridimage_id,"type:Cross Grid",2);

		if (!status)
			status = getStatus(gridimage_id);

		if (status != 'geograph')
			blocked = false;

		//for now always submit this, to make sure the tag is created, or removed if change mind
		submitModTag(gridimage_id,"type:Geograph",(status == 'geograph')?2:0);

		//submit the status
		moderateImage(gridimage_id, status, function(statusText) {
			if (blocked) {//if blocked, need wait until reply before moving on!
				highlightNext(gridimage_id);
			}
			blockList[gridref]--;
		});

		if (!blocked) {//if not blocked, then can move on right away!
			highlightNext(gridimage_id);
		}

		//reopen the map
		if (mapOpened) {
		   $('#block'+gridimage_id).next().find('a.xml-geo').trigger('click');
		}

		//and start the one after loading too!
		$('#block'+gridimage_id).next().next().find('iframe').each(function() {
                        var url = $(this).data('src');
                        if (!this.src || this.src.indexOf(url) == -1) this.src = url;
                });
	}


	function highlightNext(gridimage_id) {
		$('#block'+gridimage_id).next().removeClass('modDisabled').find('iframe').each(function() {
			var url = $(this).data('src');
			if (!this.src || this.src.indexOf(url) == -1) this.src = url;
		});

		if ($('#autoScroll').get(0).checked) {
                	setTimeout(function() {
				var ele = $('#block'+gridimage_id);
				var nxt = ele.next();
				if (nxt.length && nxt.hasClass('photoguide')) {
					//window.scrollBy(0,nxt.height()+22);
					var diff = nxt.find('.modButtons').offset().top - ele.find('.modButtons').offset().top;
					$('html, body').animate({
					    scrollTop: '+='+(diff)
					}, 300);
				}
               		}, 120);
		}
	}

	$(function(){ 
		if ($.localStorage('admin_autoscroll_not')) {
			$('#autoScroll').get(0).checked = false;
		}
		$(".photoguide").first().removeClass('modDisabled').find('iframe').each(function() {
                        var url = $(this).data('src');
                        if (this.src != url) this.src = url;
                });
		if (!$.localStorage('admin_mod_message')) {
			$('#popupMsg').show('fast');
		}

		//setup auto clicking of the moderation button on stars 
		$('span.votediv a').click(function() {
			var href = $(this).attr('href');
			if (m = href.match(/\,(\d+)\,/)) {
				var gridimage_id = m[1];
				moderateWrapper(m[1]);
			}
		});

	});

	function mapILink(gridimage_id) {
		$("#block"+gridimage_id+" iframe").each(function() {
                        var url = $(this).data('src');
			if (url) this.src = url+'&i=1';
                });
		$("#block"+gridimage_id+" .mapright a").remove();
		return false;
	}

	function autoScrollUpdate() {
		$.localStorage('admin_autoscroll_not', !$('#autoScroll').get(0).checked);
	}

	function hideMsg() {
		$('#popupMsg').hide('fast');
		$.localStorage('admin_mod_message',1);
		return false;
	}

</script>
{/literal}



<div style="float:right; width:350px">
	<input type=checkbox id="autoScroll" checked onclick="autoScrollUpdate()"> <label for=autoScroll style="font-weight:bold">Auto Scroll</label> <sup style=color:red>new!</sup><br>
</div>

<h2>{if $is_admin || $is_mod}<a title="Admin home page" href="/admin/index.php">Admin</a> : {/if}Moderation</h2>

{dynamic}

{$status_message}

{if $remoderate}{literal}
<script type="text/javascript">
	remoderate = true;
</script>
{/literal}{/if}

{if $unmoderatedcount}

	{if !$second}

	<div id="popupMsg">
		<h2>Important Message</h2>
		<p>The way this page works has changed!</p>
		<ul>
			<li>No longer need to wait for the 'reply' to the previous moderation before moving on to next. 
			<b>Can move (use auto-scroll to do it automatically!) on as soon as the next image 'ungrays'.</b> Moving on is only 'paused' for the reply if a point is at stake. 

			<li>Still should progress down the page in order, don't skip, or do them out of sequence. 

			<li>However, <b>still need to wait for all the replies to come back before moving to another page</b>, including using 'Continue'. 
			Use the counter at the bottom to confirm, the continue button also only appears once all replies received. 

			<li>If a update is still 'in progress' when try to navigate away, will get a warning message. Please cancel to let the request process, dont 'Leave' anyway. 

			<li>Can still 'Finish' early, again make sure any inflight requests are done before doing so. 
		</ul>
		<a href=# onclick="return hideMsg();">Dismiss</a>
	</div>


	<ul>

	{if $apply}
		<li>To get a feel for the moderation process, please make your suggestions for the images below. This is a dummy run, no actual moderations are taking place. Any change requests are created as normal. {if !$is_mod}Make sure you click the 'Finish my application' when finished!{/if}</li>
	{elseif $review}
		<li>The following images have been recently moderated to be different to the status you previously selected, there is no need to change anything.</li>
	{elseif $moderator}
		<li>The following images have been recently moderated by the selected moderator. There is no need to do anything, but if you believe the original moderation was wrong just use the moderation buttons as normal.</li>
	{elseif $remoderate}
		<li><b>As a quick spotcheck you are asked to make a suggestion for these recently moderated images.</b></li>
	{else}
		<li>The following images have been submitted recently.</li>
	{/if}

	<li>For more on the Image Types, see the forum, and/or <a href="/article/Image-Type-Tags-update">Image Type Tags</a> Article.</li>
 
	{if !$moderator && !$review}
		<li>Simply look at each image in turn and click the relevant button(s). The result of the action is displayed just below the buttons.</li>
	
		<li><b>Please wait</b> for confirmation after clicking the moderation button, <b>before moving onto the next image</b>. (the next image will 'clear' to show this has happened)</li> 
	
	{/if}
	{if !$apply || $is_mod}
		<li>Can now rate images at during moderation. '3' stars is average, and is the same as no vote. (<a href="/help/voting">read more</a>)</li>
	{/if}

	</ul>
	{/if}	


	{foreach from=$unmoderated item=image}

	  <div class="photoguide modDisabled" id="block{$image->gridimage_id}">

	  <div class="photoleft">
		  <a href="/photo/{$image->gridimage_id}">{$image->getFull()}</a>
	  </div>
	  
	  <div class="photoright"> 

		<div class=mapright>
			<iframe data-src="/map_frame.php?id={$image->gridimage_id}&amp;hash={$image->_getAntiLeechHash()}" width=252 height=252 frameborder=0 scrolling="no"></iframe>
			{if $image->grid_square->reference_index == 1}
				<br><a href=# onclick="return mapILink({$image->gridimage_id})">change to interactive map</a>
			{/if}
		</div>

		<div class=textleft>
			square: <b><a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}" class="gridref">{$image->grid_reference}</a></b> ({$image->imagecount} images)<br/><br/>
			by: <b><a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></b> <span{if $image->images<11} style="background-color:yellow"{/if}>({$image->images} images)</span><br/><br/>
			<b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> (<a href="/editimage.php?id={$image->gridimage_id}">edit</a>)
	  
			{if $image->comment}
				<div>
				{$image->comment|escape:'html'|geographlinks}
				</div>
			{/if}
		</div>
	  
		<div style="font-family:verdana, arial, sans serif; font-size:0.9em; clear:right" class="interestBox">
		  <span class=nowrap>{if $image->nateastings}
		  	subj: <b>{getamap gridref=$image->getSubjectGridref(true) title="(`$image->subject_gridref_precision`m precision)"}</b>
		  {else}
		  	map: <b>{getamap gridref=$image->grid_reference title="(1000m precision)"}</b>
		  {/if}</span>
		  {if $image->viewpoint_eastings}
	  		| <span class=nowrap>cam: <b>{getamap gridref=$image->getPhotographerGridref(true) title="(`$image->photographer_gridref_precision`m precision)"}</b>{if $image->different_square_true}(diff){/if}</span>
		  	| <span class="nowrap{if $image->different_square} interestBox{/if}">dist: <b><a>{$image->distance}</a></b>km</span>
		  {/if}
		</div>
		<br/>

		<div class="modButtons">
        	  {assign var="button" value="Geograph"}
		  <input class="toggle{if !$remoderate && in_array('type:Close Look',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Close Look" id="close{$image->gridimage_id}" onclick="toggleButton(this)"/>
		  <input class="toggle{if !$remoderate && in_array('type:Inside',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Inside" id="inside{$image->gridimage_id}" onclick="toggleButton(this)"/>
		  <input class="toggle{if !$remoderate && in_array('type:Extra',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Extra" id="extra{$image->gridimage_id}" onclick="toggleButton(this)"/>
		  <input class="toggle{if !$remoderate && in_array('type:Aerial',$image->tags)} on{assign var="button" value="Accept"}{/if}" type="button" value="Aerial" id="aerial{$image->gridimage_id}" onclick="toggleButton(this)"/>
		  <input class="toggle{if $image->different_square_true || (!$remoderate && in_array('type:Cross Grid',$image->tags))} on{assign var="button" value="Accept"}{/if}" type="button" id="cross{$image->gridimage_id}" value="Cross Grid" onclick="return false; toggleButton(this)"/>
		  <br/>

		  <input class="accept" type="button" id="continue{$image->gridimage_id}" value="{$button}" onclick="moderateWrapper({$image->gridimage_id})" {if $image->user_status == 'rejected'} style="color:gray"{/if}
			/>{if !$apply || $is_mod}<span class=votediv id="votediv{$image->gridimage_id}">{votestars id=$image->gridimage_id type="mod"}</span>{/if}

		  &nbsp;
		  <input class="reject" type="button" id="reject{$image->gridimage_id}" value="Reject" onClick="moderateWrapper({$image->gridimage_id}, 'rejected')" {if $image->user_status == 'rejected'} style="border-bottom:2px solid black"{/if}/>
		  {if $image->user_status}
			<span style=background-color:yellow;color:red;padding:5px">(user suggests: {$image->user_status})</span>
		  {/if}
        	</div>
		<br/>

		{if $image->sizestr}
	  		<div style="background-color:red; color:white; border:1px solid pink; padding:6px;">{$image->sizestr}</div>
		{/if}

		{if (!$remoderate && $image->user_status && $image->moderation_status != 'pending') || $moderator || $review}
	  		<br/>Current Classification: {$image->moderation_status} {if $image->mod_realname}, by {$image->mod_realname}{/if}
		{/if}
		{if $image->new_status}
	  		<br/><span{if $image->new_status != $image->moderation_status} style="border:1px solid red; padding:5px; line-height:3em;"{/if}>Suggested Classification: {$image->new_status} {if $image->ml_realname}, by {$image->ml_realname}{/if}, {$image->ml_created}</span>
		{/if}

		<div id="modinfo{$image->gridimage_id}">&nbsp;</div>

	  </div>

	  <br style=clear:both>	  
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
		<div class="interestBox" style="padding-left:100px">
			<a href="/admin/moderation.php?abandon=1">Finish</a> the current moderation session early. 
			(Continue button will appear once all replies come back!)</div>
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
	<div id="skipDiv">
		<input type=button value="Skip Rest &gt;" onclick="window.location.href='/admin/moderation.php?skip=1'">
		(Use to goto next page, without moderating all above images. The unmoderated images will be shown to another moderator, and you can continue moderating more images)
	</div>
{else}

	<p>There are no images available to moderate at this time!</p>

{/if}
	
    
{/dynamic}    
<div style="height:500px;"></div>
{include file="_std_end.tpl"}
