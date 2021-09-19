{assign var="page_title" value="Forum Reports"}
{include file="_std_begin.tpl"}

{dynamic}

<form method=get style="float:right">
	Goto:<input type=text name=topic_id placeholder="Enter Topic #ID" size=10 value="{$topic_id|escape:'html'}">
	<input type=submit value=Go>
</form>


<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :: <a href="?">Forum Reports</a></h2>

{if $title=='New or Open reports'}
	<a href=?all=1>View 50 latest reports - regardless of status</a>
{else}
	<a href=?>View current reports</a>
{/if}
<form method="post">

{if $data}
	<h4>{$title}</h4>
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
				<td><a href="/discuss/?action=vthread&forum={$row.forum_id1}&topic={$row.topic_id}"><b>{$row.thread|escape:'html'|default:$row.topic_id}</b></a><small> [{$row.posts_count}]
				{if $topic_id}
					<br><br><a href="/discuss/create-comment-thread.php?topic_id={$row.topic_id}">Thread with Topic Creator</a>
					{if $user_stat[$thread.topic_poster]}
						**
					{/if}
				{else}
					<br>(<a href="?topic_id={$row.topic_id}">view all for topic #<b>{$row.topic_id}</b></a>)</small>
				{/if}</small>
				</td>
			{else}
				<td><s>{$row.thread|escape:'html'}</s><small> (<a href="?topic_id={$row.topic_id}">view all</a>)</small></td>
			{/if}

			{if $row.post1}
				<td style="font-size:0.8em" title="{$row.post_text|escape:'html'}">{if $row.type != 'thread'}<a href="/discuss/?action=vpost&forum={$row.forum_id1}&topic={$row.topic_id}&post={$row.post_id}">{$row.post1|escape:'html'}</a>{/if}
			{else}
				<td style="font-size:0.8em" title="{$row.post_text|escape:'html'}"><s>{if $row.type != 'thread'}{$row.post2|escape:'html'}{/if}</s>
			{/if}
			{if $row.post_id && $topic_id}
				<small><br><br><a href="/discuss/create-comment-thread.php?post_id={$row.post_id}">Thread with Post Creator</a></small>
			{/if}

			<td>{$row.realname|escape:'html'}{if $topic_id}
			<small><br><br><a href="/discuss/create-comment-thread.php?topic_id={$row.topic_id}&amp;user_id={$row.user_id}">Thread with Reporter</a></small>
			{/if}

			<td>{$row.type|escape:'html'}/{$row.resolution|escape:'html'}</td>

				<td><select name="action[{$row.report_id}]">
					<option value=""></option>
					{if $row.forum_id1 || $row.post1}
						<option value="delete_thread">Delete WHOLE THREAD</option>
						{if $row.post1 && $row.posts_count > 1}
							<option value="delete_post">Delete just THIS POST</option>
							{if $row.type eq 'onwards'}
								<option value="delete_onwards">Delete this post AND ALL AFTER</option>
							{/if}
						{/if}
					{/if}
					{if $row.forum_id2 || $row.post2}
						<option value="restore_thread">Restore WHOLE THREAD</option>
						{if $row.post2 && $row.forum_id1}
							<option value="restore_post">Restore just THIS POST</option>
							{if $row.type eq 'onwards'}
								<option value="restore_onwards">Restore this post AND POSTS AFTER</option>
							{/if}
						{/if}
					{/if}
					<option value="">----</option>
					<option value="open">Mark Open (to show you aware)</option>
					<option value="rejected">Mark Report as Invalid</option>
					<option value="delt">Mark Delt/Resolved</option>
				</select></td>
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

	{if $topic_id}
		If (and only if) the thread is completely SPAM:
		<input type="hidden" name="topic_id" value="{$topic_id}"/>		
				<select name="action[new]">
                                        <option value=""></option>
                                        <option value="delete_thread">Delete WHOLE THREAD</option>
                                </select>
		<input type=submit value="Update"/>
	{/if}
{/if}
<br/>
</form>

{if $threads}
	<h3>Current Comment Thread(s) {if $topic_id} for this topic{/if}</h3>
	<ul>
	{foreach from=$threads item=thread}
		<li><a href="/discuss/comment-thread.php?id={$thread.comment_thread_id}">{$thread.title|default:'untitled comment thread'}</a> [{$thread.posts} posts]<br>
			&nbsp; <small>started by {$thread.started_by|escape:'html'} for discussion with <b>{$thread.discussion_with|escape:'html'}</b></small></li>
		{if !$thread.discussion_with}
			{assign var=internal value=1}
		{/if}
	{/foreach}
	</ul>
{/if}

{if $topic_id && !$internal}
	<a href="/discuss/create-comment-thread.php?topic_id={$topic_id}&amp;user_id=0">Create an internal Comment Thread</a> (with no specific user)
{/if}

{if $early}
<p>Note: Data is incomplete prior to 5/1/12 - no log was kept, and deleted threads prior to that date, can't be restored.</p>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
