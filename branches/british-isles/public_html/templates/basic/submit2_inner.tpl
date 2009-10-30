{include file="_basic_begin.tpl"}

<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
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
		<h2>Image Uploaded</h2>
		
		{if $preview_url}
		<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
		{/if}
		
		<p><a href="/submit2.php?inner&amp;step=1">Start over</a></p>
	{else}
	
		<div><label for="jpeg_exif">Select Image file to upload - recommend resizing to 640px on longest side</label> <br/>	
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60" style="background-color:white"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000"/></div>
		<div>
		<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px"/> (while file is sending can continue on the steps below)<br/>
		</div>

		<br/>
		<div><b><i>Optionally</i> upload an image with Locational information attached</b><br/>
		<ul>
			<li>GPS-EXIF tags based on WGS84 Lat/Long</li>
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
		AttachEvent(window,'load',function() { window.parent.doneStep({/literal}{$step}{literal}) },false);
	{/literal}
	{if $grid_reference} 
		{literal}
			AttachEvent(window,'load',function() { window.parent.doneStep(2) },false);
			AttachEvent(window,'load',function() { window.parent.clicker(3,1) },false);
		{/literal}
	{else}
		{literal}
			//AttachEvent(window,'load',function() { window.parent.clicker(2,1) },false);
		{/literal}
	{/if}
{else}
	{literal}
		AttachEvent(window,'load',function() { setTimeout("setupTheForm()",100); },false);
	{/literal}
{/if}	
{/dynamic}
</script>


</form>
</body>
</html>