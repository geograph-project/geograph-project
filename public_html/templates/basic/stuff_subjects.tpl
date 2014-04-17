{assign var="page_title" value="Subjects"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2>Subjects</h2>

{if $admin}
<p>Where a subject is not 'Offical' there are a couple of ways this can be rectified:</p>
<ol>
	<li>Add it to the offical subject list, by clicking Approve</li>
	<li>Report the tag, so the images can be moved to an offical subject</li>
	<li>Report the tag, so the subject prefix can be removed (to make a standard unprifixed tag)</li>
	<li>Change/correct the category->subject mapping, so only uses (now) offical subjects</li>
</ol>
<p>Also a subject that is used on very few images, is a sign it could removed from the offical list (using the Disapprove), then cleared up as above</p>
<hr/>
{/if}

<p>This subject list is a amalgamation of three lists, a) the offical subject list, b) subject:* tags on images, and c) subjects listed in the <a href="/stuff/category_mapping.php">category->subject mapping</a> database</p>

<table class="report sortable" id="catlist" style="font-size:8pt;" cellpadding=4>
<thead>
<tr>
	<td>Subject</td>
	<td>Offical</td>
	<td>Images (using tag)</td>
	<td>Images (via Category)</td>
	<td>Original Categories</td>
	{if $admin}
		<td>Actions</td>
	{/if}
</tr>
<thead>
<tbody>
{foreach from=$list item=row}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
		<td>{$row.subject|default:$row.tag|escape:'html'}</td>
              	<td>{if $row.subject}Y{/if}</td>
              	<td align="right" sortvalue="{$row.images}">{if $row.images}<a href="/search.php?text=[subject:{$row.subject|default:$row.tag|escape:'url'}]">{$row.images}</a>{else}0{/if}</td>
              	<td align="right" sortvalue="{$row.historic}">{if $row.historic}<a href="/search.php?canonical=.{$row.subject|default:$row.tag|escape:'url'}&amp;do=1">{$row.historic}</a>{else}0{/if}</td>
		<td align="right">{$row.cats}</td>
		{if $admin}
			<td><a href="?admin=1&amp;subject={$row.subject|default:$row.tag|escape:'url'}&amp;{if $row.subject}approve=0">Disapprove{else}approve=1">Approve{/if}</a>
			{if $row.images && $row.tag}&middot; <a href="/tags/report.php?tag=subject:{$row.tag|escape:'url'}">Report Tag</a>{/if}
			{if $row.historic}&middot; <a href="/stuff/category_mapping.php?subject={$row.subject|default:$row.tag|escape:'url'}">View Mapping</a>{/if}
			</td>
		{/if}
	</tr>
{/foreach}
</tbody>
</table>


{include file="_std_end.tpl"}
