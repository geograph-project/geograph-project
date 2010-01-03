{include file="_basic_begin.tpl"}

<form enctype="multipart/form-data" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<input type="hidden" name="inner" value="1"/>
{dynamic}

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $step eq 1}	
	
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
			<div>Please choose the size you wish to upload: <sup style="color:red">New!</sup></div>
		
			{math equation="o/180" o=$original_width assign="ratio"}
			
			<table style="font-weight:bold" cellspacing="0" border="1" bordercolor="#cccccc">
				<tr>
				
					<td valign="top"><input type="radio" name="largestsize" checked value="640" id="large640" onclick="selectImage(this)"/> {$preview_width} x {$preview_height}<br/><br/>
					<label for="large640"><img src="{$preview_url}" width="{$preview_width/$ratio}" height="{$preview_height/$ratio}" name="large640" style="border:2px solid blue"/></label><br/><br/>
					<small>(as shown on<br/> photo page)</small>
					{assign var="last_width" value=$preview_width} 
					{assign var="last_height" value=$preview_height} 
					</td>
				
				{if $original_width > 800 || $original_height > 800}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=800}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=800}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><input type="radio" name="largestsize" value="800" id="large800" onclick="selectImage(this)"/> {$resized_width} x {$resized_height}<br/><br/>
					<label for="large800"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large800" style="border:2px solid white"/></label>
					{assign var="last_width" value=$resized_width}
					{assign var="last_height" value=$resized_height}
					</td>
				{/if}
				
				{if $original_width > 1024 || $original_height > 1024}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=1024}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=1024}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><input type="radio" name="largestsize" value="1024" id="large1024" onclick="selectImage(this)"/> {$resized_width} x {$resized_height}<br/><br/>
					<label for="large1024"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large1024" style="border:2px solid white"/></label>
					{assign var="last_width" value=$resized_width}
					{assign var="last_height" value=$resized_height}
					</td>
				{/if}
				
				{if $original_width > 1600 || $original_height > 1600}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=1600}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=1600}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><input type="radio" name="largestsize" value="1600" id="large1600" onclick="selectImage(this)"/> {$resized_width} x {$resized_height}<br/><br/>
					<label for="large1600"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large1600" style="border:2px solid white"/></label>
					{assign var="last_width" value=$resized_width}
					{assign var="last_height" value=$resized_height}
					</td>
				{/if}
				
				{if $original_width > $last_width || $original_height > $last_height}

					<td valign="top"><input type="radio" name="largestsize" value="8192" id="large8192" onclick="selectImage(this)"/> {$original_width} x {$original_height}<br/><br/>
					<label for="large8192"><img src="{$preview_url}" width="{$original_width/$ratio}" height="{$original_height/$ratio}" name="large8192" style="border:2px solid white"/></label>
					</td>
				{/if}
				</tr>
			</table>
			Previews shown at <b>{math equation="round(100/r)" r=$ratio}</b>% of actual size - NOT representive of the final quality. Even if choose a larger size, we will still make the smaller sizes available too.
			
			
		{elseif $preview_url}
		<h2>Image Uploaded</h2>
		<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
		{/if}
		
		<p>Is this the wrong image? <a href="/submit2.php?inner&amp;step=1">Upload a different image</a></p>
	{else}
		{if $error}
			<p style="color:#990000;font-weight:bold;">{$error}</p>
		{/if}
		<div><label for="jpeg_exif"><b>Select Image file to upload</b></label> - (upload photos larger than 640px - <sup style="color:red">New!</sup>)<br/>	
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60" style="background-color:white"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000"/></div>
		<div>
		<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px;font-size:1.2em"/> (while file is sending can continue on the steps below)<br/>
		</div>

		<br/>
		<div><i>Optionally</i> upload an image with Locational information attached<br/>
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


function selectImage(that) {
	for(q=0;q<document.images.length;q++) {
		if (document.images[q].name && document.images[q].name == that.id) {
			document.images[q].style.border='2px solid blue';
		} else {
			document.images[q].style.border='2px solid white';
		}
	}
	return true;
}
	{/literal}
	
{/dynamic}
</script>


</form>
</body>
</html>