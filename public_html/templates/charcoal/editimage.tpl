{assign var="page_title" value="Update Image Details"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->current_title|escape:'html'}</h2>

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>This image is currently locked by {$locked_by_moderator}</b>, please come back later.
	</p>
{/if}

{if $error}
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}

{if $current_search}
	<div class="interestBox" style="text-align:center; font-size:0.9em;width:400px;margin-left:auto;margin-right:auto">
		{if $current_search.l}
			<a href="/editimage.php?id={$current_search.l}">&lt; prev image</a>
		{elseif $current_search.c > 1}
			<a href="/search.php?i={$current_search.i}&amp;page={$current_search.p-1}">&lt; prev page</a>
		{else}
			<s style="color:silver" title="first image on this page - you may be able to get to another page via the 'back to search results' itself">&lt; prev image</s>
		{/if} |
		<a href="/search.php?i={$current_search.i}&amp;page={$current_search.p}"><b>back to search results</b></a> |
		{if $current_search.n}
			<a href="/editimage.php?id={$current_search.n}">next image &gt;</a>
		{elseif $current_search.c < $current_search.t}
			<a href="/search.php?i={$current_search.i}&amp;page={$current_search.p+1}">next page &gt;</a>
		{else}
			<s style="color:silver" title="last image on this page - you may be able to get to another page via the 'back to search results' itself">next image &gt;</s>
		{/if}
	</div>
 {/if}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	{if $image->original_width}
		<div class="caption640" style="text-align:right;"><a href="/more.php?id={$image->gridimage_id}">More sizes</a> | <a href="/resubmit.php?id={$image->gridimage_id}">Upload another large version</a></div>
	{elseif $user->user_id eq $image->user_id}
		<div class="caption640" style="text-align:right;"><a href="/resubmit.php?id={$image->gridimage_id}">Upload a larger version</a></div>
	{/if}
  {if $thumb}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=0" style="font-size:0.6em">Switch to full image</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a></div>
  {else}
  	{if $isadmin}
  		<a href="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" style="font-size:0.6em">Switch to thumbnail image</a>
  	{/if}
  	<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getFull()}</a></div>
  {/if}
  <div class="caption"><b>{$image->current_title|escape:'html'}</b> by <a href="{$image->profile_link}">{$image->realname|escape:'html'}</a>{if $isowner} (<a href="/licence.php?id={$image->gridimage_id}">change credit</a>){/if}</div>

  {if $image->comment}
  <div class="caption" style="border:1px dotted lightgrey;">{$image->current_comment|escape:'html'|geographlinks}</div>
  {/if}
  {if $image->snippet_count}
	{if !$image->comment && $image->snippet_count == 1}
		{assign var="item" value=$image->snippets[0]}
		<div class="caption640">
		{$item.comment|escape:'html'|nl2br|geographlinks}{if $item.title}<br/><br/>
		<small>See other images of <a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></small>{/if}
		</div>
	{else}
		{foreach from=$image->snippets item=item name=used}
			{if !$image->snippets_as_ref && !$item.comment}
				<div class="caption640 searchresults"><br/>
				<small>See other images of <a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></small>
				</div>
			{else}
				<div class="snippet640 searchresults" id="snippet{$smarty.foreach.used.iteration}">
				{if $image->snippets_as_ref}{$smarty.foreach.used.iteration}. {/if}<b><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'|default:'untitled'}</a></b> {if $item.grid_reference && $item.grid_reference != $image->grid_reference}<small> :: <a href="/gridref/{$item.grid_reference}">{$item.grid_reference}</a></small>{/if}
				<blockquote>{$item.comment|escape:'html'|nl2br|geographlinks}</blockquote>
				</div>
			{/if}
		{/foreach}
	{/if}
	{literal}
	<script type="text/javascript">
	 AttachEvent(window,'load',function () {
			collapseSnippets({/literal}{$image->snippet_count}{literal});
		},false);
	</script>
	{/literal}
  {/if}
  <div class="statuscaption">classification:
   {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}
   {if $image->mod_realname}(moderator: <a href="/profile/{$image->moderator_id}" class="statuscaption">{$image->mod_realname|escape:'html'}</a>){/if}</div>
</div>
{if $showfull}
  	{if $isowner and $image->moderation_status eq 'pending'}
  	  {if $thankyou eq 'mod'}
	  	<h2 class="titlebar" style="background-color:lightgreen">Thank you</h2>
	  	<p>Your suggestion has been recorded, it will be taken into account during moderation. <a href="/photo/{$image->gridimage_id}">Return to the image page</a></p>
	  {elseif $thankyou eq 'modreply'}
	  	<h2 class="titlebar" style="background-color:lightgreen">Thank You</h2>
	  	<p>Your suggestion has been recorded, it will be taken into account during moderation, however please use the comment box below to explain the reason for the suggestion.</p>
	  {/if}

  	  <form action="/moderation.php" method="post">
  	  <input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
 	  <h2 class="titlebar">Moderation Suggestion</h2>
	  {if $image->user_status == 'accepted'}

  	    <p>I suggest this image should become:
  	    {if $image->user_status}
  	      <input class="accept" type="submit" id="geograph" name="user_status" value="Geograph"/>
  	    {/if}
  	    {if $image->user_status != 'accepted'}
  	      <input class="accept" type="submit" id="accept" name="user_status" value="Supplemental"/>
  	    {/if}
  	    {if $image->user_status != 'rejected'}
  	      <input class="reject" type="submit" id="reject" name="user_status" value="Reject" onclick="this.form.elements['comment'].value = prompt('Please leave a comment to explain the reason for suggesting rejection of this image.','');"/>
  	    {/if}
  	    {if $image->user_status}
	       <br/><small>[Current suggestion: {if $image->user_status eq "accepted"}Supplemental{else}{$image->user_status}{/if}</small>]
	    {/if}</p>
  	    <p style="font-size:0.8em">(Click one of these buttons to leave a hint to the moderator when they moderate your image)</p>
          {elseif $image->user_status == 'rejected'}
            <input class="accept" type="submit" id="geograph" name="user_status" value="Cancel Rejection request"/>
	  {else}
  	    <input class="reject" type="submit" id="reject" name="user_status" value="Request Rejection of this image" onclick="this.form.elements['comment'].value = prompt('Please leave a comment to explain the reason for suggesting rejection of this image.','');"/>
		Note: You should only request rejection of an image that should not be released. If any of the details are incorrect (inc. grid-references) just change them below. 
          {/if}
	  <input type="hidden" name="comment"/>
  	  </form>

  	{elseif $isadmin and $image->user_status}
  	  <h2 class="titlebar">Moderation Suggestion</h2>
  	   Suggestion: {if $image->user_status eq "accepted"}Supplemental{else}{$image->user_status}{/if}
	{/if}
<br/>
<br/>
  {if $isadmin && $is_mod}
	  <form method="post">

	  <script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>
	  <h2 class="titlebar">Moderation</h2>
		<div style="position:relative;float:right">
			<span id="votediv{$image->gridimage_id}">{votestars id=$image->gridimage_id type="mod"}</span>
		</div>
	  <p>{if $image->moderation_status eq 'pending'}
	  	<small>(pending images should be moderated in sequence via the moderation page)</small>
	  {else}
	  <input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Supp" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  {/if}
	  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <span class="caption" id="modinfo{$image->gridimage_id}">Current Classification: {$image->moderation_status}{if $image->mod_realname}<abbr title="Approximate date of last moderation: {$image->moderated|date_format:"%a, %e %b %Y"}"><small><small>, by <a href="/usermsg.php?to={$image->moderator_id}&amp;image={$image->gridimage_id}">{$image->mod_realname|escape:'html'}</a></small></small></abbr>{/if}</span></p>
	  </form>
  {/if}


{if $thankyou eq 'pending'}
	<a name="form"></a>
	<br/><br/>
	<h2 class="titlebar" style="background-color:lightgreen">Thank you</h2>
	<p>Thanks for suggesting changes, you will receive an email when
	we process your suggestion. </p>

	<p>You can review your requested changes below, or <a href="/photo/{$image->gridimage_id}">click here to return to the image page</a></p>
	<br/><br/>
{/if}

{if $thankyou eq 'closed'}
	<a name="form"></a>
	<br/><br/>
	<h2 class="titlebar" style="background-color:lightgreen">Thank you</h2>
	<p>Your changes have been saved, and will be visible shortly.</p>

	<p>You can review the changes below, or <a href="/photo/{$image->gridimage_id}">click here to return to the image page</a></p>
	<br/><br/>
{/if}

{if $thankyou eq 'comment'}
	<a name="form"></a>
	<br/><br/>
	<h2 class="titlebar" style="background-color:lightgreen">Thank you</h2>
	<p>Thanks for commenting on the change request, the moderators have been notified.</p>

	<p>You can review outstanding change requests below, or <a href="/photo/{$image->gridimage_id}">click here to return to the image page</a></p>
	<br/><br/>
{/if}


{if $show_all_tickets eq 1}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/suggestions.php" title="Suggestions Admin Listing">&lt;&lt;&lt;</a>{/if}
	All Change Suggestions
	{if $isowner}<small>(<a href="/suggestions.php" title="Suggestions Listing">back to listing</a>)</small>{/if}
	</h2>

	{if $opentickets}
	<p>All change suggestions for this image are listed below.
	<a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=0">Just show open suggestions.</a></p>
	{else}
	<p>There have been no change suggestions logged for this image</p>

	{/if}
{else}
	<h2 class="titlebar">
	{if $isadmin}<a href="/admin/suggestions.php" title="Suggestions Admin Listing">&lt;&lt;&lt;</a>{/if}
	Open Change Suggestions
	{if $isowner}<small>(<a href="/suggestions.php" title="Suggestions Listing">back to listing</a>)</small>{/if}
	</h2>
	{if $opentickets}
	<p>Any open change suggestions are listed below.
	{else}

	<p>There are no open change suggestions for this image.
	{/if}
	To see older, closed suggestions, <a href="/editimage.php?id={$image->gridimage_id}&amp;alltickets=1">view all suggestions</a></p>
{/if}

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>This image is currently locked by {$locked_by_moderator}</b>, please come back later.
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
			{if $item.field eq "tag" or $item.field eq "snippet" and (!$item.newvalue || !$item.oldvalue)}
				{if $item.newvalue}
					Add {$item.field}
				{else}
					Remove {$item.field}
				{/if}
			{else}
				Change {$item.field} from
			{/if}

			{if $item.field eq "grid_reference"}
				{assign var="field" value="current_subject_gridref"}
			{else}
				{assign var="field" value="current_`$item.field`"}
			{/if}

			{if $item.field eq "tag" or $item.field eq "snippet"}
				{if $item.oldvalue}<span style="text-decoration: line-through;font-family:monospace">[{$item.oldhtml|default:'blank'}]</span>{/if}
				{if $item.oldvalue && $item.newvalue}to{/if}
				{if $item.newvalue}<span style="font-family:monospace">[{$item.newhtml|default:'blank'}]</span>{/if}

			{elseif $item.field eq "grid_reference" || $item.field eq "photographer_gridref"}

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

			{if $editable && $item.field ne "tag" && $item.field ne "snippet" && $item.newvalue == $image->$field}
				<b>Changes already applied</b>
			{/if}

			</label>

			</div>
		{/foreach}
		{if $ticket->reopenmaptoken}
			<div style="text-align:right"><a href="/submit_popup.php?t={$ticket->reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Open map for these <i>new</i> values</a>&nbsp;&nbsp;&nbsp;</div>
		{/if}
		</div>
	{/if}

	{if ($isadmin or $isowner or ($ticket->user_id eq $user->user_id and $ticket->notify=='suggestor') )}
	<div class="ticketnotes">
		<div class="ticketnote">{$ticket->notes|escape:'html'|geographlinks|replace:'Auto-generated ticket, as a result of Moderation. Rejecting this image because:':'<span style="color:gray">Auto-generated, as a result of moderation. Rejecting this image because:</span><br/>'}</div>


		{if $ticket->comments}
			{if $isadmin or $isowner or ($user->user_id eq $ticket->user_id && $ticket->notify eq 'suggestor')}
				{foreach from=$ticket->comments item=comment}
				<div class="ticketnote">
					<div class="ticketnotehdr">
					{if $comment.user_id ne $ticket->user_id or ($isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner')) }
						{$comment.realname|escape:'html'} {if $ticket->public ne 'everyone' && $ticket->user_id eq $comment.user_id}(anonymously){/if}
					{else}
						suggestor
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
					<div class="ticketnotehdr">{$ticket->lastcomment.realname|escape:'html'} {if $ticket->lastcomment.moderator}(Moderator){/if} wrote on {$ticket->lastcomment.added|date_format:"%a, %e %b %Y at %H:%M"}</div>
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
		<div>&nbsp;<b>Add a reply to this suggestion:</b></div>
		<textarea name="comment" rows="4" cols="70"></textarea><br/>

		<input type="submit" name="addcomment" value="Add comment"/>

		{if $isadmin and $ticket->moderator_id > 0 and $ticket->moderator_id != $user->user_id}
			<input type="checkbox" name="claim" value="on" id="claim" checked="checked"/> <label for="claim" title="Claim this suggestion to be moderated by me">Claim Suggestion</label>
			&nbsp;&nbsp;&nbsp;
		{elseif $isadmin}
			<input type="hidden" name="claim" value="on"/>
		{/if}

		{if ($isowner || $isadmin) && $ticket->user_id ne $user->user_id}
			<input type="checkbox" name="notify" value="suggestor" id="notify_suggestor" {if $ticket->notify=='suggestor'}checked="checked"{/if}/> <label for="notify_suggestor">Send {if $isadmin || $ticket->public eq 'everyone' || ($isowner && $ticket->public eq 'owner') }{$ticket->suggester_name}{else} suggestor{/if} this comment.</label>
			&nbsp;&nbsp;&nbsp;
		{/if}
		{if $isadmin}

			{if $ticket->changes}

			<input type="submit" name="accept" value="Accept ticked changes and close suggestion" onclick="autoDisable(this)"/>

			{else}

			<input type="submit" name="close" value="Close suggestion" onclick="autoDisable(this)"/>

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
		<div style="text-align:right">
		<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=1" style="font-size:0.6em">Switch to Simple Edit Page</a>
		</div>

	{/if}
{else}
	<div style="text-align:right">
	<a href="/editimage.php?id={$image->gridimage_id}&amp;simple=0" style="font-size:0.6em">Switch to Full Edit Page</a>
	</div>
{/if}

{if $opentickets && !$error && $isowner && $ticketsforcomments && $showfull}
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<ul>
		<li>If you agree with the changes suggested, please indicate your acceptance, <b>in the reply box above</b>.</li>
		<li>If you disagree, please explain above why you do not accept the changes. This will be helpful to the moderator in making a decision.</li>
		<li>However, if you want to make the changes straight away {if $moderated.grid_reference}<span class="moderatedlabel">(except grid square changes)</span>{/if}, or want to make other changes, use the <b><a href="/editimage.php?id={$image->gridimage_id}&amp;form">Change Image Details</a> Form</b>.</li>
		<li>If a suggestion suggests an issue but doesn't actually list the changes then it would help us if you were to make the changes yourself</li>
	</ul>
</div>
<br>


{else}

<div class="tabHolder" style="font-size:1em">
	<a class="tabSelected nowrap" id="tab1" onclick="tabClick('tab','div',1,4)">Image Details</a>&nbsp;
        <a class="tab nowrap" id="tab2" onclick="tabClick('tab','div',2,4); document.getElementById('tagframe').src='/tags/tagger.php?gridimage_id={$image->gridimage_id}{if $isadmin}&amp;admin=1{/if}';">Tags, Subject, Geographical Context</a>&nbsp;
{if $isadmin || $isowner}
        <a class="tab nowrap" id="tab3" onclick="tabClick('tab','div',3,4); document.getElementById('shareframe').src='/submit_snippet.php?gridimage_id={$image->gridimage_id}&gr='+escape(document.theForm.grid_reference.value)+'&gr2={$image->subject_gridref|escape:'html'}{if $isadmin}&amp;admin=1{/if}';">Shared Descriptions</a>
	<a class="tab nowrap" id="tab4" onclick="tabClick('tab','div',4,4); document.getElementById('nearframe').src='/finder/used-nearby.php?gridimage_id={$image->gridimage_id}&gr='+escape(document.theForm.grid_reference.value)+'&gr2={$image->subject_gridref|escape:'html'}';">Used Nearby</a>&nbsp;
{/if}
</div>

<div id="div1">
<h2 class="titlebar" style="margin-bottom:0px">{if $isowner}Change{else}Suggest a Change to{/if} Image Details <small><a href="/help/changes">[help]</a></small></h2>
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
		<b>Please make the required changes in the boxes below</b>.<br/>
		&middot; Any changes you suggest are moderated and will first be approved by
		a moderator before going live.<br/>&middot; You will receive an email when this happens.
	{else}
		&middot; If you change any fields labelled as "moderated" your changes will first be approved by
		a moderator before going live.<br/>&middot; You will receive an email when this happens.
	{/if}
</div>
{/if}

  <div style="float:right;  position:relative">
  <a title="Open in Google Earth" href="/photo/{$image->gridimage_id}.kml" class="xml-kml">KML</a></div>

<p>
<label for="grid_reference"><b style="color:#0018F8">Subject Grid Reference</b> {if $moderated.grid_reference}<span class="moderatedlabel">(moderated{if $isowner} for gridsquare changes{/if})</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="14" value="{$image->subject_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>{if $rastermap->service != 'Google'}<img src="{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/>{else}<img src="https://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks the Subject" width="14" height="24" align="middle"/>{/if}


<p>
<label for="photographer_gridref"><b style="color:#002E73">Camera Grid Reference</b> - Optional {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="14" value="{$image->photographer_gridref|escape:'html'}" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>{if $rastermap->service != 'Google'}<img src="{$static_host}/img/icons/viewc--1.png" alt="Marks the Camera" width="29" height="29" align="middle"/>{else}<img src="{$static_host}/img/icons/camicon-new.png" alt="Marks the Camera" width="14" height="24" align="middle"/>{/if}
<br/>
<span style="font-size:0.6em">
| <a href="javascript:void(copyGridRef());">Copy from Subject</a> |
<a href="javascript:void(resetGridRefs());">Reset to initial values</a> |  <span id="dist_message" style="padding-left:20px"></span><br/></span>

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
</label> <small>(camera facing)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
	{foreach from=$dirs key=key item=value}
		<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$image->view_direction} selected="selected"{/if}>{$value}</option>
	{/foreach}
</select></p>

<span id="styleguidelink">({newwin href="/help/style" text="Open Style Guide"})</span>

<p><label for="title"><b>Title</b> {if $moderated.title}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
 <span class="formerror" style="display:none" id="titlestyle">Possible style issue. See Guide above. <span id="titlestylet" style="font-size:0.9em"></span><br/></span>
{if $error.title}<span class="formerror">{$error.title}</span><br/>{/if}
<input type="text" id="title" name="title" size="50" value="{$image->title|escape:'html'}" title="Original: {$image->current_title|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);" maxlength="128"/>
</p>


{if !$rastermap->enabled}
{literal}
<script type="text/javascript">

//rest loaded in geograph.js
AttachEvent(window,'load',onChangeImageclass,false);

</script>
{/literal}
{/if}

{if $image->imageclass}
	<p><label for="imageclass"><b>Image Category</b> (Optional, if supply Tags) {if $moderated.imageclass}<span class="moderatedlabel">(moderated)</span>{/if}</label><br />
		{if $error.imageclass}
		<span class="formerror">{$error.imageclass}</span><br/>
		{/if}

		<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onmouseover="prePopulateImageclass()" disabled="disabled">
			<option value="">--please select feature--</option>
			{if $image->imageclass}
				<option value="{$image->imageclass}" selected="selected">{$image->imageclass}</option>
			{/if}
		</select><input type="button" name="imageclass_enable_button" value="change" onclick="prePopulateImageclass()"/>

	</p>
{/if}

{if $user->user_id eq $image->user_id || $isadmin}
	<p><label><b>Date Picture Taken</b> {if $moderated.imagetaken}<span class="moderatedlabel">(moderated)</span>{/if}</label> <br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>
{else}
	<p><label><b>Date Picture Taken</b></label> <span class="moderatedlabel">(only changeable by owner)</span><br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m" all_extra="disabled"}</p>
{/if}


<p><label for="comment"><b>Description</b> {if $moderated.comment}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
 <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span><br/></span>
{if $error.comment}<span class="formerror">{$error.comment}</span><br/>{/if}
<textarea id="comment" name="comment" rows="7" cols="80" title="Original: {$image->current_comment|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$image->comment|escape:'html'}</textarea>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> to link to a grid square or <span style="color:blue">[[54631]]</span> to link to another image.<br/>
For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>
</p>

<br/>
<div class="interestBox">
<p>
<label for="updatenote">&nbsp;<b>Please describe what's wrong or briefly why you have {if $isowner}made{else}suggested{/if} the changes above...</b></label><br/>

{if $error.updatenote}<br/><span class="formerror">{$error.updatenote}</span><br/>{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="5" cols="60"{if $user->message_sig} onfocus="if (this.value=='') {literal}{{/literal}this.value='{$user->message_sig|escape:'javascript'}';setCaretTo(this,0); {literal}}{/literal}"{/if}>{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
	Please provide as much detail for the moderator
	{if !$isowner} and photo owner{/if} about this suggestion as possible.
	Explaining the reasoning behind the suggestion will greatly help everyone.
</div>

</td></tr></table>

<div>
<input type="checkbox" name="type" value="minor" id="type_minor"/> <label for="type_minor">I certify that this change is minor, e.g. only spelling and grammar.</label>
</div>

<br style="clear:both"/>

{if $isadmin}
	<div>
	<input type="radio" name="mod" value="" id="mod_blank" checked="checked"/> <label for="mod_blank">Create a new suggestion to be moderated by someone else.</label><br/>
	<input type="radio" name="mod" value="assign" id="mod_assign"/> <label for="mod_assign">Create an open suggestion and assign to myself. (Give the contributor a chance to respond.)</label><br/>
	<input type="radio" name="mod" value="apply" id="mod_apply"/> <label for="mod_apply">Apply the changes immediately, and close the suggestion. (Contributor is notified.)</label></div>

	<br style="clear:both"/>
{else}
	{if $isowner}
	<div>
		<input type="checkbox" name="mod" value="pending" id="mod_pending"{if $mod_pending} checked="checked"{/if}/> <label for="mod_pending">Bring this issue to the attention of a moderator (regardless of changes made).</label><br/><br/>
	</div>
	{/if}
{/if}


{if !$isowner && !$isadmin && $user->ticket_public ne 'everyone' && $user->ticket_public ne ''}
<div class="interestBox" style="background-color:pink;border:3px solid red;margin:10px">
	<b>We no longer allow anonymous suggestions</b><br/><br/>
	You currently have your preferences set to hide your name on new suggestions. This will no longer be honoured, and <span class="nowrap">your name and link to your profile <b>will</b> appear with the suggestion</span>, and be visible to everyone.<br/>

		<ul style="font-size:0.8em">
			<li>Remove this message by changing the option on your {newwin href="/profile.php?edit=1#prefs" text="preferences"} page.</li>
			<li>{newwin href="/contact.php" text="Contact us"} if you have concerns.</li>
	</div>
{/if}

<input type="submit" name="save" value="Submit Changes" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>

{if !$isowner && !$isadmin && ($user->ticket_public eq 'everyone' || $user->ticket_public eq '')}
	<span style="font-size:0.8em; color:gray; padding-left:50px">(We no longer allow anonymous suggestions)</span>
{/if}

</div>
</form>

	<script type="text/javascript">{literal}
	function previewImage() {
		window.open('','_preview');//forces a new window rather than tab?
		var f1 = document.forms['theForm'];
		var f2 = document.forms['previewForm'];
		for (q=0;q<f2.elements.length;q++) {
			if (f2.elements[q].name && f1.elements[f2.elements[q].name]) {
				f2.elements[q].value = f1.elements[f2.elements[q].name].value;
			}
		}
		return true;
	}
	{/literal}</script>
	<form action="/preview.php" method="post" name="previewForm" target="_preview" style="padding:10px; text-align:center">
	<input type="hidden" name="grid_reference"/>
	<input type="hidden" name="photographer_gridref"/>
	<input type="hidden" name="view_direction"/>
	<input type="hidden" name="use6fig"/>
	<input type="hidden" name="title"/>
	<textarea name="comment" style="display:none"/></textarea>
	<input type="hidden" name="imageclass"/>
	<input type="hidden" name="imagetakenDay"/>
	<input type="hidden" name="imagetakenMonth"/>
	<input type="hidden" name="imagetakenYear"/>
	<input type="hidden" name="id"/>
	<input type="submit" value="Preview edits in a new window" onclick="previewImage()"/>
	</form>


<br/><br/>
<p>Looking for Tags/Shared Descriptions? See the tabs, further up the page.</p> 
</div>

<div id="div2" style="display:none">
	<iframe src="about:blank" height="300" width="100%" id="tagframe">
	</iframe>

	<p>Once made all changes, can <a href="/photo/{$image->gridimage_id}">return to image page</a>, changes inside the Tags box are automatically saved. Or return to the 'Image Details' tab to make other changes.</p>
</div>
{if $isadmin || $isowner}
<div id="div3" style="display:none">
        <iframe src="about:blank" height="400" width="100%" id="shareframe">
        </iframe>

	<p>Once made all changes, can <a href="/photo/{$image->gridimage_id}">return to image page</a>, changes inside the Shared Description box are automatically saved. Or return to the 'Image Details' tab to make other changes.</p>
</div>

<div id="div4" style="display:none">
        <iframe src="about:blank" height="300" width="100%" id="nearframe">
        </iframe>

	<p>Once made all changes, can <a href="/photo/{$image->gridimage_id}">return to image page</a>, changes inside the Nearby box are automatically saved. Or return to the 'Image Details' tab to make other changes.</p>
</div>

{/if}


{/if}


{if $image->imageclass}
<script type="text/javascript" src="/categories.js.php"></script>
{/if}

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


function unloadMess() {
        return "**************************\n\nYou have unsaved changes in main Image Details tab.\n\n**************************\n";
}
function setupMess() {
	//this is unreliable with AttachEvent
	window.onbeforeunload=unloadMess;
}

function cancelMess() {
        window.onbeforeunload=null;
}
function setupSubmitForm() {
	var form = document.forms['theForm'];
	for(q=0;q<form.elements.length;q++)
		AttachEvent(form.elements[q],'change',setupMess,false);
        AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


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
