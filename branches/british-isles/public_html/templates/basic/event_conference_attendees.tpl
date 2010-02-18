{assign var="page_title" value="Geograph Conference"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>



<h2>First Geograph Conference 17th Feb 2010 in Southampton</h2>

<p>&middot; <a href="http://www.ordnancesurvey.co.uk/oswebsite/media/news/2010/feb/geograph.html">Press Release</a> (and Group Photo)</p>

{if $data}

<h4>Attendees</h4>

<table class="report sortable" id="events">
<thead><tr>
	<td>Name</td>
	<td>Nickname</td>
</tr></thead>
<tbody>

{foreach from=$data item=item name=names}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}" id="row{$item.entry_id}">
		<td>{if $item.user_id}<a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>{else}{$item.Name|escape:'html'} {$item.Last|escape:'html'}{/if}</td>
		<td>{if $item.user_id}{$item.nickname|escape:'html'}{else}{$item.Nickname|escape:'html'}{/if}</td>
		{if $item.partipation}<td>{$item.partipation|escape:'html'}</td>{/if}
	</tr>
	
{/foreach}
</tbody>
</table>
{else}
  <p>There are no listed items.</p>
{/if}




{include file="_std_end.tpl"}

