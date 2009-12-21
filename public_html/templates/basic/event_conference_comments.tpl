{assign var="page_title" value="Geograph Conference"}
{include file="_std_begin.tpl"}



{dynamic}

<h2>Geograph Conference - Comment Page</h2>





{if $data}

<table class="report sortable" id="events" cellpadding="3">

<tbody>

{foreach from=$data item=item name=names}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		{assign var="this" value="`$item.user_id`|`$item.Last`"}
		{if $this != $last} 
			<tr bgcolor="black">
				<td class="nowrap" colspan="3" style="color:yellow"><b>
				{if $item.realname}
					<a href="/profile/{$item.user_id|escape:'html'}" style="color:yellow">{$item.realname|escape:'html'}</a>
				{else}
					{$item.Name|escape:'html'} {$item.Last|escape:'html'} <small>[{$item.Nickname|escape:'html'}]</small>
				{/if}</b></td>

			</tr>			
		{/if}
		
	<tr bgcolor="{$bgcolor}" id="row{$item.entry_id}">
		<td align="right">{$item.entry_id}</td>
		
		<td class="nowrap">{$item.created|escape:'html'}</td>
		<td>{$item.comment|escape:'html'}</td>
	</tr>
	{assign var="last" value="`$item.user_id`|`$item.Last`"}
{/foreach}
</tbody>
</table>
{else}
  <p>There are no listed items.</p>
{/if}



{/dynamic}


{include file="_std_end.tpl"}

