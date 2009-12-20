{assign var="page_title" value="Geograph Conference"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


{dynamic}

<h2>Geograph Conference - Admin Page</h2>





{if $data}

<table class="report sortable" id="events">
<thead><tr>
	<td sorted="asc">ID</td>
	<td>Name</td>
	<td>Nickname</td>
	<td>Speaking</td>
	<td>Confirmed</td>
	<td>Cancelled</td>
	<td>Emailed</td>
</tr></thead>
<tbody>

{foreach from=$data item=item name=names}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}" id="row{$item.entry_id}">
		<td align="right">{$item.entry_id}</td>
		<td><a href="mailto:{$item.Email|escape:'html'}">{$item.Name|escape:'html'} {$item.Last|escape:'html'}</a></td>
		<td>{$item.Nickname|escape:'html'}</td>
		<td>{$item.Speaking|escape:'html'}</td>
		<td sortvalue="{$item.confirmed}" class="nowrap">{if $item.confirmed > 0}{$item.confirmed|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		<td sortvalue="{$item.cancelled}" class="nowrap">{if $item.cancelled > 0}{$item.cancelled|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		<td sortvalue="{$item.emailed}" class="nowrap">{if $item.emailed > 0}{$item.emailed|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
	</tr>
	
{/foreach}
</tbody>
<tfoot><tr class="totalrow sortbottom">
	<td>Total</td>
	<td>{$total.Name}</td>
	<td>.</td>
	<td>{$total.Speaking}</td>
	<td>{$total.Confirmed}</td>
	<td>{$total.Cancelled}</td>
	<td>{$total.Emailed}</td>
</tr></tfoot>
</table>
{else}
  <p>There are no listed items.</p>
{/if}



{/dynamic}


{include file="_std_end.tpl"}

