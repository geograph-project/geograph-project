{assign var="page_title" value="Geograph Conference"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


{dynamic}

<h2>Geograph Conference - Admin Page</h2>

<p>| <b><a href="?action=listall">List all registrants</a></b> |
<a href="?action=viewcomments">View all Comments</a> |
<a href="http://geograph.wufoo.com/admin">Pre-registrants</a>(wufoo) [<a href="http://geograph.wufoo.com/forms/geograph-conference-preregistration/">Form</a>] | 
<a href="http://spreadsheets.google.com/ccc?key=0Ah1uJBkZsxp0dDI2aTE3RVBONWtVWWtxT3NGWGhqVEE&hl=en">Call for talks</a>(GDocs) 
   [<a href="http://spreadsheets.google.com/viewform?hl=en&formkey=dDI2aTE3RVBONWtVWWtxT3NGWGhqVEE6MA">Form</a>] |
<a href="http://spreadsheets.google.com/ccc?key=0Ah1uJBkZsxp0dGVEQVZkaEZUNXE3cERkZXk3Y2RYQ3c&hl=en">Testimonies</a>(GDocs) 
   [<a href="http://spreadsheets.google.com/viewform?hl=en&formkey=dGVEQVZkaEZUNXE3cERkZXk3Y2RYQ3c6MA">Form</a>] |</p>



{if $data}

<table class="report sortable" id="events">
<thead><tr>
	<td sorted="asc">ID</td>
	<td>Name</td>
	<td>Nickname</td>
	<td>Speaking</td>
	<td>Confirmed</td>
	{if $cancelled}
	<td>Cancelled</td>
	{/if}
	<td>Emailed</td>
	<td>Emailed2</td>
	<td>Sent Call</td>
	<td>Comments?</td>
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
		{if $cancelled}
		<td sortvalue="{$item.cancelled}" class="nowrap">{if $item.cancelled > 0}{$item.cancelled|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		{/if}
		<td sortvalue="{$item.emailed}" class="nowrap">{if $item.emailed > 0}{$item.emailed|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		<td sortvalue="{$item.emailed2}" class="nowrap">{if $item.emailed2 > 0}{$item.emailed2|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		<td sortvalue="{$item.sentspeaker}" class="nowrap">{if $item.sentspeaker > 0}{$item.sentspeaker|date_format:"%a, %e %b %Y"}{else}-{/if}</td>
		<td sortvalue="{$item.comments}">{if $item.comments > 0}<a href="?action=viewcomments&amp;entry_id={$item.entry_id}">{$item.comments}</a>{else}-{/if}</td>
		
	</tr>
	
{/foreach}
</tbody>
<tfoot><tr class="totalrow sortbottom">
	<td>Total</td>
	<td>{$total.Name}</td>
	<td>.</td>
	<td>{$total.Speaking}</td>
	<td>{$total.Confirmed}</td>
	{if $cancelled}
	<td>{$total.Cancelled}</td>
	{/if}
	<td>{$total.Emailed}</td>
	<td>{$total.Emailed2}</td>
	<td>{$total.Sentspeaker}</td>
</tr></tfoot>
</table>
{else}
  <p>There are no listed items.</p>
{/if}



{/dynamic}


{include file="_std_end.tpl"}

