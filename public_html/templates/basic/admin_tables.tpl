{assign var="page_title" value="Database Tables"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>
{dynamic}

<h2>Database Tables</h2>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    

{if $table}
	<form action="{$script_name}" method="post">
	<input type="hidden" name="next" value="{$next}">
	<input type="hidden" name="table" value="{$table}">
		<table cellpadding="3" cellspacing="0" style="font-size:0.8em">
		  <tr>
			 <td><b>table</b></td>
			 <td>{$table}</td>
		  </tr>
		  <tr>
			 <td><b>Type</b></td>
			 <td> 
				{html_radios name="type" options=$types checked=$arr.type}
			 </td>
		  </tr>
		  <tr>
			 <td><b>Backup</b></td>
			 <td>
				{html_radios name="backup" options=$backups checked=$arr.backup}
			 </td>
		  </tr>
		  <tr>
			 <td><b>Sensitive</b></td>
			 <td>
				{html_radios name="sensitive" options=$sensitives checked=$arr.sensitive}
			 </td>
		  </tr>
		  <tr>
			 <td><b>description</b></td>
			 <td><textarea name="description" rows="4" cols="50">{$arr.description}</textarea></td>
		  </tr>
		  <tr>
			 <td><b>public title</b></td>
			 <td><input name="title" size="50" value="{$arr.title}"/></td>
		  </tr>
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Update" name="submit"></td>
		  </tr>
	</table></form>
	
	<a href="{$script_name}">back</a>
{else}
   	
	<table cellpadding="4" cellspacing="0" border="1" class="report sortable" id="reportlist"> 
	<thead>
	<tr><th>Table</th><th>Type</th><th>Backup</th><th>Sensitive</th><th>Rows</th><th>Data Length</th><th>Created</th><th>Updated</th><th>Checked</th><th>Edit</th></tr>
	</thead>
	<tbody>
	{foreach key=table item=row from=$arr name=loop}
		<tr{if $row.skipped} style="color:gray;text-decoration:line-through"{/if}>
		<td><tt>{$table}</td>
		<td><tt>{$row.type}</td>
		<td><tt>{$row.backup|default:'Y'}</td>
		<td><tt>{$row.sensitive|default:'N'}</td>
		<td align=right>{$row.Rows}</td>
		<td align=right>{$row.Data_length}</td>
		<td align=right>{$row.Create_time}</td>
		<td align=right>{$row.Update_time}</td>
		<td align=right>{$row.Check_time}</td>
		<td align=right><a href="{$script_name}?table={$table}">Edit...</a></td>
		</tr>
	{/foreach}
	</tbody>
	</table>
{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
