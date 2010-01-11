{assign var="page_title" value="User Editor"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>User Editor</h2>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    

{if $i}
	<h3>User Saved</h3>
	<p><a href="/profile/{$i}">View Profile</a></p>
	or 
	<p><a href="/admin/user.php?id={$i}">Edit User again</a></p>
{elseif $id} 


	
	<form action="{$script_name}" method="post">
		<table cellpadding="3" cellspacing="0" style="font-size:0.8em">
		{foreach key=name item=value from=$row name=loop}
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'">
			 <td><b>{$name}</b> {$map.$name}</td>
			 {if $desc.$name.values}
			 	<td><select name="{$name}[]" {$desc.$name.multiple} value="{$value|escape:'html'}">
			 	
			 		{html_options options=$desc.$name.values selected=$value} 
				
			 	</select></td>
			 
			 {elseif $name == 'message_sig'}
			 	<td><textarea name="{$name}" rows="3" cols="60">{$value|escape:'html'}</textarea></td>
			 {elseif $desc.$name.Type == 'text'}
			 	<td><textarea name="{$name}" rows="6" cols="60">{$value|escape:'html'}</textarea></td>
			 {elseif $name == 'password'}
			 	<td><input type="password" name="{$name}" value="{$value|escape:'html'}" size="{$desc.$name.size}" maxlength="{$desc.$name.maxlength}"></td>
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


{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
