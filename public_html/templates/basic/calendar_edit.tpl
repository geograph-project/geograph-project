{include file="_std_begin.tpl"}


<h2>Step 2. Create Geograph Calendar</h2>

<form method=post>

<fieldset>
	<legend>Edit Calendar</legend>

{dynamic}
<div class="field">
        {if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

        <label for="title">Title:</label>
        <input type="text" name="calendar_title" value="{$calendar.title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">Just for your refernece, not printed on calendar!</div>

        {if $errors.title}</div>{/if}
</div>

</fieldset>

<h3>Selected Images</h3>

<p>The second column is the image shown at the correct aspect ratio, showing how the image will display on the page (with blank areas)

<table style="box-sizing: border-box;">
	{foreach from=$images key=index item=image}
		<tr>
			<td align=center valign=middle>{$image->getThumbnail(120,120)}</td>
			<td><div style="width:206px;height:147px;border:1px solid gray;padding:2;text-align:center;white-space:nowrap"
				><span style="display: inline-block; height:100%; vertical-align:middle"></span
				><img src="{$image->preview_url}" style="max-width:200px;max-height:141px;display:inline-block;vertical-align: middle;transform: translateZ(0);"></div></td>
			<td><b style=color:brown>{$image->month}</b> 
				{if $image->sort_order > $min}<button type=submit name="move[{$image->gridimage_id}]" value="-1">Move Up /\</button>{/if}
				{if $image->sort_order < $max}<button type=submit name="move[{$image->gridimage_id}]" value="1">Move Down \/</button>{/if}
				{if $min == 1}
					<input type=radio name=cover_image value={$image->gridimage_id} id="cover_image{$image->gridimage_id}" {if $calendar.cover_image == $image->gridimage_id} checked{/if}>
					<label for="cover_image{$image->gridimage_id}">Use as Cover Image</label>
				{/if}
				<br>
			<table>
				<tr><th align=right>Title</th>
					<td><input type=text name="title[{$image->gridimage_id}]" value="{$image->title|escape:"html"}" maxlength="80" size="60"/></td>
				<tr><th align=right>Grid Reference</th>
					<td><input type=text name="grid_reference[{$image->gridimage_id}]" value="{$image->grid_reference|escape:"html"}" maxlength="16" size="10"/></td>
				<tr><th align=right>Credit</th>
					<td><input type=text name="realname[{$image->gridimage_id}]" value="{$image->realname|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				<tr><th align=right>Image Taken</th>
					<td><input type=date name="imagetaken[{$image->gridimage_id}]" value="{$image->imagetaken|escape:"html"}" maxlength="10" size="10"/><span class=dateformat>(Format: YYYY-MM-DD)</span></td>

			</table></td>
		</tr>
		<tr>
			<td colspan=3>
				Image is {$image->width}x{$image->height}px and <span{if $image->dpi < 100} style=color:red{/if}>will print at about <b>{$image->dpi}</b> DPI</span>.
				{if $image->user_id == $user->user_id}
				 <a href="upload.php">Upload larger version</a>
				{/if}
			<hr>
	{/foreach}
</table>
{/dynamic}

<input type=submit name="save" value="Save Changes">

<input type=submit name="proceed" value="Proceed and Order">

</form>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>{literal}
$(function() {
	var test = document.createElement('input');
	var html5date = true; //on by default, gets disabled if not available
	test.type = 'date';
	test.value = 'Hello World'; // any not date!
	if (test.type === 'text')
		html5date = false;
	if (test.value === 'Hello World') //if still the string, then its a plain 'text'!
		html5date = false;

	if (html5date) //the html5 element uses a 'localized' format, not the standard ISO. 
		$('.dateformat').hide();


	$('input[name*="title"]').each(function() {
		var $this = $(this);
		var len = $this.attr('maxlength');
		var $ele = $('<span style=padding-left:10px;color:gray/>');
		$this.after($ele);
		$this.keyup(function() {
			$ele.text(this.value.length+'/'+len);
		}).trigger('keyup');
	});

});
</script>
<style>
input:checked + label {
	font-weight:bold;
}
</style>
{/literal}

{include file="_std_end.tpl"}


