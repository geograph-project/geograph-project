{dynamic}
{assign var="page_title" value="Editing Calendar #`$calendar.calendar_id`"}
{include file="_std_begin.tpl"}


<h2>Step 2. Create Geograph Calendar for {$calendar.year}</h2>

<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>

<form method=post name=theForm>

<fieldset>
	<legend>Edit Calendar</legend>

<div class="field">
        {if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

        <label for="title">Title:</label>
        <input type="text" name="calendar_title" value="{$calendar.title|escape:"html"}" style="font-size:1.1em" maxlength="40" size="40"/>

	<div class="fieldnotes">Optional title for this calendar</div>

        {if $errors.title}</div>{/if}
</div>

<br>

<div class="field">
        {if $errors.print_title}<div class="formerror"><p class="error">{$errors.print_title}</p>{/if}

        <label for="print_title">Show Title on Cover:</label>
        <input type="checkbox" name="print_title" value="1" {if $calendar.print_title} checked{/if}>

	<div class="fieldnotes"> Show your custom title (above) on front cover - otherwise will just be for reference purposes here</div>

        {if $errors.print_title}</div>{/if}
</div>

<br>

<div class="field">
        {if $errors.background}<div class="formerror"><p class="error">{$errors.background}</p>{/if}

        <label for="show_id">Black Background:</label>
        <input type="checkbox" name="background" id="background" value="1" {if $calendar.background} checked{/if} onclick="setBackAll(this)">

	<div class="fieldnotes">Use a black background around photos (optional) - otherwise will use white+shadow frame</div>

        {if $errors.show_id}</div>{/if}
</div>


</fieldset>

<h3>Selected Images</h3>

<p>The second column is the image shown at the correct aspect ratio, showing how the image will display on the page (with blank areas).
The Cover Image is expanded to fill the page, so will be cropped. {if $min == 0}A preview is shown below, but may be manually tweaked during production.{/if}

{if $min==1}
	<p><button type=button onclick="replaceImage(0)">Specify Cover Image</button> (use this to specify a different image, rather than picking below!)</p>
{/if}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>


<table style="box-sizing: border-box;" cellspacing=0>
	{foreach from=$images key=index item=image}
		<tr class="image{$image->sort_order}">
			<td align=center valign=middle>{$image->getThumbnail(120,120)}</td>
			{if $image->sort_order == 0}
				<td><div style="width:206px;height:147px;background:url({$image->preview_url})  no-repeat center center; background-size:cover;">
				</div></td>
			{else}
				<td class="innerImage"><div style="width:206px;height:147px;border:1px solid gray;padding:2;text-align:center;white-space:nowrap"
				><span style="display: inline-block; height:100%; vertical-align:middle"></span
				><img src="{$image->preview_url}" style="max-width:200px;max-height:141px;display:inline-block;vertical-align: middle;transform: translateZ(0);{if $image->sort_order>0}box-shadow: 1px 1px 4px #999;{/if}"></div></td>
			{/if}
			<td><table>
				<tr><td align=center style=background-color:#e4e4fc;font-size:1.3em>{$image->month}</td>
				<td>
				{if $image->sort_order > $min}<button type=submit name="move[{$image->gridimage_id}]" value="-1">Move Up /\</button>{/if}
				{if $image->sort_order < $max}<button type=submit name="move[{$image->gridimage_id}]" value="1">Move Down \/</button>{/if}
				{if $min == 1}
					<input type=radio name=cover_image value={$image->gridimage_id} id="cover_image{$image->gridimage_id}" {if $calendar.cover_image == $image->gridimage_id} checked{/if} required>
					<label for="cover_image{$image->gridimage_id}">Use as Cover Image</label>
				{/if}
				&nbsp; <button type=button onclick="replaceImage({$image->sort_order})">Replace Image</button>
				</td>
				</tr>
				<tr><th align=right>Title</th>
					<td><input type=text name="title[{$image->gridimage_id}]" value="{$image->title|escape:"html"}" maxlength="120" size="60"/></td>
				<tr><th align=right>Grid Reference</th>
					<td><input type=text name="grid_reference[{$image->gridimage_id}]" value="{$image->grid_reference|escape:"html"}" maxlength="16" size="10"/></td>
				<tr><th align=right>Place</th>
					<td><input type=text name="place[{$image->gridimage_id}]" value="{$image->place|escape:"html"}" maxlength="80" size="47"/></td>
				<tr><th align=right>Credit</th>
					<td><input type=text name="realname[{$image->gridimage_id}]" value="{$image->realname|escape:"html"}" maxlength="128" size="47" readonly disabled/></td>
				<tr><th align=right>Image Taken</th>
					<td><input {if strpos($image->imagetaken,'-00')}type=text{else}type=date{/if} name="imagetaken[{$image->gridimage_id}]" value="{$image->imagetaken|escape:"html"}" maxlength="10" size="10"/><span class=dateformat>(Format: YYYY-MM-DD)</span>
			</table></td>
		</tr>
		<tr class="image{$image->sort_order}">
			<td colspan=3>
				{if $image->width}
				Image is <span style="font-family:verdana">{$image->width}x{$image->height}px</span> and <span{if $image->dpi < 100} style=color:red{/if}>will print at about <b>{$image->dpi}</b> DPI</span>.
				{/if}
				{if $image->user_id == $user->user_id}
					<button type=submit name=upload value={$image->gridimage_id}>Upload {if $image->upload_id}another{/if} larger version</button>
				{/if}
			<hr>
	{/foreach}
</table>

<a href="start.php" {if $back} onclick="history.go(-1);return false"{/if}>&lt; Start again at step 1</a>
<input type=submit name="save" value="Save Changes">

{if $calendar.status == 'new'}
	<input type=submit name="proceed" value="Proceed and Order &gt;">
{else}
	<a href="./">Back to Calendar Home</a>
{/if}

<input type=hidden name=new_position>
<input type=hidden name=new_id>
</form>


<script>
 {literal}$(function() {{/literal}
         setBackAll($('input#background').get(0));
 {literal}});{/literal}

{/dynamic}

{literal}

function setBackAll(that) {
	var color = that.checked?'black':'white';
	var shadow = that.checked?'':'1px 1px 4px #999';
	$('td.innerImage div').css('backgroundColor',color);
	$('td.innerImage img').css('boxShadow',shadow);
}

$(function() {
	var test = document.createElement('input');
	var html5date = true; //on by default, gets disabled if not available
	try {
		test.type = 'date';
		test.value = 'Hello World'; // any not date!
		if (test.type === 'text')
			html5date = false;
		if (test.value === 'Hello World') //if still the string, then its a plain 'text'!
			html5date = false;
	}
	catch(err) {
		html5date = false;
	}

	if (html5date) //the html5 element uses a 'localized' format, not the standard ISO. 
		$('.dateformat').hide();


	$('input[type=text][name*="title"], input[type=text][name*="place"]').each(function() {
		var $this = $(this);
		var len = $this.attr('maxlength');
		var $ele = $('<span style=padding-left:10px;color:gray/>');
		$this.after($ele);
		$this.keyup(function() {
			$ele.text(this.value.length+'/'+len);
			if (this.value.length>len)
				$ele.css({color:'red'});
			else
				$ele.css({color:'green'});
		}).trigger('keyup');
	});

});
//prevent enter submitting the form (which will be an arbitary move button!) 
$('form input').keydown(function (e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
});

function replaceImage(sort) {
	var value = prompt("Please enter the Image ID (or page URL) of the new replacement image. (for position "+sort+")");

	if (value && value.length> 0) {
		var form = document.forms['theForm'];
		form.elements['new_position'].value = sort;
		form.elements['new_id'].value = value;
		form.submit();
	}
}

</script>
<style>
input:checked + label {
	font-weight:bold;
	background-color:yellow;
}
tr.image0 {
	background-color:#eee;
}
tr.image0 td {
	padding:2px;
}

</style>
{/literal}

{include file="_std_end.tpl"}


