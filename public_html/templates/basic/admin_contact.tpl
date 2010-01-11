{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Contact</h2>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    
{if $saved}
	<h3>Saved</h3>
	{if $id}<p><a href="/admin/contact.php?id={$id}">Edit Contact</a></p>{/if}
	<p><a href="/admin/contact.php">Return to list</a></p>
{/if}

{if $inject}
	<form action="{$script_name}?inject" method="post">
	
	<p>Paste WHOLE message direct from Google Mail - automatically extracts variables</p>
	
	<textarea name="msg" rows="20" cols="80">{$value|escape:'html'}</textarea><br/>
		
	<input type="submit" value="Save"/>	
			
	</form>

{elseif $id} 


	<form action="{$script_name}" method="post">
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
	<thead><tr>
		<td>Subject</td>
		<td>Message</td>
		<td style="width:200px">More</td>
	</tr></thead>
	<tbody>
	
	{foreach from=$data item=item}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
	<td><a href="{$script_name}?id={$item.contact_id}">{$item.subject|escape:'html'|default:'no subject'}</a></td>
	<td style="font-size:8pt;" valign="top">{$item.msg|escape:'html'|nl2br}<hr/>
	{if $item.user_id}<a href="/profile/{$item.user_id}">Profile</a>{/if}
	{if $item.referring_page}from:<a href="{$item.referring_page|escape:'html'}">{$item.referring_page|escape:'html'}</a>{/if}
	</td>
	<td style="width:200px" valign="top">{$item.created}<br/>
	Status: {$item.status}<br/>
	
	<form action="{$script_name}?id={$item.contact_id}" method="post">
	{if $item.status eq 'new'}
	<input type=submit name="open" value="open"/>
	{/if}
	{if $item.moderator_id eq 0}
	<input type=submit name="dealing" value="show me dealing"/>
	{else}
	<input type=submit name="close" value="close"/>
	{/if}
	</form>
	</td>
	</tr>
	{/foreach}
	</tbody>
	</table>
{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
