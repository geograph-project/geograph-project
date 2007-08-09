{assign var="page_title" value="Trouble Tickets"}
{include file="_std_begin.tpl"}

<script type="text/javascript" src="http://s0.{$http_host}/sorttable.js"></script>
<script type="text/javascript" src="http://s0.{$http_host}/admin/moderation.v{$javascript_version}.js"></script>

{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);
</script>{/literal}

{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :: Trouble Tickets, <small>{$title}</small></h2>

    <form method="get" action="{$script_name}">
    <p> 
   When:<select name="modifer">
    	{html_options options=$modifers selected=$modifer}
    </select> <br/>
    Type:<select name="type">
    	{html_options options=$types selected=$type}
    </select> <br/>
    Your:<select name="theme">
    	{html_options options=$themes selected=$theme}
    </select> <br/>
    Photographer:<select name="variation">
    	{html_options options=$variations selected=$variation}
    </select><br/>
    <label for="defer">Include Deferred?</label><input type="checkbox" name="defer" id="defer" {if $defer} checked="checked"{/if}/> &nbsp;
     <label for="minor">Minor</label><input type="checkbox" name="i" id="minor" {if $minor} checked="checked"{/if}/> &nbsp;
     <label for="major">Major</label><input type="checkbox" name="a" id="major" {if $major} checked="checked"{/if}/> &nbsp;
    <input type="submit" name="Submit" value="Go"/></p></form>

{if $newtickets}

{if $moderator}
<p>These tickets have been recently been touched by the selected moderator</p>
{/if}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	{if $col_moderator}<td>Moderator</td>{/if}
	<td>Photographer</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Suggested by</td>
	<td>Submitted</td>
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
{if $col_moderator}<td>{$ticket.moderator}</td>{/if}
<td>{$ticket.submitter}{if $ticket.submitter_comments}<img src="/templates/basic/img/star-light.png" width="14" height="14" title="Comment: {$ticket.submitter_comment}"/>{/if}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title|default:'Untitled'}</a></td>
<td>{if $ticket.type == 'minor'}(minor) {/if}{$ticket.notes|escape:'html'|geographlinks}</td>
<td>{$ticket.suggester}</td>
<td>{$ticket.suggested}</td>
<td><input class="accept" type="button" id="defer" value="D" style="width:10px;" onclick="deferTicket({$ticket.gridimage_ticket_id},24)"/><span class="caption" id="modinfo{$ticket.gridimage_ticket_id}"></span></td>
</tr>
{/foreach}
</tbody>
</table>
<br/>
<div class="interestBox" style="padding-left:100px"><a href="/admin/tickets.php?{$query_string}">Continue &gt;</a> 
		or <a href="/admin/moderation.php?abandon=1">Finish</a> the current moderation session</div>

{else}
  <p>There are no tickets available to moderate at this time, please try again later.</p>
{/if}


{/dynamic}    
{include file="_std_end.tpl"}
