{assign var="page_title" value="API Key Management"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>API Key Management</h2>
<p>Use this page to setup who has access to the API (for example the export csv page).</p>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    


{if $id} 
	
	<form action="{$script_name}" method="post">
	<input type="hidden" name="id" value="{$id}">
		<table cellpadding="3" cellspacing="0" style="font-size:0.8em">
		  <tr>
			 <td><b>key</b></td>
			 <td><input type="text" name="apikey" value="{$arr.apikey}"> a-z 0-9 and
				underscore only (case sensitive)</td>
		  </tr>
		  <tr>
			 <td><b>IP</b></td>
			 <td><input type="text" name="ip_text" value="{$arr.ip_text}">   key must
				connect from this IP, blank for unrestricted</td>
		  </tr>
		  <tr>
			 <td><b>homepage url</b></td>
			 <td><input type="text" name="homepage_url" value="{$arr.homepage_url}">
				optional - could be used to provide a page listing users of the api</td>
		  </tr>
		  <tr>
			 <td><b>comments</b></td>
			 <td>admin only comments about the use of this key<br><textarea name="comments" rows="4" cols="50">{$arr.comments}</textarea></td>
		  </tr>
		  <tr>
			 <td><b>enabled</b></td>
			 <td>Y<input type="radio" name="enabled" value="Y"{if $arr.enabled == 'Y'} checked="checked"{/if}>&nbsp; N<input type="radio" name="enabled" value="N"{if $arr.enabled == 'N'} checked="checked"{/if}></td>
		  </tr>
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Update" name="submit"></td>
		  </tr>
	</table></form>

	<table cellpadding="4" cellspacing="0" border="1"> 
	<tr><th>Accesses</th><th>Records</th><th>Average</th></tr>
		<tr>
		<td align=right>{$arr.accesses}</td>
		<td align=right>{$arr.records}</td>
		<td align=right>{if $arr.accesses}{$arr.records/$arr.accesses|string_format:"%.2f"}{else}0{/if}</td>
		</tr>
	</table>
{else}
	<p><a href="{$script_name}?id=-new-">Add New Key</a></p>
   	
	<table cellpadding="4" cellspacing="0" border="1"> 
	<tr><th>Key</th><th>Accesses</th><th>Records</th><th>Average</th><th>Enabled</th><th>Edit</th></tr>
	{foreach key=id item=i from=$arr name=loop}
		<tr>
		<td>{if $i.homepage_url}<a href="{$i.homepage_url}">{/if}{$i.apikey}</a></td>
		<td align=right>{$i.accesses}</td>
		<td align=right>{$i.records}</td>
		<td align=right>{if $i.accesses}{$i.records/$i.accesses|string_format:"%.2f"}{else}0{/if}</td>
		<td align=center>{$i.enabled}</td>
		<td align=right><a href="apikeys.php?id={$id}">Edit...</a></td>
		</tr>
	{/foreach}
	</table>
{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
