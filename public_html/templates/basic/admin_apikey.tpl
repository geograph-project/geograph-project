{assign var="page_title" value="API Key"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>API Key Request</h2>
<p>Use this page to request a key to use one of the Geograph APIs...</p>
    
{if $message}
	<div style="border:1px solid red; padding:20px;margin:20px;">{$message}</div>
{/if}
    
	
	<form action="{$script_name}" method="post">
	<input type="hidden" name="id" value="{$id}">
		<table cellpadding="3" cellspacing="0">
		  <tr>
			 <td><b>your name</b></td>
			 <td><input type="name" required name="name" value="{$arr.name|escape:'html'}"></td>
		  </tr>
		  <tr>
			 <td><b>your email</b></td>
			 <td><input type="email" required name="email" value="{$arr.email|escape:'html'}">
				please enter your email so we may contact you</td>
		  </tr>
		  <tr>
			 <td><b>homepage</b></td>
			 <td><input type="text" name="homepage_url" value="{$arr.homepage_url|escape:'html'}">
				optional - please provide a link to your site/project homepage</td>
		  </tr>
		  <tr>
			 <td><b>type</b></td>
			 <td><select name="type" value="{$arr.type}|escape:'html'">
					<option></option>
					<option>commercial project</option>
					<option>non-profit project</option>
					<option>hobby project</option>
					<option>personal use only</option>
					<option>other</option>
				</select></td>
		  </tr>
		  <tr>
			 <td><b>comments</b></td>
			 <td><textarea name="comments" rows="4" cols="50">{$arr.comments}</textarea><br/>
			 please note what you want to use the key for, and expected traffic levels etc</td>
		  </tr>
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Request Key" name="submit"></td>
		  </tr>
	</table></form>

    
{/dynamic}    
{include file="_std_end.tpl"}
