<html>
<head>
<title>Touble Tickets</title>
<script src="/sorttable.js"></script>
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/basic.css" media="screen" />

</head>
<body bgcolor="#ffffff">
<h2>Trouble&nbsp;Tickets</h2>

{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);
</script>{/literal}

{dynamic}

{if $newtickets}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	<td>Title</td>
	<td>Suggested by</td>
	<td>Submitted</td>
	<td>Photographer</td>	
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><b><a href="/editimage.php?id={$ticket.gridimage_id}" target="_main">{$ticket.title|default:'Untitled'}</a></b></td>
<td>{$ticket.suggester}</td>
<td>{$ticket.suggested}</td>
<td>{$ticket.submitter}{if $ticket.submitter_comments}<img src="/templates/basic/img/star-light.png" width="14" height="14" title="Comment: {$ticket.submitter_comment}"/>{/if}</td>
</tr>
<tr bgcolor="{$bgcolor}">
<td colspan="4">{if $ticket.type == 'minor'}(minor) {/if}{$ticket.notes}</td>
</tr>
{/foreach}
</tbody>
</table>
<br/>
<div class="interestBox"><a href="/admin/tickets.php?sidebar=1" target="_self">Next page &gt;</a><br/><br/>
		or <a href="/admin/moderation.php?abandon=1" onclick="alert('Please now close the sidebar.');" target="_main">Abandon</a> </div>
<br/>
{else}
  <p>There are no tickets available to moderate at this time, please try again later.</p>
{/if}
{/dynamic}    
</body>
</html>