{include file="_std_begin.tpl"}


{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :: Forum Reports</h2>



{if $data}
	<table class="report sortable" id="newtickets">
	<thead><tr>
		<td>Date</td>
		<td>Topic</td>
		<td>Post</td>
		<td>Reporter</td>
		<td>Resolution</td>
	</tr></thead>
	<tbody>

	{foreach from=$data item=row}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}">
			<td>{$row.created|escape:'html'}</td>
			<td><b>{$row.thread|escape:'html'}</b></td>
			<td style="font-size:0.8em">{$row.post|escape:'html'}</td>
			<td>{$row.realname|escape:'html'}</td>
			<td>{$row.resolution|escape:'html'}</td>
		</tr>
		{if $row.comment}
			<tr bgcolor="{$bgcolor}">
				<td style="font-size:0.8em" colspan="5">{$row.comment|escape:'html'}</td>
			</tr>
		{/if}
	{/foreach}
	</tbody>
	</table>
{/if}
<br/>

{/dynamic}
{include file="_std_end.tpl"}
