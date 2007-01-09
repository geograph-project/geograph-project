{assign var="page_title" value="Update Image Details"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->current_title}</h2>
 
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
  <div class="caption"><b>{$image->current_title|escape:'html'}</b> by {$image->realname}</div>
  
  {if $image->comment}
  <div class="caption">{$image->current_comment|escape:'html'|geographlinks}</div>
  {/if}
  {if $isadmin || ($user->user_id eq $image->user_id)}
  <div class="statuscaption">status:
   {if $image->ftf}first{/if}
   {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
  {/if}
</div>
  	{if ($user->user_id eq $image->user_id) and $image->moderation_status != 'rejected'}
  	  <form action="/moderation.php" method="post">
  	  <input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
  	  <h2 class="titlebar">Image Self Moderation</h2>
  	  <p>I suggest this image should become:
  	  {if ($image->moderation_status == 'pending' || $image->moderation_status == 'geograph') && $image->user_status == 'accepted'}
  	  <input class="accept" type="submit" id="geograph" name="user_status" value="Geograph"/>
  	  {/if}
  	  {if $image->moderation_status != 'accepted' && $image->user_status != 'accepted'}
  	  <input class="accept" type="submit" id="accept" name="user_status" value="Supplemental"/>
  	  {/if}
  	  {if $image->moderation_status == 'pending' && $image->user_status != 'rejected'}
  	  <input class="reject" type="submit" id="reject" name="user_status" value="Reject"/>
  	  {/if}
  	  {if $image->user_status}
	  <br/><small>[Current suggestion: {if $image->user_status eq "accepted"}supplemental{else}{$image->user_status}{/if}</small>]
	  {/if}</p>
  	  
  	  </form>
	{/if}
<br/>
<br/>
  {if $isadmin}
	  <form method="post">
	  <script type="text/javascript" src="/admin/moderation.js"></script>
	  <h2 class="titlebar">Moderation</h2>
	  <p><input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <span class="caption" id="modinfo{$image->gridimage_id}">Current Status: {$image->moderation_status} {if $image->mod_realname}, by {$image->mod_realname}{/if}</span></p>
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

<div class="ticket">
	

	<div class="ticketbasics">
	{if $ticket->type == 'minor'}
		<u>Minor Changes</u>, 
	{/if}
	{if $isadmin}
		Submitted by {$ticket->suggester_name} 
		
		{if $ticket->user_id eq $image->user_id}
		  (photo owner)
		{/if}
		 
	{/if} 
	

	{if $ticket->suggested ne $ticket->updated}

	Updated {$ticket->updated|date_format:"%a, %e %b %Y at %H:%M"} | 
	{/if}

	Suggested {$ticket->suggested|date_format:"%a, %e %b %Y at %H:%M"} 
	

	</div>
	

	

	{if $ticket->changes}

		<div class="ticketfields">
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
				{assign var="field" value="subject_gridref"}
			{else}
				{assign var="field" value=$item.field}
			{/if}

			{if $item.field eq "grid_reference" || $item.field eq "photographer_gridref"}

				<span{if $editable && $item.oldvalue != $image->$field} style="text-decoration: line-through"{/if}>
					{getamap gridref=$item.oldvalue|default:'blank'}
				</span>
				to
				{getamap gridref=$item.newvalue|default:'blank'}

			{elseif $item.field eq "comment"}
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
		</div>
	{/if}
	
	<div class="ticketnotes">
		<div class="ticketnote">{$ticket->notes|escape:'html'|geographlinks}</div>
	
		
		{if $ticket->comments and ($isadmin or $isowner)}


			{foreach from=$ticket->comments item=comment}
			<div class="ticketnote">
				<div class="ticketnotehdr">{$comment.realname} {if $comment.moderator}(Moderator){/if} wrote on {$comment.added|date_format:"%a, %e %b %Y at %H:%M"}</div>
				{$comment.comment|escape:'html'|geographlinks}


			</div>
			{/foreach}

		{/if}
		
	

	</div>
	
	{if ($isadmin or $isowner) and ($ticket->status ne "closed")}
	<div class="ticketactions">
		<textarea name="comment" rows="2" cols="50"></textarea><br/>
		
		<input type="submit" name="addcomment" value="Add comment"/>
		{if $isadmin}
		
			{if $ticket->changes}
		
			<input type="submit" name="accept" value="Accept ticked changes and close ticket" onclick="autoDisable(this)"/>

			{else}

			<input type="submit" name="close" value="Close ticket" onclick="autoDisable(this)"/>

			{/if}
		{/if}
		
	</div>
	{/if}
	

</div>
</form>
{/foreach}


{/if}


<br/>
<br/>


<h2 class="titlebar">Report Problem / Change Image Details <small><a href="/help/changes">[help]</a></small></h2>
{if $error}
<a name="form"></a>
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}

	{if $rastermap->enabled}
		<div class="rastermap" style="float:right;  width:45%;position:relative">
		
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		 
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				window.onload = function () {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					onChangeImageclass();
				}
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="/mapping.js?v={$javascript_version}"></script>
	{/if}
	
 		


<form method="post" action="/editimage.php#form" name="theForm" onsubmit="this.imageclass.disabled=false">
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
  <a title="Open in Google Earth" href="/kml.php?id={$image->gridimage_id}" class="xml-kml">KML</a></div>

<p>
<label for="grid_reference">Subject Grid Reference {if $moderated.grid_reference}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="14" value="{$image->subject_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false)"/>
{getamap gridref=$image->subject_gridref text="Get-a-map&trade; for `$image->subject_gridref`"}
</p>

<p>
<label for="photographer_gridref">Optional Photographer Grid Reference {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="14" value="{$image->photographer_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false)"/>
{if $image->photographer_gridref}
  {getamap gridref=$image->photographer_gridref text="Get-a-map&trade; for `$image->photographer_gridref`"}
{else}
  {getamap text="Get-a-map&trade;"}
{/if}
{if $rastermap->enabled}
	<br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1"/> <label for="use6fig">Only display 6 figure grid reference (<a href="/help/map_precision" target="_blank">Explanation</a>)</label>
{/if}
</p>


<p><label for="view_direction">Edit View Direction  {if $moderated.view_direction}<span class="moderatedlabel">(moderated)</span>{/if}
</label> <small>(photographer facing)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
	{foreach from=$dirs key=key item=value}
		<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$image->view_direction} selected="selected"{/if}>{$value}</option>
	{/foreach}
</select></p>


<p><label for="title">Edit the Title {if $moderated.title}<span class="moderatedlabel">(moderated)</span>{/if}</label> (<a href="/help/style" target="_blank">Open Style Guide</a>)<br/>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title|escape:'html'}" title="Original: {$image->current_title|escape:'html'}" spellcheck="true"/>
</p>





<script type="text/javascript" src="/categories.js.php"></script>
{if !$rastermap->enabled}
{literal}
<script type="text/javascript">
<!--
//rest loaded in geograph.js

window.onload = onChangeImageclass;
//-->
</script>
{/literal}
{/if}
<p><label for="imageclass">Edit Category {if $moderated.imageclass}<span class="moderatedlabel">(moderated)</span>{/if}</label><br />	
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
	</select><input type="button" name="imageclass_enable_button" value="enable" onclick="prePopulateImageclass()"/>
	
	
	<span id="otherblock">
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/></p>
	</span>
	

	
</p>	

{if $user->user_id eq $image->user_id}

	<p><label>Edit Date picture taken {if $moderated.imagetaken}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>
{else}
<p><label>Date picture taken</label> <span class="moderatedlabel">(only changable by submitter)</span><br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m" all_extra="disabled"}</p>
{/if}


<p>
<label for="comment">Edit Image Comment {if $moderated.comment}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="7" cols="80" title="Original: {$image->current_comment|escape:'html'}" spellcheck="true">{$image->comment|escape:'html'}</textarea>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>
</p>

<br/>
<p>
<label for="updatenote">&nbsp;<b>Please tell us what's wrong or briefly why you have made the changes above...</b></label><br/>

{if $error.updatenote}<br/><span class="formerror">{$error.updatenote}</span><br/>{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="4" cols="50">{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
Please provide as much detail for the moderator 
{if $user->user_id ne $image->user_id} and submitter{/if} about 
any necessary change (if you know the details e.g. a corrected grid reference,
then please enter directly into the boxes above)
</div>

</td></tr></table>

<div>
{if $isadmin}<input type="checkbox" name="mod" value="apply" id="mod_apply"/> <label for="mod_mod">Apply changes immediately. (Photographer is notified)</label><br/>{/if}
<input type="checkbox" name="type" value="minor" id="type_minor"/> <label for="type_minor">I certify that this change is minor, e.g. only spelling and grammar.</label>
</div>

<br style="clear:both"/>

<input type="submit" name="save" value="Submit Changes" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>


</form>



{else}
	<h2>Sorry, image not available</h2>

	<p>{$error}</p>

	<p>Please <a title="Contact Us" href="/contact.php">contact us</a> 
	if you have queries</p>
{/if}

{include file="_std_end.tpl"}
{/dynamic}
