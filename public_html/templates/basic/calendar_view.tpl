<fieldset style="background-color:#eee">
	<legend>View Calendar</legend>

{dynamic}
<h3 style=margin-top:0>{$calendar.title|escape:"html"|default:'Untitled Calendar'}</h3>

<b>Quantity: {$calendar.quantity}</b><br>

Calendar Ref:  {$calendar.user_id}{$calendar.alpha}<br>
Delivery Name:  {$calendar.delivery_name|escape:"html"}<br>
Delivery Address:  <i>Hidden</i><br>

</fieldset>
<br>
	{foreach from=$images key=index item=image}
		<table style="box-sizing: border-box;">
			<tr>
			<td align=center valign=top>
				{$image->sort_order} <b>{$image->month}</b><br>
				<a href="/photo/{$image->gridimage_id}" style=vertical-align:middle>{$image->getThumbnail(120,120)}</a>
			</td>
			<td><span style="width:200px;height:141px;border:1px solid black;padding:0;text-align:center;white-space:nowrap;display:inline-block;vertical-align:middle"
				><span style="display: inline-block; height:100%; vertical-align:middle"></span
				><img src="{$image->download}" style="max-width:200px;max-height:141px;display:inline-block;vertical-align: middle;transform: translateZ(0);"></div></td>
		</table>

			<table>
				<tr><th align=right>Title</th>
					<td><input type=text disabled name="title[{$image->gridimage_id}]" value="{$image->title|escape:"html"}" maxlength="128" size="47"/></td>
				<tr><th align=right>Grid Ref</th>
					<td><input type=text disabled name="grid_reference[{$image->gridimage_id}]" value="{$image->grid_reference|escape:"html"}" maxlength="16" size="10"/></td>
				<tr><th align=right>Credit</th>
					<td><input type=text name="realname[{$image->gridimage_id}]" value="{$image->realname|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				{if $image->imagetaken != '0000-00-00'}
					<tr><th align=right>Taken</th>
						<td><input type=text disabled name="imagetaken[{$image->gridimage_id}]" value="{$image->getFormattedTakenDate()|regex_replace:"/^[A-Z][\w ]+, /":''}" size="40"/></td>
				{/if}

			</table>
		<p class=nowrap>Image is {$image->width}x{$image->height}px and will print at about <b>{$image->dpi}</b> DPI.
				{if $image->download}
					<a class=download href="{$image->download|escape:'html'}" data-filename="{$image->filename}">download image</a>
				{/if}</p>
			<hr>
	{/foreach}
{/dynamic}

