{include file="_std_begin.tpl"}


<div class="tabHolder" style="margin-top:3px">
        {foreach from=$links key=key item=value}
	        {if $key == $selected}
			<span class="tabSelected">{$value}</span>
	        {else}
		        <a class="tab nowrap" href="?{$key}">{$value}</a>
		{/if}
        {/foreach}
</div>
<div class="interestBox">
	<h2>Image Selections - by Month Taken</h2>
</div>

<p>This page presents a selection of your images, broken down by the month taken. It's not a requirement that the photos in the calendar be taken in the same month, but it might be a fun target to aim towards</p>
<p>Use the Mark button to note the image. At the bottom see a breakdown of current select (to see if you have selected something for each month)</p>

{dynamic}
<table cellspacing="0" cellpadding="5" border="1" bordercolor=#eee>

{assign var="last" value=""}
{foreach from=$images item=image}
	{if $last != $image->monthname}
		<tr>
			<th>{$image->monthname}</th>
	{/if}
	{assign var="last" value=$image->monthname}


	<td align="center" valign="top" class="shadow">
	        <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
		<br>
		{$image->takenyear} [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
	</td>

{/foreach}
</table>

<a name="stats"></a>
<h3>Marked List Stats</h3>
{if $stats}
	<p>Here is a breakdown of how many images selected for each month</p>
	{foreach from=$stats item=row}
		{$row.monthname} = {$row.images},
	{/foreach}
{else}
	None Selected
{/if}
<p><a href="?{$selected}&#stats" onclick="history.go(0)">Refresh</a> (use after selecting images above) or <a href="/finder/marked.php">View seperately</a></p>

{if $marked_count && $marked_count>=12}
	<a href="order.php">Proceed to Calendar Order Process</a>
{/if}

{/dynamic}



	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>


{include file="_std_end.tpl"}
