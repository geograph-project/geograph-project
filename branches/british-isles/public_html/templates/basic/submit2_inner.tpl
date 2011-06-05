{include file="_basic_begin.tpl"}

<form enctype="multipart/form-data" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<input type="hidden" name="inner" value="1"/>
{dynamic}

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $step eq 0}
	<div class="interestBox" style="float:right;width:160px;">&middot; <a href="/submit-multi.php" target="_top">Upload a batch of images</a></div>

		<table id="upload" class="report sortable">
			<thead>
			<tr style="color:yellow">
				<th>Preview</th>
				<th>Continue</th>
				<th>Uploaded</th>
				<th>Taken</th>
			</tr>
			</thead>
			<tbody>

			{foreach from=$data item=item}
				<tr>
					<td><a href="/submit.php?preview={$item.transfer_id}" target="_blank"><img src="/submit.php?preview={$item.transfer_id}" width="160"/></a></td>
					<td><a href="/submit2.php?inner&amp;step=1&amp;transfer_id={$item.transfer_id}">continue &gt;</a></td>
					<td sortvalue="{$item.uploaded}">{$item.uploaded|date_format:"%a, %e %b %Y at %H:%M"}</td>
					<td sortvalue="{$item.imagetaken}">{if $item.imagetaken}{$item.imagetaken|date_format:"%a, %e %b %Y at %H:%M"}{/if}</td>
				</tr>
			{foreachelse}
				<tr><td colspan="4">No images yet. <a href="/submit-multi.php" target="_top">Upload some now!</a></td></tr>
			{/foreach}
			</tbody>
		</table>

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

		{if $original_width}

			{include file="_submit_sizes.tpl"}

		{elseif $preview_url}
			<h2>Image Uploaded</h2>
			<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
		{/if}

		<p>Is this the wrong image? <a href="/submit2.php?inner&amp;step=1">Upload a new image</a> or <a href="/submit2.php?inner&amp;step=0">Select an different uploaded image</a></p>
	{else}
		<div class="interestBox" style="float:right;width:200px;">&middot; <a href="/submit-multi.php" target="_top">Upload multiple images</a><br/>&middot;  <a href="/submit2.php?inner&amp;step=0">Select an uploaded image</a> <span style="color:red">New!</span></div>
		{if $error}
			<p style="color:#990000;font-weight:bold;">{$error}</p>
		{/if}
		<div><label for="jpeg_exif"><b>Select Image file to upload</b></label> - (upload photos larger than 640px - upto 8Mb filesize <a href="/article/Larger-Uploads-Information" class="about" target="_blank">About</a>)<br/>
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60" style="background-color:white" accept="image/jpeg"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000"/></div>
		<div>
		<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px;font-size:1.2em"/> (while file is sending can continue on the steps below)<br/>
		</div>

		<br/>
		<div><i>Optionally</i> upload an image with Locational information attached <a href="/article/Uploading-Tagged-Images" class="about" target="_blank">About</a><br/>
		<ul>
			<li>GPS-EXIF tags based on WGS84 Lat/Long (used for the Photographer Position)</li>
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
{dynamic}
{if $success}
	{literal}
		AttachEvent(window,'load',parentUpdateVariables,false);
		AttachEvent(window,'load',function() { window.parent.doneStep({/literal}{$step},{if $original_width}true{else}false{/if}{literal}) },false);
	{/literal}
	{if $grid_reference}
		{literal}
			AttachEvent(window,'load',function() { window.parent.doneStep(9) },false);
			AttachEvent(window,'load',function() { window.parent.clicker(2,1) },false);
		{/literal}
	{else}
		{literal}
			//AttachEvent(window,'load',function() { window.parent.clicker(2,1) },false);
		{/literal}
	{/if}
	{if $imagetaken}
		{literal}
		AttachEvent(window,'load',function() { window.parent.setTakenDate({/literal}'{$imagetaken}'{literal}) },false);
		{/literal}
	{/if}
	{if $preview_url}
		{literal}
		AttachEvent(window,'load',function() { window.parent.showPreview({/literal}'{$preview_url}',{$preview_width},{$preview_height},'{$filename|escape:'javascript'}'{literal}) },false);
		{/literal}
	{/if}
{/if}
	{literal}
		AttachEvent(window,'load',function() { setTimeout("setupTheForm()",100); },false);
	{/literal}

{if $container}
	{literal}

	function resizeContainer() {
		var FramePageHeight =  document.body.offsetHeight + 10;
		window.parent.document.getElementById('{/literal}{$container|escape:'javascript'}{literal}').style.height=FramePageHeight+'px';
	}

	AttachEvent(window,'load',resizeContainer,false);
	{/literal}
{/if}

{/dynamic}
</script>


</form>
</body>
</html>
