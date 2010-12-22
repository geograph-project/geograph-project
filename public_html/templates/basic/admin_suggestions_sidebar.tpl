<html>
<head>
<title>Change Suggestion</title>
<script src="{"/sorttable.js"|revision}"></script>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />

</head>
<body bgcolor="#ffffff">
<h2>>Change&nbsp;Suggestion</h2>

{literal}<script type="text/javascript">
	setTimeout('window.location.href="/admin/";',1000*60*45);
</script>{/literal}

{dynamic}

<h3>{$title}</h3>

    <form method="get" action="{$script_name}">
    <p> 
    <input type="hidden" name="sidebar" value="1"/>
   When:<select name="modifer">
    	{html_options options=$modifers selected=$modifer}
    </select> <br/>
    Type:<select name="type">
    	{html_options options=$types selected=$type}
    </select> <br/>
    Your:<select name="theme">
    	{html_options options=$themes selected=$theme}
    </select> <br/>
    Contributor:<select name="variation">
    	{html_options options=$variations selected=$variation}
    </select><br/>
    <label for="defer">Include Deferred?</label><input type="checkbox" name="defer" id="defer" {if $defer} checked{/if}/> &nbsp;
     <label for="minor">Minor</label><input type="checkbox" name="i" id="minor" {if $minor} checked{/if}/> &nbsp;
     <label for="major">Major</label><input type="checkbox" name="a" id="major" {if $major} checked{/if}/> &nbsp;
    <input type="submit" name="Submit" value="Go"/></p></form>

{if $newtickets}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	{if $col_moderator}<td>Moderator</td>{/if}
	<td>Title</td>
	<td>Suggested by</td>
	<td>Submitted</td>
	<td>Contributor</td>
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}" {if !$ticket.available} style="color:red"{/if}>
{if $col_moderator}<td>{$ticket.moderator}</td>{/if}
<td><b><a href="/editimage.php?id={$ticket.gridimage_id}" target="_main">{$ticket.title|default:'Untitled'}</a></b></td>
<td>{$ticket.suggester}{if $ticket.suggester_comment}<img src="http://{$static_host}/img/star-light.png" width="14" height="14" title="Comment: {$ticket.suggester_comment}"/>{/if}</td>
<td>{$ticket.suggested}</td>
<td>{$ticket.submitter}{if $ticket.submitter_comment}<img src="http://{$static_host}/img/star-light.png" width="14" height="14" title="Comment: {$ticket.submitter_comment}"/>{/if}</td>
</tr>
<tr bgcolor="{$bgcolor}">
<td colspan="4">{if $ticket.type == 'minor'}(minor) {/if}{$ticket.notes|escape:'html'|geographlinks}</td>
</tr>
{/foreach}
</tbody>
</table>
<br/>
<div class="interestBox"><a href="/admin/suggestions.php?{$query_string}" target="_self">Next page &gt;</a><br/><br/>
		or <a href="/admin/moderation.php?abandon=1" onclick="alert('Please now close the sidebar.');" target="_main">Abandon</a> </div>
<br/>
{else}
  <p>There are no suggestions available to moderate at this time, please try again later.</p>
{/if}
{/dynamic}    
</body>
</html>