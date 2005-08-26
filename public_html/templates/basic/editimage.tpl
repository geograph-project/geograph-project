{include file="_std_begin_dynamic.tpl"}
{dynamic}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow">{$image->getFull()}</div>
  <div class="caption">{$image->title|escape:'html'}</div>
  
  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'}</div>
  {/if}
</div>

{if $thankyou eq 'pending'}
	<h2>Thankyou!</h2>
	<p>Thanks for suggesting changes, you will receive an email when 
	we process your suggestion. </p>

	<p>You can review your requested changes below, or <a href="/photo/{$image->gridimage_id}">click here to return to the photo page</a></p>
{/if}

{if $thankyou eq 'comment'}
	<h2>Thankyou!</h2>
	<p>Thanks for commenting on the change request, the moderators have been notified.</p>

	<p>You can review outstanding change requests below, or <a href="/photo/{$image->gridimage_id}">click here to return to the photo page</a></p>
{/if}


{if $show_all_tickets eq 1}
	<h2>All Change Requests</h2>
	
	{if $opentickets}	
	<p>All change requests for this image are listed below. 
	<a href="/editimage.php?id={$image->gridimage_id}&alltickets=0">Just show open requests.</a></p>
	{else}
	<p>There have been no change requests logged for this image</p>

	{/if}
{else}
	<h2>Open Change Requests</h2>
	{if $opentickets}	
	<p>Any open change requests are listed below. 
	{else}

	<p>There are no open change requests for this image. 
	{/if}
	To see older, closed requests, <a href="/editimage.php?id={$image->gridimage_id}&alltickets=1">view all requests</a></p>
{/if}

{if $opentickets}

{foreach from=$opentickets item=ticket}
<form action="/editimage.php" method="post">
<input type="hidden" name="gridimage_ticket_id" value="{$ticket->gridimage_ticket_id}"/>
<input type="hidden" name="id" value="{$ticket->gridimage_id}"/>

<div class="ticket">
	

	<div class="ticketbasics">

	{if $isadmin}
		Submitted by {$ticket->suggester_name} | 
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

			{if  $ticket->status eq "closed" }
				<input disabled="disabled" type="checkbox" {if ($item.status eq 'immediate') or ($item.status eq 'approved')}checked="checked"{/if}/>
				
			{else}
				{if $isadmin}
				<input type="checkbox" value="1" id="accept{$item.gridimage_ticket_item_id}" name="accepted[{$item.gridimage_ticket_item_id}]"/>
				{/if}
			{/if}
			<label for="accept{$item.gridimage_ticket_item_id}">
			Change {$item.field} from

			
			{if $item.field eq "grid_reference"}
			  {getamap gridref=$item.oldvalue}
			  to
			  {getamap gridref=$item.newvalue}
			  
			{else}
			  {$item.oldvalue|escape:'html'|default:'blank'}
			  to 
			  {$item.newvalue|escape:'html'|default:'blank'}
			{/if}
			
			</label>
			
			</div>
		{/foreach}
		</div>
	{/if}
	
	<div class="ticketnotes">
		<div class="ticketnote">{$ticket->notes}</div>
	
		
		{if $ticket->comments and ($isadmin or $isowner)}


			{foreach from=$ticket->comments item=comment}
			<div class="ticketnote">
				<div class="ticketnotehdr">{$comment.realname} {if $comment.moderator}(Moderator){/if} wrote on {$comment.added|date_format:"%a, %e %b %Y at %H:%M"}</div>
				{$comment.comment}


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
		
			<input type="submit" name="accept" value="Accept ticked changes and close ticket"/>

			{else}

			<input type="submit" name="close" value="Close ticket"/>

			{/if}
		{/if}
		
	</div>
	{/if}
	

</div>
</form>
{/foreach}


{/if}





{if $error}
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{else}
<h2>Report Problem / Change Image Details <small><a href="/help/changes">[help]</a></small></h2>
{/if}

<form method="post" action="/editimage.php">
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


<div style="margin-top:20px;">
<label for="updatenote">Tell us what's wrong...</label><br/>

{if $error.updatenote}<br/><span class="formerror">{$error.updatenote}</span><br/>{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="4" cols="50">{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
Please provide as much detail for the moderator 
{if $user->user_id ne $image->user_id} and submitter{/if} about 
any necessary change (even if you are unable to provide further
detail, e.g. a corrected grid reference)
</div>

</td></tr></table>

<br style="clear:both"/>
</div>



<p>
<label for="grid_reference">New Grid Reference {if $moderated.grid_reference}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="8" value="{$image->grid_reference|escape:'html'}"/>
It maybe useful to refer to the {getamap gridref=$image->grid_reference text="OS Get-a-Map for `$image->grid_reference`"}
   
  
</p>




<p><label for="title">New Title {if $moderated.title}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title|escape:'html'}"/>
</p>


<p>
<label for="comment">New Comment {if $moderated.comment}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="3" cols="50">{$image->comment|escape:'html'}</textarea>
</p>

{literal}
<script type="text/javascript">
<!--
function onChangeImageclass()
{
	var sel=document.getElementById('imageclass');
	var idx=sel.selectedIndex;
	
	var isOther=idx==sel.options.length-1
	
	var otherblock=document.getElementById('otherblock');
	otherblock.style.display=isOther?'inline':'none';
	
}
//-->
</script>
{/literal}

<p><label for="imageclass">New Category {if $moderated.imageclass}<span class="moderatedlabel">(moderated)</span>{/if}</label><br />	
	{if $error.imageclass}
	<span class="formerror">{$error.imageclass}</span><br/>
	{/if}
	
	{if $error.imageclassother}
	<span class="formerror">{$error.imageclassother}</span><br/>
	{/if}
	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()">
		<option value="">--please select feature--</option>
		{html_options options=$classes selected=$image->imageclass}
		<option value="Other">Other...</option>
	</select>
	
	
	<span id="otherblock" {if $image->imageclass ne 'Other'}style="display:none;"{else}style="display:inline;"{/if}>
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32"/></p>
	</span>
	

	
</p>	

{if $user->user_id eq $image->user_id}

	<p><label>Date picture taken {if $moderated.imagetaken}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>
{/if}

<input type="submit" name="save" value="Submit Changes"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>







</form>



{else}
	<h2>Sorry, image not available</h2>

	<p>{$error}</p>

	<p>Please <a title="Contact Us" href="/contact.php">contact us</a> 
	if you have queries</p>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
