{assign var="page_title" value="Update Image Details"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->current_title|escape:'html'}</h2>

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>This image is currently open by {$locked_by_moderator}</b>, please come back later.
	</p>
{/if}

{if $error}
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}


<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  {if $thumb}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=0" style="font-size:0.6em">Switch to full Image</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a></div>
  {else}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" style="font-size:0.6em">Switch to thumbnail Image</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getFull()}</a></div>
  {/if}
  <div class="caption"><b>{$image->current_title|escape:'html'}</b> by <a href="{$image->profile_link}">{$image->realname}</a>{if $isowner} (<a href="/licence.php?id={$image->gridimage_id}">change credit</a>){/if}</div>
  
  {if $image->comment}
  <div class="caption" style="border:1px dotted lightgrey;">{$image->current_comment|escape:'html'|geographlinks}</div>
  {/if}
  <div class="statuscaption">classification:
   {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}
   {if $image->mod_realname}(moderator: <a href="/profile/{$image->moderator_id}">{$image->mod_realname}</a>){/if}</div>
</div>
{if $showfull}
  	{if $isowner and $image->moderation_status eq 'pending'}
  	  <form action="/moderation.php" method="post">
  	  <input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
  	  <h2 class="titlebar">Moderation Suggestion</h2>
  	  <p>I suggest this image should become:
  	  {if $image->user_status}
  	  <input class="accept" type="submit" id="geograph" name="user_status" value="Geograph"/>
  	  {/if}
  	  {if $image->user_status != 'accepted'}
  	  <input class="accept" type="submit" id="accept" name="user_status" value="Supplemental"/>
  	  {/if}
  	  {if $image->user_status != 'rejected'}
  	  <input class="reject" type="submit" id="reject" name="user_status" value="Reject"/>
  	  {/if}
  	  {if $image->user_status}
	  <br/><small>[Current suggestion: {if $image->user_status eq "accepted"}supplemental{else}{$image->user_status}{/if}</small>]
	  {/if}</p>
  	  <p style="font-size:0.8em">(Click one of these buttons to leave a hint to the moderator when they moderate your image)</p>
  	  </form>
  	{elseif $isadmin and $image->user_status}
  	  <h2 class="titlebar">Moderation Suggestion</h2>
  	   Suggestion: {if $image->user_status eq "accepted"}supplemental{else}{$image->user_status}{/if}
	{/if}
<br/>
<br/>
  {if $isadmin && $is_mod}
	  <form method="post">
	  <script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>
	  <h2 class="titlebar">Moderation</h2>
	  <p><input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Supp" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <span class="caption" id="modinfo{$image->gridimage_id}">Current Classification: {$image->moderation_status}{if $image->mod_realname}<abbr title="Approximate date of last moderation: {$image->moderated|date_format:"%a, %e %b %Y"}"><small><small>, by <a href="/usermsg.php?to={$image->moderator_id}&amp;image={$image->gridimage_id}">{$image->mod_realname}</a></small></small></abbr>{/if}</span></p>
	  </form>
  {/if}


{if $thankyou eq 'pending'}
	<a name="form"></a>
	<h2>Thankyou!</h2>
	<p>Thanks for suggesting changes, you will receive an email when 
	we process your suggestion. </p>

	<p>You can review your requested changes below, or <a href="/photo/{$image->gridimage_id}">click here to return to the photo page</a></p>
{/if}

{if $thankyou eq 'comment'}
	<a name="form"></a>
	<h2>Thankyou!</h2>
	<p>Thanks for commenting on the change request, the moderators have been notified.</p>

	<p>You can review outstanding change requests below, or <a href="/photo/{$image->gridimage_id}">click here to return to the photo page</a></p>
{/if}


{if $show_all_tickets eq 1}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/tickets.php" title="Ticket Admin Listing">&lt;&lt;&lt;</a>{/if}
	All Change Requests
	{if $isowner}<small>(<a href="/tickets.php" title="Ticket Listing">back to listing</a>)</small>{/if}
	</h2>
	
	{if $opentickets}	
	<p>All change requests for this image are listed below. 
	<a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=0">Just show open requests.</a></p>
	{else}
	<p>There have been no change requests logged for this image</p>

	{/if}
{else}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/tickets.php" title="Ticket Admin Listing">&lt;&lt;&lt;</a>{/if}
	Open Change Requests
	{if $isowner}<small>(<a href="/tickets.php" title="Ticket Listing">back to listing</a>)</small>{/if}
	</h2>
	{if $opentickets}	
	<p>Any open change requests are listed below. 
	{else}

	<p>There are no open change requests for this image. 
	{/if}
	To see older, closed requests, <a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=1">view all requests</a></p>
{/if}

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>This image is currently open by {$locked_by_moderator}</b>, please come back later.
	</p>
{/if}

{if $opentickets}

{foreach from=$opentickets item=ticket}
<form action="/editimage.php" method="post" name="ticket{$ticket->gridimage_ticket_id}">
<input type="hidden" name="gridimage_ticket_id" value="{$ticket->gridimage_ticket_id}"/>
<input type="hidden" name="id" value="{$ticket->gridimage_id}"/>

{if $lastdays ne $ticket->days}
<b>-updated {$ticket->days} ago-</b>
{/if}
{assign var="lastdays" value=$ticket->days} 
<div class="ticket">
	

	<div class="ticketbasics">
	{if $ticket->type == 'minor'}
		<u>Minor Changes</u>, 
	{/if}
	{if $isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner') }
		<b>Submitted</b> {if $ticket->public ne 'everyone'}anonymously{/if}{if $ticket->public eq 'owner'}(to everyone else){/if} by {$ticket->suggester_name} 
		
		{if $ticket->user_id eq $image->user_id}
		  <b>(photo owner)</b>
		{/if}
	{elseif $ticket->user_id eq $image->user_id}
		Submitted <b>by photo owner</b>
	{elseif $ticket->user_id eq $user->user_id}
		<b>You</b> Submitted
	{else}
		<b>Submitted</b> by anonymous site visitor 
	{/if} 
	<b>on</b> {$ticket->suggested|date_format:"%a, %e %b %Y at %H:%M"} |

	{if $ticket->suggested ne $ticket->updated}

	<b>Updated</b> {$ticket->updated|date_format:"%a, %e %b %Y at %H:%M"} | 
	{/if}

	
	<i>({$ticket->status})</i>
	
	{if $ticket->status ne "closed" && $isadmin && $ticket->moderator_id == $user->user_id}

		<input type="submit" name="disown" id="disown" value="Disown"/>

	{/if}
	</div>
	

	

	{if $ticket->changes}

		<div class="ticketfields" style="padding-bottom:3px;margin-bottom:3px;border-bottom:1px solid gray">
		{foreach from=$ticket->changes item=item}
			<div>
			{assign var="editable" value=0}
			{if ($ticket->status eq "closed") or ($item.status eq 'immediate')}
				<input disabled="disabled" type="checkbox" {if ($item.status eq 'immediate') or ($item.status eq 'approved')}checked="checked"{/if}/>
				
			{else}
				{if $isadmin}
				<input type="checkbox" value="1" id="accept{$item.gridimage_ticket_item_id}" name="accepted[{$item.gridimage_ticket_item_id}]"/>
				{assign var="editable" value=1}
				{/if}
			{/if}
			<label for="accept{$item.gridimage_ticket_item_id}">
			Change {$item.field} from

			{if $item.field eq "grid_reference"}
				{assign var="field" value="current_subject_gridref"}
			{else}
				{assign var="field" value="current_`$item.field`"}
			{/if}

			{if $item.field eq "grid_reference" || $item.field eq "photographer_gridref"}

				<!--<span{if $editable && $item.oldvalue != $image->$field} style="text-decoration: line-through"{/if}>
					{getamap gridref=$item.oldvalue|default:'blank'}
				</span>
				to
				{getamap gridref=$item.newvalue|default:'blank'}-->
				<span{if $editable && $item.oldvalue != $image->$field} style="text-decoration: line-through"{/if}>
					{$item.oldvalue|escape:'html'|default:'blank'}
				</span>
				to
				{$item.newvalue|escape:'html'|default:'blank'}

			{elseif $item.field eq "comment1" || $item.field eq "comment2"}
			  <br/>
			  <span style="border:1px solid #dddddd{if $editable && $item.oldvalue != $image->$field}; text-decoration: line-through{/if}">{$item.oldvalue|escape:'html'|default:'blank'}</span><br/>
			  to<br/>
			  <span style="border:1px solid #dddddd">{$item.newvalue|escape:'html'|default:'blank'}</span>
			{else}
			  <span style="border:1px solid #dddddd{if $editable && $item.oldvalue != $image->$field}; text-decoration: line-through{/if}">{$item.oldvalue|escape:'html'|default:'blank'}</span>
			  to 
			  <span style="border:1px solid #dddddd">{$item.newvalue|escape:'html'|default:'blank'}</span>
			{/if}

			{if $editable && $item.newvalue == $image->$field}
				<b>Changes already applied</b>
			{/if}

			</label>
			
			</div>
		{/foreach}
		{if $ticket->reopenmaptoken}
			<div style="text-align:right"><a href="/submit_popup.php?t={$ticket->reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Open Map for these <i>new</i> values</a>&nbsp;&nbsp;&nbsp;</div>
		{/if}
		</div>
	{/if}
	
	{if ($isadmin or $isowner or ($ticket->user_id eq $user->user_id and $ticket->notify=='suggestor') )}
	<div class="ticketnotes">
		<div class="ticketnote">{$ticket->notes|escape:'html'|geographlinks|replace:'Auto-generated ticket, as a result of Moderation. Rejecting this image because:':'<span style="color:gray">Auto-generated ticket, as a result of Moderation. Rejecting this image because:</span><br/>'}</div>
	
		
		{if $ticket->comments}
			{if $isadmin or $isowner or ($user->user_id eq $ticket->user_id && $ticket->notify eq 'suggestor')}
				{foreach from=$ticket->comments item=comment}
				<div class="ticketnote">
					<div class="ticketnotehdr">
					{if $comment.user_id ne $ticket->user_id or ($isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner')) }
						{$comment.realname}
					{else}
						ticket suggestor
					{/if} 
					{if $comment.user_id == $image->user_id}
						(Photo Owner)
					{elseif $comment.moderator}
						(Moderator)
					{/if}
					wrote on {$comment.added|date_format:"%a, %e %b %Y at %H:%M"}</div>
					{$comment.comment|escape:'html'|geographlinks}

				</div>
				{/foreach}
			{else}
				{if ($user->user_id eq $ticket->user_id) and ($ticket->status eq "closed") && $ticket->lastcomment.moderator}
				<div class="ticketnote">
					<div class="ticketnotehdr">{$ticket->lastcomment.realname} {if $ticket->lastcomment.moderator}(Moderator){/if} wrote on {$ticket->lastcomment.added|date_format:"%a, %e %b %Y at %H:%M"}</div>
					{$ticket->lastcomment.comment|escape:'html'|geographlinks}

				</div>
				{/if}
			{/if}
		{/if}
		
	

	</div>
	{/if}
	
	{if ($isadmin or $isowner or ($ticket->user_id eq $user->user_id and $ticket->notify=='suggestor') ) and ($ticket->status ne "closed")}
		{assign var="ticketsforcomments" value=1}
	<div class="ticketactions interestBox">
		<div>&nbsp;<b>Add a reply to this ticket:</b></div>
		<textarea name="comment" rows="4" cols="70"></textarea><br/>
		
		<input type="submit" name="addcomment" value="Add comment"/>
		
		{if $isadmin and $ticket->moderator_id > 0 and $ticket->moderator_id != $user->user_id}
			<input type="checkbox" name="claim" value="on" id="claim" checked="checked"/> <label for="claim" title="Claim this ticket to be moderated by me">Claim Ticket</label>
			&nbsp;&nbsp;&nbsp;
		{elseif $isadmin}
			<input type="hidden" name="claim" value="on"/>
		{/if}
		
		{if ($isowner || $isadmin) && $ticket->user_id ne $user->user_id}
			<input type="checkbox" name="notify" value="suggestor" id="notify_suggestor" {if $ticket->notify=='suggestor'}checked="checked"{/if}/> <label for="notify_suggestor">Send {if $isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner') }{$ticket->suggester_name}{else}ticket suggestor{/if} this comment.</label>
			&nbsp;&nbsp;&nbsp;
		{/if}
		{if $isadmin}
		
			{if $ticket->changes}
		
			<input type="submit" name="accept" value="Accept ticked changes and close ticket" onclick="autoDisable(this)"/>

			{else}

			<input type="submit" name="close" value="Close ticket" onclick="autoDisable(this)"/>

			{/if} {$ticket->suggester_name} is notified.
			
			<input class="accept" type="button" id="defer" value="Defer 24 hours" onclick="deferTicket({$ticket->gridimage_ticket_id},24)"/>
	 		<input class="accept" type="button" id="defer" value="Defer 7 days" onclick="deferTicket({$ticket->gridimage_ticket_id},168)"/>
	 		<span class="caption" id="modinfo{$ticket->gridimage_ticket_id}"></span>
		{/if}
		
	</div>
	{/if}
	

</div>
</form>
{/foreach}


{/if}


<br/>
<br/>
{if !($opentickets && !$error && $isowner && $ticketsforcomments)}
<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=1" style="font-size:0.6em">Switch to Simple Edit Page</a>
{/if}
{else}
<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=0" style="font-size:0.6em">Switch to Full Edit Page</a>
{/if}

{if $opentickets && !$error && $isowner && $ticketsforcomments && $showfull}
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<ul>
		<li>If you agree with the changes suggested, please indicate your acceptance, <b>in the reply box above</b>.</li> 
		<li>If you disagree, please explain above why you do not accept the changes. This will be helpful to the Moderator in making a decision.</li>
		<li>However, if you want to make the changes straight away {if $moderated.grid_reference}<span class="moderatedlabel">(except gridsquare changes)</span>{/if}, or want to make other changes, use the <b><a href="/editimage.php?id={$image->gridimage_id}&amp;form">Change Image Details</a> Form</b>.</li>
		<li>If a ticket suggests an issue but doesn't actually list the changes then it would help us if you were to make the changes yourself</li>
	</ul>
</div>
<br>


{else}

<h2 class="titlebar" style="margin-bottom:0px">Report Problem / Change Image Details <small><a href="/help/changes">[help]</a></small></h2>
{if $error}
<a name="form"></a>
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}

	{if $rastermap->enabled}
		<div class="rastermap" style="float:right;  width:350px;position:relative">
		
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		 
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					{/literal}{if $image->view_direction == -1}
						updateViewDirection();
					{/if}{literal}

				}
				AttachEvent(window,'load',updateMapMarkers,false);
				AttachEvent(window,'load',onChangeImageclass,false);
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
	
 		


<form method="post" action="/editimage.php#form" name="theForm" onsubmit="this.imageclass.disabled=false" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0; border-top:none">
<input type="hidden" name="id" value="{$image->gridimage_id}"/>

{if $moderated_count}

<div>
	{if $all_moderated}
		Any changes you suggest are moderated and will first be approved by
		a moderator before going live. You will receive an email when this happens.
	{else}
		If you change any fields labelled as "moderated" your changes will first be approved by
		a moderator before going live. You will receive an email when this happens.
	{/if}
</div>
{/if}

  <div style="float:right;  position:relative">
  <a title="Open in Google Earth" href="/photo/{$image->gridimage_id}.kml" class="xml-kml">KML</a></div>

<p>
<label for="grid_reference"><b style="color:#0018F8">Subject Grid Reference</b> {if $moderated.grid_reference}<span class="moderatedlabel">(moderated{if $isowner} for gridsquare changes{/if})</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="14" value="{$image->subject_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks the Subject" width="20" height="34" align="middle"/>{/if}
<!--{getamap gridref="document.theForm.grid_reference.value" gridref2=$image->subject_gridref text="OS Get-a-map&trade;"}-->


<p>
<label for="photographer_gridref"><b style="color:#002E73">Photographer Grid Reference</b> - Optional {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="14" value="{$image->photographer_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks the Photographer" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Marks the Photographer" width="12" height="20" align="middle"/>{/if}
<!--{getamap gridref="document.theForm.photographer_gridref.value" gridref2=$image->photographer_gridref text="OS Get-a-map&trade;"}--><br/>
<span style="font-size:0.6em">
| <a href="javascript:void(copyGridRef());">Copy from Subject</a> | 
<a href="javascript:void(resetGridRefs());">Reset to initial values</a> |<br/></span>

	{literal}
	<script type="text/javascript">
		function copyGridRef() {
			document.theForm.photographer_gridref.value = document.theForm.grid_reference.value;
			updateMapMarker(document.theForm.photographer_gridref,false);
			return false;
		}
		function resetGridRefs() {
			document.theForm.grid_reference.value = document.theForm.grid_reference.defaultValue;
			document.theForm.photographer_gridref.value = document.theForm.photographer_gridref.defaultValue;
			updateMapMarker(document.theForm.grid_reference,false);
			updateMapMarker(document.theForm.photographer_gridref,false);
			var ele = document.theForm.view_direction;
			for (var q=0;q<ele.options.length;q++) {
				if (ele.options[q].defaultSelected)
					ele.options[q].selected = true;
			}
			if (document.theForm.use6fig)
				document.theForm.use6fig.checked = document.theForm.use6fig.defaultChecked;
			return false;
		}
	</script>
	{/literal}


	<br/><input type="checkbox" name="use6fig" id="use6fig" {if $image->use6fig} checked="checked"{/if} value="1"/> <label for="use6fig">Only display 6 figure grid reference ({newwin href="/help/map_precision" text="Explanation"})</label>
</p>


<p><label for="view_direction"><b>View Direction</b>  {if $moderated.view_direction}<span class="moderatedlabel">(moderated)</span>{/if}
</label> <small>(photographer facing)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
	{foreach from=$dirs key=key item=value}
		<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$image->view_direction} selected="selected"{/if}>{$value}</option>
	{/foreach}
</select></p>

<span id="styleguidelink">({newwin href="/help/style" text="Open Style Guide"})</span>

<p><label for="title"><b>Title</b> {if $moderated.title}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
 <span class="formerror" style="display:none" id="titlestyle">Possible style issue. See Guide above. <span id="titlestylet" style="font-size:0.9em"></span><br/></span>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title1|escape:'html'}" title="Original: {$image->current_title1|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);" maxlength="128"/>
</p>
<p><label for="title2"><b>Non-English Title</b> (optional) {if $moderated.title2}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
 <span class="formerror" style="display:none" id="title2style">Possible style issue. See Guide above. <span id="title2stylet" style="font-size:0.9em"></span><br/></span>
{if $error.title2}<span class="formerror">{$error.title2}</span><br/>{/if}
<input type="text" id="title2" name="title2" size="50" value="{$image->title2|escape:'html'}" title="Original: {$image->current_title2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title2',true);" onkeyup="checkstyle(this,'title2',false);" maxlength="128"/>
</p>


{if !$rastermap->enabled}
{literal}
<script type="text/javascript">

//rest loaded in geograph.js
AttachEvent(window,'load',onChangeImageclass,false);

</script>
{/literal}
{/if}
<p><label for="imageclass"><b>Image Category</b> {if $moderated.imageclass}<span class="moderatedlabel">(moderated)</span>{/if}</label><br />	
	{if $error.imageclass}
	<span class="formerror">{$error.imageclass}</span><br/>
	{/if}
	
	{if $error.imageclassother}
	<span class="formerror">{$error.imageclassother}</span><br/>
	{/if}
	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onmouseover="prePopulateImageclass()" disabled="disabled">
		<option value="">--please select feature--</option>
		{if $image->imageclass}
			<option value="{$image->imageclass}" selected="selected">{$image->imageclass}</option>
		{/if}
		<option value="Other">Other...</option>
	</select><input type="button" name="imageclass_enable_button" value="change" onclick="prePopulateImageclass()"/>
	
	
	<span id="otherblock"><br/>
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/></p>
	</span>
</p>	

{if $user->user_id eq $image->user_id || $isadmin}
	<p><label><b>Date picture taken</b> {if $moderated.imagetaken}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>
{else}
	<p><label><b>Date picture taken</b></label> <span class="moderatedlabel">(only changeable by owner)</span><br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m" all_extra="disabled"}</p>
{/if}


<p><label for="comment"><b>Description</b> {if $moderated.comment}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
 <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span><br/></span>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="7" cols="80" title="Original: {$image->current_comment1|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$image->comment1|escape:'html'}</textarea>
</p>
<p>
<label for="comment2"><b>Non-English Description</b> (optional) {if $moderated.comment2}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
 <span class="formerror" style="display:none" id="comment2style">Possible style issue. See Guide above. <span id="comment2stylet"></span><br/></span>
{if $error.comment2}<span class="formerror">{$error.comment2}</span><br/>{/if}
<textarea id="comment2" name="comment2" rows="7" cols="80" title="Original: {$image->current_comment2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'comment2',true);" onkeyup="checkstyle(this,'comment2',false);">{$image->comment2|escape:'html'}</textarea>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>
</p>

<br/>
<div class="interestBox">
<p>
<label for="updatenote">&nbsp;<b>Please describe what's wrong or briefly why you have made the changes above...</b></label><br/>

{if $error.updatenote}<br/><span class="formerror">{$error.updatenote}</span><br/>{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="5" cols="60"{if $user->message_sig} onfocus="if (this.value=='') {literal}{{/literal}this.value='{$user->message_sig|escape:'javascript'}';setCaretTo(this,0); {literal}}{/literal}"{/if}>{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
	Please provide as much detail for the moderator 
	{if !$isowner} and photo owner{/if} about this suggestion as possible. 
	Explaining the reasoning behind the suggestion will greatly help everyone in dealing with this ticket. 
</div>

</td></tr></table>

<div>
<input type="checkbox" name="type" value="minor" id="type_minor"/> <label for="type_minor">I certify that this change is minor, e.g. only spelling and grammar.</label>
</div>

<br style="clear:both"/>

{if $isadmin}
	<div>
	<input type="radio" name="mod" value="" id="mod_blank" checked="checked"/> <label for="mod_blank">Create a new ticket to be moderated by someone else.</label><br/>
	<input type="radio" name="mod" value="assign" id="mod_assign"/> <label for="mod_assign">Create an open ticket and assign to myself. (give the Contributor a chance to respond)</label><br/>
	<input type="radio" name="mod" value="apply" id="mod_apply"/> <label for="mod_apply">Apply the changes immediately, and close the ticket. (Contributor is notified)</label></div>

	<br style="clear:both"/>
{else}
	{if $isowner} 
	<div>
		<input type="checkbox" name="mod" value="pending" id="mod_pending"{if $mod_pending} checked="checked"{/if}/> <label for="mod_pending">Bring this issue to the attention of a moderator (regardless of changes made).</label><br/><br/>
	</div>
	{/if}
{/if}

<input type="submit" name="save" value="Submit Changes" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>

{if !$isowner && !$isadmin}
&nbsp;	<select name="public">
		<option value="no">Do not disclose my name</option>
		<option value="owner" {if $user->ticket_public eq 'owner'} selected{/if}>Show my name to the photo owner</option>
		<option value="everyone" {if $user->ticket_public eq 'everyone'} selected{/if}>Show my name against the ticket</option>
	</select>
{/if}
</div>
</form>

{/if}

<script type="text/javascript" src="/categories.js.php"></script>
{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{literal}
	<script type="text/javascript">
	
	function releaseLock() {
		var myImage = new Image();
		myImage.src = "/editimage.php?id={/literal}{$image->gridimage_id}{literal}&unlock";
	}
	AttachEvent(window,'unload',releaseLock,false);
	</script>
{/literal}
{else}
	<h2>Sorry, image not available</h2>

	<p>{$error}</p>

	<p>Please <a title="Contact Us" href="/contact.php">contact us</a> 
	if you have queries</p>
{/if}

{include file="_std_end.tpl"}
{/dynamic}
