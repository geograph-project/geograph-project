{assign var="page_title" value="Forum Reports"}
{include file="_std_begin.tpl"}


{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :: <a href="?">Forum Reports</a></h2>



{if $data}
	<style>{literal}
		.report td.small {
			border-top:1px solid silver;
			font-size:0.8em;
			padding-left:20px;
		}
		.report td.div {
			background-color:brown;
		}
	</style>{/literal}
	<form method="post">
	
	<table class="report">
	<thead><tr>
		<td>Date</td>
		<td>Topic</td>
		<td>Post</td>
		<td>Reporter</td>
		<td>Resolution</td>
		<td>Action</td>
	</tr></thead>
	<tbody>

	{foreach from=$data item=row}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}">
			<td>{$row.created|escape:'html'}</td>
			{if $row.forum_id1}
				<td><a href="/discuss/?action=vthread&forum={$row.forum_id1}&topic={$row.topic_id}"><b>{$row.thread|escape:'html'|default:$row.topic_id}</b></a><small> (<a href="?topic_id={$row.topic_id}">all</a>)</small></td>
			{else}
				<td><s>{$row.thread|escape:'html'}</s><small> (<a href="?topic_id={$row.topic_id}">all</a>)</small></td>
			{/if}
			{if $row.post1}
				<td style="font-size:0.8em" title="{$row.post_text|escape:'html'}">{if $row.type != 'thread'}<a href="/discuss/?action=vpost&forum={$row.forum_id1}&topic={$row.topic_id}&post={$row.post_id}">{$row.post1|escape:'html'}</a>{/if}</td>
			{else}
				<td style="font-size:0.8em" title="{$row.post_text|escape:'html'}"><s>{if $row.type != 'thread'}{$row.post2|escape:'html'}{/if}</s></td>
			{/if}
			<td>{$row.realname|escape:'html'}</td>
			<td>{$row.type|escape:'html'}/{$row.resolution|escape:'html'}</td>
			{if $row.user_id != $user->user_id}
				<td><select name="action[{$row.report_id}]">
					<option value=""></option>
					{if $row.forum_id1}
						<option value="delete_thread">Delete WHOLE THREAD</option>
						{if $row.post}
							<option value="delete_post">Delete just THIS POST</option>
							{if $row.type eq 'onwards'}
								<option value="delete_onwards">Delete this post AND ALL AFTER</option>
							{/if}
						{/if}
					{/if}
					{if $row.forum_id2}
						<option value="restore_thread">Restore WHOLE THREAD</option>
						{if $row.post}
							<option value="restore_post">Restore just THIS POST</option>
							{if $row.type eq 'onwards'}
								<option value="restore_onwards">Restore this post AND POSTS AFTER</option>
							{/if}
						{/if}
					{/if}
					<option value="">----</option>
					<option value="open">Mark Open</option>
					<option value="rejected">Mark Rejected</option>
					<option value="delt">Mark Delt</option>
				</select></td>
			{/if}
		</tr>
		{if $row.comment}
			<tr bgcolor="{$bgcolor}">
				<td class="small" colspan="6">{$row.comment|escape:'html'}</td>
			</tr>
		{/if}
		{foreach from=$logs item=log}
			{if $log.report_id eq $row.report_id}
				<tr bgcolor="{$bgcolor}">
					<td class="small" colspan="6">ACTION: <b>{$log.action|escape:'html'}</b> by <a href="/profile/{$log.user_id}">{$log.realname|escape:'html'|default:'system'}</a> @{$log.created|escape:'html'}</td>
				</tr>
			{/if}
		{/foreach}
		<tr bgcolor="{$bgcolor}">
			<td class="div" colspan="6"></td>
		</tr>
	{/foreach}
	</tbody>
	<tr>
		<td colspan="6" align="right"><input type=submit value="Update"/></td>
	</tr>
	</table>
	
	
	
	</form>
{else}
	<p>No data to show...</p>	
{/if}
<br/>


<p>Note: Data is incomplete prior to 5/1/12 - no log was kept, and deleted threads can't be restored.</p>

{/dynamic}
{include file="_std_end.tpl"}
