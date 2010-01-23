{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Contact</h2>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    

{if $inject}
	{if $saved}
		<h3>Saved</h3>
		<p><a href="/admin/contact.php">Return to list</a></p>
		<hr/>
	{/if}
	
	<form action="{$script_name}?inject" method="post">
	
	<p>Paste WHOLE message direct including headers from the Google Mail webpage - automatically extracts variables</p>
	
	<textarea name="msg" rows="20" cols="80">{$value|escape:'html'}</textarea><br/>
		
	<input type="submit" value="Save"/>	
			
	</form>
		

{elseif $saved}
	<h3>Saved</h3>
	<p><a href="/admin/contact.php?id={$id}">Edit Contact</a></p>
	<p><a href="/admin/contact.php">Return to list</a></p>
{elseif $id} 


	<form action="{$script_name}?id={$row.contact_id}" method="post">
		<table cellpadding="3" cellspacing="0" style="font-size:0.8em">
		{foreach key=name item=value from=$row name=loop}
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'">
			 <td><b>{$name}</b> {$map.$name}</td>
			 {if $desc.$name.values}
			 	<td>
			 	
			 		{html_radios name=$name options=$desc.$name.values selected=$value separator="<br />"} 
				
			 	</td>
			 
			 {elseif $desc.$name.Type == 'varchar(60000)'}
			 	<td><textarea name="{$name}" rows="20" cols="80">{$value|escape:'html'}</textarea></td>
			 {else}
			 	<td><input type="text" name="{$name}" value="{$value|escape:'html'}" size="{$desc.$name.size}" maxlength="{$desc.$name.maxlength}"></td>
			 {/if}
			 <td>{$desc.$name.Type}</td>
		  </tr>
		{/foreach}
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Save" name="submit"></td>
		  </tr>
	</table></form>

{else}
	{if $is_admin}
		<a href="{$script_name}?inject">Inject a old query</a>
	{/if}
	<table class="report sortable" id="newtickets" >
	
	{foreach from=$data item=item}

	<thead><tr>
		<td><a href="{$script_name}?id={$item.contact_id}">{$item.subject|escape:'html'|default:'no subject'}</a></td>
		<td style="width:200px" valign="top">{$item.created}</td>
	</tr></thead>
	<tbody>

	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
	<td style="font-size:8pt;" valign="top">{$item.msg|escape:'html'|nl2br|geographlinks}<hr/>
	{if $item.user_id}<a href="/profile/{$item.user_id}">Profile</a>{/if}
	{if $item.referring_page}from:<a href="{$item.referring_page|escape:'html'}">{$item.referring_page|escape:'html'}</a>{/if}
<br/><br/>
	</td>
	<td style="width:200px" valign="top">
	Status: {$item.status}<br/>
	
	<form action="{$script_name}?id={$item.contact_id}" method="post">
	{if $item.status eq 'new'}
	<input type=submit name="open" value="open"/>
	{/if}
	{if $item.moderator_id eq 0}
	<input type=submit name="dealing" value="show me dealing"/>
	{else}
	by {$item.moderator_id}<br/>
	<input type=submit name="close" value="close"/>
	{/if}
	</form>
	</td>
	</tr>
	</tbody>
	{/foreach}
	</table>
{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
