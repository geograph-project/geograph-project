{include file="_basic_begin.tpl"}

<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<input type="hidden" name="inner" value="1"/>
{dynamic}
{if $container}
	<input type="hidden" name="container" value="{$container|escape:'html'}"/>
{/if}

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $step eq 0}
	<div class="interestBox" style="float:right;width:160px;">&middot; {$data|@count} images pending submission<br/>&middot; <a href="/submit-multi.php" target="_top">Upload a batch of images</a><br/>
			&middot; <a href="/submit2.php?inner&amp;step=1&amp;container={$container|escape:'html'}" targer="_self">Upload a single image</a></div>

	{if $one && $item}
			

				<a href="{$script_name}?inner&amp;step=0&amp;one=1&amp;delete={$item.transfer_id}{if $container}&amp;container={$container|escape:'url'}{/if}#sort=Uploaded%A0%A0%u2193" onclick="return confirm('Are you sure?');" style="color:red" targer="_self">Delete this image</a>
				<a href="{$script_name}?inner&amp;step=1&amp;transfer_id={$item.transfer_id}{if $container}&amp;container={$container|escape:'url'}{/if}">Submit this image &gt;</a>
				<br>
				Uploaded: {$item.uploaded|date_format:"%a, %e %b %Y at %H:%M"} 
				{if $item.imagetaken}&nbsp; Taken: {$item.imagetaken|date_format:"%a, %e %b %Y at %H:%M"}{/if}<br>
		
				<a href="/submit.php?preview={$item.transfer_id}" target="_blank"><img src="/submit.php?preview={$item.transfer_id}"/></a>
			

	{else}
		<table id="upload" class="report sortable">
			<thead>
			<tr>
				<td>Preview</td>
				<td>Continue</td>
				<td>Uploaded</td>
				<td>Taken</td>
				<td>Delete?</td>
			</tr>
			</thead>
			<tbody>

			{foreach from=$data item=item}
				<tr>
					<td><a href="/submit.php?preview={$item.transfer_id}" target="_blank"><img src="/submit.php?preview={$item.transfer_id}" width="160"/></a></td>
					<td><a href="{$script_name}?inner&amp;step=1&amp;transfer_id={$item.transfer_id}{if $container}&amp;container={$container|escape:'url'}{/if}">continue &gt;</a></td>
					<td sortvalue="{$item.uploaded}">{$item.uploaded|date_format:"%a, %e %b %Y at %H:%M"}</td>
					<td sortvalue="{$item.imagetaken}">{if $item.imagetaken}{$item.imagetaken|date_format:"%a, %e %b %Y at %H:%M"}{/if}</td>
					<td><a href="{$script_name}?inner&amp;step=0&amp;delete={$item.transfer_id}{if $container}&amp;container={$container|escape:'url'}{/if}#sort=Uploaded%A0%A0%u2193" onclick="return confirm('Are you sure?');" style="color:red" targer="_self">Delete</a></td>
				</tr>
			{foreachelse}
				<tr><td colspan="4">No images yet. <a href="/submit-multi.php" target="_top">Upload some now!</a></td></tr>
			{/foreach}
			</tbody>
		</table>

		<script src="{"/sorttable.js"|revision}"></script>
	{/if}

{elseif $step eq 1}

	{if $success}
		{if $grid_reference}
			<input type="hidden" name="grid_reference" value="{$grid_reference|escape:'html'}"/>
		{/if}
		{if $photographer_gridref}
			<input type="hidden" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}"/>
		{/if}
		{if $upload_id}
			<input type="hidden" name="upload_id" value="{$upload_id|escape:'html'}"/>
		{/if}
		{if $imagetaken}
			<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
		{/if}

		{if $rotation_warning}
			<div id="rotation_warning" style="background-color:yellow;border:2px solid red;margin:10px;padding:10px">
				<div style="float:left;font-size:3em;color:red;padding-right:10px">&#9888;</div>
				Warning: <b>This image has EXIF 'Orientation' flag set.</b> 
				It's highly recommended to use the rotation function to reorientate the image, this resets the flag which prevents potential display issues, as not all Browsers etc will honor the flag.<br><br>
				So please rotate the image, even if it actully displays <i>correctly</i> in the preview! Rotate it sideways, and then <i>back</i> until displays correctly again.<br>Your browser might be ignoring the flag which is why the preview appears ok to you!<br><br>
				<i>The rotation buttons are the arrows above the preview image bottom left.</i>
			</div>
		{/if}

		{if $original_width}

			{include file="_submit_sizes.tpl"}

		{elseif $preview_url}
			<h2>Image Uploaded</h2>
			<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
		{/if}

		<p>Is this the wrong image? <a href="/submit2.php?inner&amp;step=1{if $container}&amp;container={$container|escape:'url'}{/if}">Upload a new image</a> 
		or <a href="/submit2.php?inner&amp;step=0{if $container}&amp;container={$container|escape:'url'}{/if}#sort=Uploaded%A0%A0%u2193">Select an different uploaded image</a>
		(<a href="/submit2.php?inner&amp;step=0&amp;one=1{if $container}&amp;container={$container|escape:'url'}{/if}">Submit one uploaded image</a>)
		</p>
	{else}
		<div class="interestBox" style="float:right;width:200px;">&middot; <a href="/submit-multi.php" target="_top">Upload multiple images</a><br/>
		&middot; <a href="/submit2.php?inner&amp;step=0{if $container}&amp;container={$container|escape:'url'}{/if}#sort=Uploaded%A0%A0%u2193">Select an uploaded image</a><br> 
		&middot; <a href="/submit2.php?inner&amp;step=0&amp;one=1{if $container}&amp;container={$container|escape:'url'}{/if}">Submit an uploaded image</a> </div>
		{if $error}
			<p style="color:#990000;font-weight:bold;">{$error}</p>
		{/if}

	{if $filepicker}

		<input type="filepicker-dragdrop" id="jpeg_url" name="jpeg_url" data-fp-apikey="AWbx7KpSUTJ-4fLh3i4TEz" data-fp-option-container="modal" data-fp-option-maxsize="8192000" data-fp-option-services="BOX,COMPUTER,DROPBOX,FACEBOOK,GITHUB,GOOGLE_DRIVE,FLICKR,GMAIL,INSTAGRAM" onchange="this.value = event.files[0].url;">
		<div>
		<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px;font-size:1.2em" /> (while file is sending can continue on the steps below)<br/>
		</div>

	{else}
		<div><label for="jpeg_exif"><b>Select Image file to upload</b></label> - (upload photos larger than 640px - upto {if $small_upload}<b>5Mb</b>{else}8Mb{/if} filesize <a href="/article/Larger-Uploads-Information" class="about" target="_blank">About</a>)<br/>
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60" style="background-color:white" accept="image/jpeg"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000"/></div>
		<div>
		<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px;font-size:1.2em" onclick="return check_jpeg(this.form.jpeg_exif)"/> (while file is sending can continue on the steps below)<br/>
		</div>

	{literal}<script>
        document.getElementById("jpeg_exif").onchange = function(e) {
            var file = e.target.files[0];
            if (file && file.size && file.size > 8192000) {
                alert('File appears to be '+file.size+' bytes, which is too big for final submission. Please downsize the image to be under 8 Megabytes.');
	    } else if (file && file.type && file.type != "image/jpeg") {
                alert('File appears to not be a JPEG image. We only accept .jpg files');
            } else if (file && file.size && file.size < 10000) {
		alert('File appears to be '+file.size+' bytes, which is rather small. Please check selected right image.');
            }
        }
	</script>{/literal}


	{/if}

		<br/>
		<div><i>Optionally</i> upload an image with Locational information attached <a href="/article/Uploading-Tagged-Images" class="about" target="_blank">About</a><br/>
		<ul>
			<li>GPS-EXIF tags based on WGS84 Lat/Long (used for the Camera Position)</li>
			<li>Subject grid-reference from the name of the file (eg "<tt>photo-<b style="padding:1px">TQ435646</b>A.jpg</tt>")</li>
			<li>Subject grid-reference in EXIF Comment tag</li>
		</ul></div>
	{/if}

{elseif $step eq 2}

	<input type="hidden" name="grid_reference" value="{$grid_reference|escape:'html'}"/>
	<h2>Location found</h2>
{/if}


{/dynamic}
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>
<script type="text/javascript">
{literal}
function check_jpeg(ele) {
	if (ele && ele.value && ele.value.length > 0 && !ele.value.match(/.jpe?g$/i)) {
		return confirm("The name of the file does not appear to have a .jpg extension. Note, we only accept JPEG images. To upload anyway, press OK. To select a different file click Cancel");
	}
}
{/literal}
{dynamic}
	{literal}
	AttachEvent(window,'load', function() {
	{/literal}
{if $success}
		parentUpdateVariables();
		window.parent.doneStep({$step},{if $original_width || $rotation_warning}true{else}false{/if});

	{if $grid_reference}
		window.parent.doneStep(9);
		window.parent.clicker(2,1);
	{/if}

        {if $original_width || $rotation_warning}
		//click back on step 1, on tabs it moves focus back to 1; on non-tabs, it just makes sure 1 stays open
                window.parent.clicker(1,1);
	{/if}

	{if $imagetaken}
		window.parent.setTakenDate('{$imagetaken}');
	{/if}
	{if $preview_url}
		window.parent.showPreview('{$preview_url}',{$preview_width},{$preview_height},'{$filename|escape:'javascript'}');
	{/if}
{/if}
		setTimeout("setupTheForm()",100);

	{if $container}
		var FramePageHeight = document.body.offsetHeight + 10;
		window.parent.document.getElementById('{$container|escape:'javascript'}').style.height=FramePageHeight+'px';
	{/if}

	{literal}
	}, false);
	{/literal}

</script>
</form>


        {if $filepicker}
                <script type="text/javascript" src="//api.filepicker.io/v0/filepicker.js"></script>
		<script type="text/javascript">
	        {literal}
		function setupFilePicker() {
			filepicker.constructWidget(document.getElementById('jpeg_url'));
		}
		AttachEvent(window,'load',setupFilePicker,false);
	        {/literal}
		</script>
	{/if}

{/dynamic}

</body>
</html>
