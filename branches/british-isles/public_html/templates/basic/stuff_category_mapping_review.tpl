{assign var="page_title" value="Bulk Convertor"}
{include file="_std_begin.tpl"}
{literal}
<style>
.add {
	color:green;
}
.rem {
	color:red;
}
td.add {
        background-color:lightgreen;
}
td.rem {
        background-color:pink;
}

</style>
{/literal}
<h2>Bulk Category --> Context and Tags convertor</h2>


{dynamic}    

<form method="post">

<p>Tick the items in the agree or disagree column, to signify if think the specific change should be made to the master conversion table.</p>

<table class="report sortable" id="catlist" style="font-size:8pt;" cellpadding=4>
<thead>
<tr>
	<td>Category</td>
	<td>Context(s)</td>
	<td>Subject</td>
	<td>Tag(s)</td>
	<td>Agree</td>
	<td>Disagree</td>
	<td colspan=2>Comment</td>
</tr>
<thead>
<tbody>
{assign var="last" value=""}
{foreach from=$suggestions item=row}
	{if $row.imageclass != $last}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		{assign var="last" value=$row.imageclass}
		<tr><td colspan=9 style="background-color:gray;height:3px"></td></tr>
	{/if}
	<tr bgcolor="{$bgcolor}">
		<td><b><a href="/search.php?imageclass={$row.imageclass|escape:'url'}&amp;do=1{if $user_id}&amp;user_id={$user_id}{/if}">{$row.imageclass|escape:'html'}</a></b></td>
		{foreach from=$fields item=field}
			<td class="tags">
				{if $row.field == $field}
					{if $row.action == 'add'}
						<b>Add</b>: <span class=tag>
							<a href="/search.php?tag={$row.value|escape:'url'}" class="taglink add">{$row.value|escape:'html'}</a>
						</span><br/>
					{elseif $row.action == 'remove'}
                                        	<s>Remove</s>: <span class=tag>
                                                	<a href="/search.php?tag={$row.value|escape:'url'}" class="taglink rem">{$row.value|escape:'html'}</a>
	                                        </span><br/>
        	                        {/if}
				{/if}
			</td>
		{/foreach}
		<td align="center" class="add"><input type="radio" name="choice[{$row.change_id}]" value="1"/></td>
		<td align="center" class="rem"><input type="radio" name="choice[{$row.change_id}]" value="-1"/></td>
		<td>{$row.realname|escape:'html'}</td>
		<td>{$row.explanation|escape:'html'}</td>
	</tr>
{/foreach}
</tbody>
</table>
<input type="submit" value="Cast Vote(s)"/>
</form>

{/dynamic}


{include file="_std_end.tpl"}
