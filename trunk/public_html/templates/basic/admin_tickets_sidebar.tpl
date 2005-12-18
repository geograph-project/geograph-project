<html>
<head>
<title>Touble Tickets</title>
<script src="/sorttable.js"></script>
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/basic.css" media="screen" />

</head>
<body bgcolor="#ffffff">
<h2>Trouble&nbsp;Tickets</h2>
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
<td><b><a href="/editimage.php?id={$ticket.gridimage_id}&amp;alltickets=1" target="_main">{$ticket.title|default:'Untitled'}</a></b></td>
<td>{$ticket.suggester}</td>
<td>{$ticket.suggested}</td>
<td>{$ticket.submitter}</td>
</tr>
<tr bgcolor="{$bgcolor}">
<td colspan="4">{$ticket.notes}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no new tickets</p>
{/if}
{/dynamic}    
</body>
</html>