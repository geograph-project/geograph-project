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
			 {if $desc.$name.values && $desc.$name.multiple}
				<td colspan=2>{foreach item=item from=$desc.$name.values name=inner}
					<label class=nowrap><input type=checkbox name="{$name}[]" value="{$item}" {if in_array($item, $value) !== FALSE}checked{/if}>
						{$item}</label> &nbsp;
				{/foreach}</td>
			 {elseif $desc.$name.values}
			 	<td><select name="{$name}[]" {$desc.$name.multiple} value="{$value|escape:'html'}">
			 	
			 		{html_options options=$desc.$name.values selected=$value} 
				
			 	</select></td>
			 
			 {elseif $name == 'public_about' || $name == 'public_email'}
				<td>
					<input type="hidden" name="{$name}" value="0">
					<input type="checkbox" name="{$name}" value="1" {if $value}checked{/if}>
			 {elseif $name == 'message_sig'}
			 	<td><textarea name="{$name}" rows="3" cols="60">{$value|escape:'html'}</textarea></td>
			 {elseif $desc.$name.Type == 'text'}
			 	<td><textarea name="{$name}" rows="6" cols="60">{$value|escape:'html'}</textarea></td>
			 {elseif $name == 'password'}
			 	<td><input type="password" name="{$name}" value="{$value|escape:'html'}" size="{$desc.$name.size}" maxlength="{$desc.$name.maxlength}"></td>
			 {else}
			 	<td><input type="text" name="{$name}" value="{$value|escape:'html'}" size="{$desc.$name.size}" maxlength="{$desc.$name.maxlength}" {if $name == 'user_id'} readonly{/if}></td>
			 {/if}
			{if $name != 'rights'}
			 <td style=color:silver>{$desc.$name.Type}</td>
			{/if}
		  </tr>
		{/foreach}
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Save" name="submit"></td>
		  </tr>
	</table></form>

{else}
	<form method=get>
		Enter Profile ID: <input type=number name=id size=6> (eg 1234)<br>
		<input type=submit value=continue>
	</form>
{/if}	 
   
    
{if $id && $row.rights}
<hr>
<h3>Reference - in particular reference to Deceased members</h3>
The main fields you need to consider:
<ul>
<li><b>realname, nickname, website, about_yourself</b> are hopefully all clear, can be edited/cleared if need be. </li>

<li><big>But don't leave <b>realname</b> empty - needs to contain something. <u>Changing it changes the credit on existing images</u>.</big>

<li>Sometimes a message is added to <b>about_yourself</b>, sometimes not. Other times its just emptied.

<li>To 'remove' the <b>email</b>, literally just delete it from the box. Empty means we don't sent ticket emails, AND can't be contacted. But also removes the Gravatar icon. To keep the gravatar, just change public_email to 0 and set dormant in rights (leaving the email still present)  

<li><b>deceased_date</b> - enter a date, and the profile shows " site member [signup] till [deceased] "<br>
format, YYYY-MM-DD - but can omit the day, by using 00, eg 2013-03-00

<li><b>rights</b> - is the most tricky. Hold ctrl and click to toggle rights on/off. 

<ul>

  <li><b>basic</b> - this needs to be on, so can login. sometimes, the nok asks us to maintain login, so could get if needed
  (but only set this to on, if the user has a nickname and/or email address still active) 

  <li><b>dormant</b> - set this on, which removes the 'contact the contributor' link. 

  <li><b>alumni</b> - set this on, if they have helped in a offical capcity (their name will show at bottom of the 'team' page) 


  <li>... otherwise, ALL the other rights can be removed once deceased.

</ul>

<li><b>role</b> - another tricky field. it contains a custom label for some users with extra rights. probably dont need to change this, except, if 1) the user was a moderator, and/or 2) there is already a custom label in the box. If either of these true enter exactly Member in the box. This has the effect of adding them to the bottom of the team page. 

</ul>

{/if}
{/dynamic}    


{include file="_std_end.tpl"}
