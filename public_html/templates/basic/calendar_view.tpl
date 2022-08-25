<html>
	<head>
		<title>Ref:{$calendar.user_id}{$calendar.alpha}/{$calendar.year} (Geograph Calendar)</title>
	</head>
	<body>

{dynamic}

<fieldset style="background-color:#eee">
	<legend>View Calendar #{$calendar.calendar_id} Ref:{$calendar.user_id}{$calendar.alpha} /<b>{$calendar.year}</b></legend>

Title: <input type=text size=80 disabled value="{$calendar.title|escape:"html"|default:'Untitled Calendar'}"><br>
 (include on calendar: {if $calendar.print_title}yes{else}NO{/if})

<hr>

<b>Background:</b> {if $calendar.background}black{else}normal{/if}<br>
<b>Custom Quantity: {$calendar.quantity}</b><br>
<b>Best Quantity: {$calendar.best_quantity}</b><br>

Ref:  <b>{$calendar.user_id}{$calendar.alpha}</b><br>
Delivery Name:  {$calendar.delivery_name|escape:"html"}<br>
<hr>
<b>{$message}</b>
</fieldset>
<br>
	{foreach from=$images key=index item=image}
		<table style="box-sizing: border-box;">
			<tr>
			<td align=center valign=top>
				{$image->sort_order} <b style=color:brown>{$image->month}</b><br>
				<a href="/photo/{$image->gridimage_id}" style=vertical-align:middle>{$image->getThumbnail(120,120)}</a>
			</td>
			<td class=innerImage><div style="width:200px;height:141px;border:1px solid black;padding:0;text-align:center;white-space:nowrap;display:inline-block;vertical-align:middle"
				><span style="display: inline-block; height:100%; vertical-align:middle"></span
				><img src="{$image->download}" style="max-width:200px;max-height:141px;display:inline-block;vertical-align: middle;transform: translateZ(0);{if $image->sort_order>0}box-shadow: 1px 1px 4px #999;{/if}"></div></td>
		</table>

			<table>
				<tr><th align=right>Title</th>
					<td><input type=text disabled name="title[{$image->gridimage_id}]" value="{$image->title|escape:"html"}" maxlength="128" size="47"/></td>
				<tr><th align=right>Grid Ref</th>
					<td><input type=text disabled name="grid_reference[{$image->gridimage_id}]" value="{$image->grid_reference|escape:"html"}" maxlength="16" size="10"/></td>
				<tr><th align=right>Credit</th>
					<td><input type=text name="realname[{$image->gridimage_id}]" value="{$image->realname|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				<tr><th align=right>Place</th>
					<td><input type=text name="place[{$image->gridimage_id}]" value="{$image->place|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				<tr><th align=right>Taken</th>
					<td><input type=text disabled name="imagetaken[{$image->gridimage_id}]" value="{if $image->imagetaken && strpos($image->imagetaken,'-00') === false && strpos($image->imagetaken,'0000-') === false}{$image->imagetaken|date_format:"%e %B %Y"}{/if}" size="40"/>{$image->imagetaken}</td>

			</table>
		<p class=nowrap>Image is {$image->width}x{$image->height}px and will print at about <b>{$image->dpi}</b> DPI.
				{if $image->download}
					<a class=download href="{$image->download|escape:'html'}" data-filename="{$image->filename}">download image</a>
				{/if}</p>
			<hr>
	{/foreach}



<table>
	<tr>
		<td>#{$calendar.calendar_id}
		<td>Ref:{$calendar.user_id}{$calendar.alpha} /<b>{$calendar.year}</b>
		<td>{if $calendar.background}black{else}normal{/if}
		<td>
		<td>{if $calendar.print_title}{$calendar.title|escape:"html"}{/if}
	{foreach from=$images key=index item=image}
		<tr>
			<td>{$image->sort_order}
			<td>{$image->month}
			<td><a href="{$image->download}">{$image->download}</a>
			<td>{$image->gridimage_id}
			<td>{$image->title|escape:"html"}
			<td>{$image->grid_reference|escape:"html"}
			<td>{$image->realname|escape:"html"}
			<td>{$image->place|escape:"html"}
			<td>{if $image->imagetaken && strpos($image->imagetaken,'-00') === false && strpos($image->imagetaken,'0000-') === false}{$image->imagetaken|date_format:"%e %B %Y"}{/if}
			<td>{$image->imagetaken}</td>
			<td>{$image->width}x{$image->height}px
		</tr>
	{/foreach}
</table>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
{if $calendar.background}
 {literal}$(function() {{/literal}
         setBackAll(1);
 {literal}});{/literal}
{/if}

{/dynamic}

{literal}

function setBackAll(that) {
        var color = that?'black':'white';
        var shadow = that?'':'1px 1px 4px #999';
        $('td.innerImage div').css('backgroundColor',color);
        $('td.innerImage img').css('boxShadow',shadow);
}
</script>
{/literal}


