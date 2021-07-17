{include file="_std_begin.tpl"}


<form method=post>

<fieldset style="background-color:#eee">
	<legend>View Calendar</legend>

{dynamic}
<h3>{$calendar.title|escape:"html"|default:'Untitled Calendar'}</h3>

<b>Quantity: {$calendar.quantity}</b><br>

Profile ID:  {$calendar.user_id}<br>
Delivery Name:  {$calendar.delivery_name|escape:"html"}<br>
Delivery Address:  <i>Hidden</i><br>

</fieldset>

<h3>Selected Images</h3>
<table style="box-sizing: border-box;">

	{foreach from=$images key=index item=image}
		<tr>
			<td align=center valign=middle>{$image->getThumbnail(120,120)}</td>
			<td><div style="width:200px;height:141px;border:1px solid black;padding:0;text-align:center;white-space:nowrap"
				><span style="display: inline-block; height:100%; vertical-align:middle"></span
				><img src="{$image->preview_url}" style="max-width:200px;max-height:141px;display:inline-block;vertical-align: middle"></div></td>
			<td>{$image->month}<br><table>
				<tr><th align=right>Title</th>
					<td><input type=text disabled name="title[{$image->gridimage_id}]" value="{$image->title|escape:"html"}" maxlength="128" size="47"/></td>
				<tr><th align=right>Grid Reference</th>
					<td><input type=text disabled name="grid_reference[{$image->gridimage_id}]" value="{$image->grid_reference|escape:"html"}" maxlength="16" size="10"/></td>
				<tr><th align=right>Credit</th>
					<td><input type=text name="realname[{$image->gridimage_id}]" value="{$image->realname|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				{if $image->imagetaken != '0000-00-00'}
					<tr><th align=right>Image Taken</th>
						<td><input type=text disabled name="imagetaken[{$image->gridimage_id}]" value="{$image->imagetaken|escape:"html"}" maxlength="10" size="10"/></td>
				{/if}

			</table></td>
		</tr>
		<tr>
			<td colspan=3>
				Image is {$image->width}x{$image->height}px and will print at about <b>{$image->dpi}</b> DPI.
			<hr>
	{/foreach}
</table>
{/dynamic}

</form>


{include file="_std_end.tpl"}


