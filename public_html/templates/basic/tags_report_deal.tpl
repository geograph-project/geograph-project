{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>


<form method="post" action="{$script_name}?deal=1">

<table class="report sortable" id="photolist" style="font-size:8pt;">
	<thead>
	<tr>
		<td>Type</td>
		<td>Old</td>
		<td>Original</td>
		<td>Uses</td>
		<td>New?</td>
		<td>Suggestion</td>
		<td>Ignore</td>
		<td>Reject</td>
		<td>Move Images</td>
		<td>Rename Tag</td>
		<td>Set Canonical</td>
	</tr></thead>

{dynamic}
	<tbody>
	{foreach from=$reports item=row}
		<tr>
			<td>{$row.type|escape:'html'}</td>
			<td>{$row.tag_id|escape:'html'}</td>
			<td>{$row.tag|escape:'html'}</td>
			<td align="right">{$row.images|escape:'html'}</td>
			<td>{$row.tag2_id|escape:'html'}</td>
			<td{if strcasecmp($row.tag,$row.tag2) == 0} style="background-color:pink"{/if}>{if $row.tag2}{$row.tag2|escape:'html'}{else}<a href="?tag={$row.tag|escape:'url'}">suggest</a>{/if}</td>
			<td><input type="radio" name="res[{$row.report_id}]" value="" checked></td>
			<td><input type="radio" name="res[{$row.report_id}]" value="reject"></td>
			<td>{if $row.tag2}
				<input type="radio" name="res[{$row.report_id}]" value="move">
			{/if}</td>
			<td>{if (!$row.tag2_id && $row.tag2) || $row.tag2_id eq $row.tag_id}
				<input type="radio" name="res[{$row.report_id}]" value="rename">
			{/if}</td>
			<td>{if $row.tag2_id && !$row.canonical}
				{if $row.type == 'canonical'}
					<input type="radio" name="res[{$row.report_id}]" value="canonical">
				{else}
					<input type="checkbox" name="canon[{$row.report_id}]" value="on" checked>
				{/if}
			{/if}</td>
		</tr>
	{/foreach}
	</tbody>
{/dynamic}

</table>

<input type="submit" name="commit" value="Take selected Action(s)"/>

</form>


	<ul>
		<li>Ignore - Do nothing with this report/suggestion right now</li>
		<li>Reject - reject the suggestion (takes no action other than removing the report)</li>
		<li>Move Images - move all the images to the new tag</li>
		<li>Rename Tag - changes the tag (only avialable if there is no tag for the suggestion)</li>
		<li>Set Canonical - if moving images, also set canonical (to help catch further images)</li>
	</ul>


{include file="_std_end.tpl"}

