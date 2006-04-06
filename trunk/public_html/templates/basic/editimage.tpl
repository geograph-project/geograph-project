{assign var="page_title" value="Update Image Details"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 
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
  <div class="caption"><b>{$image->title|escape:'html'}</b> by {$image->realname}</div>
  
  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'|geographlinks}</div>
  {/if}
  {if $isadmin || ($user->user_id eq $image->user_id)}
  <div class="statuscaption">status:
   {if $image->ftf}first{/if}
   {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
  {/if}

  	{if !$isadmin and ($user->user_id eq $image->user_id) and $image->moderation_status != 'rejected'}
  	  <form action="/moderation.php" method="post">
  	  <input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
  	  <b>Self Moderation</b>
  	  
  	  {if $image->moderation_status == 'pending' && $image->user_status == 'accepted'}
  	  <input class="accept" type="submit" id="geograph" name="user_status" value="Geograph"/>
  	  {/if}
  	  {if $image->moderation_status != 'accepted' && $image->user_status != 'accepted'}
  	  <input class="accept" type="submit" id="accept" name="user_status" value="Supplemental"/>
  	  {/if}
  	  {if $image->user_status != 'rejected'}
  	  <input class="reject" type="submit" id="reject" name="user_status" value="Reject"/>
  	  {/if}
  	  {if $image->user_status}
	  <br/>[Currently: {if $image->user_status eq "accepted"}supplemental{else}{$image->user_status}{/if}]
	  {/if}
  	  
  	  </form>
	{/if}
</div>

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
	<h2>
	{if $isadmin}<a href="/admin/tickets.php" title="Ticket Admin Listing">&lt;&lt;&lt;</a>{/if}
	All Change Requests</h2>
	
	{if $opentickets}	
	<p>All change requests for this image are listed below. 
	<a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=0">Just show open requests.</a></p>
	{else}
	<p>There have been no change requests logged for this image</p>

	{/if}
{else}
	<h2>
	{if $isadmin}<a href="/admin/tickets.php" title="Ticket Admin Listing">&lt;&lt;&lt;</a>{/if}
	Open Change Requests</h2>
	{if $opentickets}	
	<p>Any open change requests are listed below. 
	{else}

	<p>There are no open change requests for this image. 
	{/if}
	To see older, closed requests, <a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=1">view all requests</a></p>
{/if}

{if $opentickets}

{foreach from=$opentickets item=ticket}
<form action="/editimage.php" method="post" name="ticket{$ticket->gridimage_ticket_id}">
<input type="hidden" name="gridimage_ticket_id" value="{$ticket->gridimage_ticket_id}"/>
<input type="hidden" name="id" value="{$ticket->gridimage_id}"/>

<div class="ticket">
	

	<div class="ticketbasics">

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




<h2>Report Problem / Change Image Details <small><a href="/help/changes">[help]</a></small></h2>
{if $error}
<a name="form"></a>
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}

<form method="post" action="/editimage.php#form" name="edit{$image->gridimage_id}">
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



<p>
<label for="grid_reference">Subject Grid Reference {if $moderated.grid_reference}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="8" value="{$image->subject_gridref|escape:'html'}"/>
{getamap gridref=$image->subject_gridref text="OS Get-a-Map for `$image->subject_gridref`"}
</p>

<p>
<label for="photographer_gridref">Optional Photographer Grid Reference {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="8" value="{$image->photographer_gridref|escape:'html'}"/>
{if $image->photographer_gridref}
  {getamap gridref=$image->photographer_gridref text="OS Get-a-Map for `$image->photographer_gridref`"}
{else}
  {getamap text="OS Get-a-Map"}
{/if}
</p>


<p><label for="view_direction">Edit View Direction  {if $moderated.view_direction}<span class="moderatedlabel">(moderated)</span>{/if}
</label> <small>(photographer facing)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace">
	{foreach from=$dirs key=key item=value}
		<option value="{$key}"
			{if $key%45!=0}style="color:gray"{/if}
			{if $key==$image->view_direction}selected="selected"{/if}
			>{$value}</option>
	{/foreach}
</select></p>


<p><label for="title">Edit the Title {if $moderated.title}<span class="moderatedlabel">(moderated)</span>{/if}</label> (<a href="/help/style" target="_blank">Open Style Guide</a>)<br/>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title|escape:'html'}"/>
</p>


<p>
<label for="comment">Edit Comment {if $moderated.comment}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="3" cols="50">{$image->comment|escape:'html'}</textarea>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>
</p>


<script type="text/javascript" src="/categories.js.php"></script>
{literal}
<script type="text/javascript">
<!--
//rest loaded in geograph.js
function prePopulateImageclass() {
	setTimeout('populateImageclass()',500);
}

window.onload = prePopulateImageclass;
//-->
</script>
{/literal}

<p><label for="imageclass">Edit Category {if $moderated.imageclass}<span class="moderatedlabel">(moderated)</span>{/if}</label><br />	
	{if $error.imageclass}
	<span class="formerror">{$error.imageclass}</span><br/>
	{/if}
	
	{if $error.imageclassother}
	<span class="formerror">{$error.imageclassother}</span><br/>
	{/if}
	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()">
		<option value="">--please select feature--</option>
		{if $image->imageclass}
			<option value="{$image->imageclass}" selected="selected">{$image->imageclass}</option>
		{/if}
		<option value="Other">Other...</option>
	</select>
	
	
	<span id="otherblock">
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32"/></p>
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
