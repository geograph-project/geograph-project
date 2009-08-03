{assign var="page_title" value="Trouble Tickets"}
{include file="_std_begin.tpl"}

<script type="text/javascript" src="{"/sorttable.js"|revision}"></script>
<script type="text/javascript" src="{"/admin/moderation.js"|revision}"></script>

{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);
</script>{/literal}

{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :: Trouble Tickets, <small>{$title}</small></h2>

    <form method="get" action="{$script_name}" style="background-color:#f0f0f0;padding:2px;margin:0px; border:1px solid #d0d0d0;">
    <div> 
    <span class="nowrap">When:<select name="modifer">
    	{html_options options=$modifers selected=$modifer}
    </select></span> &nbsp;
    <span class="nowrap">Type:<select name="type">
    	{html_options options=$types selected=$type}
    </select></span> &nbsp;
    <span class="nowrap">Your:<select name="theme">
    	{html_options options=$themes selected=$theme}
    </select></span> &nbsp;
    <span class="nowrap">Contributor:<select name="variation">
    	{html_options options=$variations selected=$variation}
    </select></span><br/>
    Include:
     <label for="minor">Minor</label><input type="checkbox" name="i" id="minor" {if $minor} checked="checked"{/if}/>/
     <label for="major">Major</label><input type="checkbox" name="a" id="major" {if $major} checked="checked"{/if}/> &nbsp;
     <label for="defer" style="color:gray">Deferred</label><input type="checkbox" name="defer" id="defer" {if $defer} checked="checked"{/if}/> &nbsp;
     <label for="locked" style="color:red">Locked</label><input type="checkbox" name="locked" id="locked" {if $locked} checked="checked"{/if}/> &nbsp;
    <input type="submit" name="Submit" value="Update"/>
    &nbsp;&nbsp;&nbsp;&nbsp;
    (Keywords Search:<input type="text" name="q" value="{$q|escape:'html'}"> <sup><a href="#n1">1</a></sup>)
    
    </div></form>

{if $newtickets}

{if $moderator}
<p>These tickets have been recently been touched by the selected moderator</p>
{elseif $locked}
<p>NOTE: <span style="color:red">tickets in red</span> are currently open by another moderator, it is not recommended to process these tickets</p>
{else}
<p>Tickets currently open by other moderators are not shown in the list below. Click the small D button to defer the ticket for 24 hours.</p>
{/if}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	{if $col_moderator}<td>Moderator</td>{/if}
	<td>Contributor</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Suggested by</td>
	<td>Submitted</td>
	<td title="Defer ticket for a day, and Age of ticket in days">&nbsp;</td>
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}" {if !$ticket.available} style="color:red"{/if}>
{if $col_moderator}<td>{$ticket.moderator}</td>{/if}
<td{if !$ticket.ownimage && (($ticket.submitter_ticket_option == 'none') || ($ticket.submitter_ticket_option == 'major' && $ticket.type == 'minor') || $ticket.submitter_dormant)} style="text-decoration:line-through"{/if}>{$ticket.submitter}{if $ticket.submitter_comment}<img src="http://{$static_host}/img/star-light.png" width="14" height="14" title="Comment: {$ticket.submitter_comment}"/>{/if}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title|default:'Untitled'}</a></td>
<td>{if $ticket.type == 'minor'}(minor) {/if}{$ticket.notes|escape:'html'|geographlinks}</td>
<td>{$ticket.suggester}{if $ticket.suggester_comment}<img src="http://{$static_host}/img/star-light.png" width="14" height="14" title="Comment: {$ticket.suggester_comment}"/>{/if}</td>
<td>{$ticket.suggested}</td>
<td sortvalue="{$ticket.days}"><input class="accept" type="button" id="defer" value="D" style="width:10px;" onclick="deferTicket({$ticket.gridimage_ticket_id},24)"/><span class="caption" id="modinfo{$ticket.gridimage_ticket_id}"></span><br/>{$ticket.days}</td>
</tr>
{/foreach}
</tbody>
</table>
<br/>
<div class="interestBox" style="padding-left:100px"><a href="/admin/tickets.php?{$query_string}">Continue &gt;</a> 
		or <a href="/admin/moderation.php?abandon=1">Finish</a> the current moderation session</div>


<p><small>KEY: |<span style="text-decoration:line-through">User opted out of receiving initial notification</span> | <img src="http://{$static_host}/img/star-light.png" width="14" height="14" title="Comment"/> User has left comment on this ticket | <input class="accept" type="button" value="D" style="width:10px;"> - Defer the ticket for 24 hours - the number after the defer button is days since last update | <span style="color:red">ticket is locked</span>|</small> </p>



{else}
  <p>There are no tickets available to moderate at this time, please try again later.</p>
{/if}

<p><small><a name="n1">Note 1: Only works on active (not closed) tickets, and is <b>not updated live</b>. Uses same basic syntax as image/forum search, including negative keywords. Searches Grid Reference (4fig, hectad and myriad), Image Title/Comment, Ticket Note, Contributor Name and Suggestor Name. (but not changes themselves or replies yet)</small> </p>

{/dynamic}    
{include file="_std_end.tpl"}
