{assign var="page_title" value="Cooperative Searching"}
{include file="_std_begin.tpl"}


	<h2><a href="/finder/">Finder</a> :: Cooperative Searching</h2>

{dynamic}
	{if $message}
		<p style="color:red">{$message|escape:"html"}</p>
	{/if}
{/dynamic}



{dynamic}
<div>
	<h3>Geographical Features</h3>

	{if $list}
		<table id="tabl" class="report sortable">
			<thead>
			<tr>
				<td>Title</td>
				<td>Images</td>
				<td>View Images</td>
				<td>Add more images</td>

				<td><small>Report as inappropriate</td>
			</tr>
			</thead>
			<tbody>
			{foreach from=$list item=item}

				<tr>
					<td><b><big>{$item.title|escape:'html'}</big></b></td>
					<td align="right">{$item.images}</td>
					<td>{if $item.images}<b><a href="{$script_name}?id={$item.search_id}&amp;mode=result&geo">View Images</a></b>{/if}</td>
					<td><a href="{$script_name}?id={$item.search_id}&amp;mode=answer&geo">Add more images</a></td>

					<td><small><a href="{$script_name}?id={$item.search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" style="color:red">Report as inappropriate</a></td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{else}
			<li><i>There is no content to display at this time.</i></li>
	{/if}
</div>

<br style="clear:both"/>



	{if $user->registered}
		<div class="interestBox">

			<form method="post" action="{$script_name}?create&geo">
				<h3 style="margin-top:0px">Create new Search</h3>

				Title: <input type="text" name="title" value="{$item.title|escape:"html"}"/>
					<input type="submit" name="create" value="Create"/><br/>
				{if $errors.title}<p class="error">{$errors.title}</p>{/if}
				Keywords: <input type="text" name="q" value="{$item.q|escape:"html"}"/> (optional - if you know good keywords enter here)<br/>
				{if $errors.q}<p class="error">{$errors.q}</p>{/if}
			</form>
		</div>

	{/if}
{/dynamic}


{include file="_std_end.tpl"}
