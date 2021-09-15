{assign var="page_title" value="Add High-Res"}
{include file="_std_begin.tpl"}

{dynamic}

{$status_message}

{if $image}
	<div style="position:relative; float:right; width:220px; background-color:#eeeeee; padding: 10px; text-align:center">
		<b>Chosen Image</b>
		<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
			 <div style="font-size:0.7em">
				  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
				  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
				  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
			</div>
		</div>
	</div>
{/if}

<h2>Add temporally high resolution image to <a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>


{if $error}
<h2><span class="formerror">check and correct errors below...<br/>{$error}</span></h2>
{/if}

{if $step eq 3}
	<h3>Thank you</h3>
	
	<p>Return to the <a href="edit.php?id={$calendar_id}">calendar page</a></p>

{elseif $step eq 2}



<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}&amp;cid={$calendar_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="upload_id" value="{$upload_id}"/>




<h3>Step 2 : Confirm Rotation</h3>

		{if $original_width || $allow_same}

			{if $rotation_warning}
				<div id="rotation_warning" style="background-color:yellow;border:2px solid red;margin:10px;padding:10px">
					Warning: <b>This image has EXIF 'Orientation' flag set.</b> 
					It's highly recommended to use the rotation function to reorientate the image, this resets the flag which prevents potential display issues, as not all Browsers etc will honor the flag.<br><br>
					So please rotate the image, even if it actully displays <i>correctly</i> in the preview! Rotate it sideways, and then <i>back</i> until displays correctly again.<br>Your browser might be ignoring the flag which is why the preview appears ok to you!
				</div>
			{/if}

			<div class="interestBox" style="max-width:500px">
			Rotate Image by 90 degrees: 
				<button type=button value=&#8634; title="Anti-Clockwise 90deg rotation" onclick="rotateImage(270)">&#8634;</button> 
				<button type=button value=&#8635; title="Clockwise 90deg rotation" onclick="rotateImage(90)">&#8635;</button> (updates the preview below)
			</div>			

			<img src="{$preview_url}" name="large640" style="border:2px solid blue"/>

<div id="step3">
<h3>Step 3 : Finalize</h3>

	<p>Note this image is NOT being released as Creative Commons, its only been stored for use on this single Calendar. 
	Otherwise the image is only being processed as per standard Geograph Terms of Use</p>

	<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
	
	<p>If you agree with these terms, click "I agree" and the upload will proceed.<br />
	
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);"/> 
	
	</p>
</div>	
		{else}
			<h3>Error: file not big enough, please click: 
			<input style="background-color:pink; width:200px" type="submit" name="abandon" value="Abandon upload"/>
		{/if}


	</form>
	

{else if $step eq 1}

	<ul>
		<li>Use this form to add a higher resolution image to the above submission</li>
		<li>This should only be used to add the same exact image - although for example better tweaking of contrast and brightness is fine</li>
		<li>NOTE: This only adds the image for use on the calendar - it does NOT affect the photo shown on <a href="/photo/{$image->gridimage_id}">the photo page</a>
	</ul>

{if $exif}
	<p>Data from EXIF that might help locate the original:</p>
	<ul>
		{if $exif.filename}
			<li>Filename: <b>{$exif.filename|escape:'html'}</b></li>
		{/if}
		{if $exif.datetime}
			<li>Date: <b>{$exif.datetime|escape:'html'}</b></li>
		{/if}
		{if $exif.model}
			<li>Camera Model: <b>{$exif.model|escape:'html'}</b></li>
		{/if}
		{if $exif.width}
			<li>Width: <b>{$exif.width|thousends} pixels</b></li>
		{/if}
		{if $exif.height}
			<li>Height: <b>{$exif.height|thousends} pixels</b></li>
		{/if}
		{if $exif.filesize}
			<li>File size: <b>{$exif.filesize|thousends} bytes</b></li>
		{/if}
	</ul>
	
{/if}

<br style="clear:both"/>
<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}&amp;cid={$calendar_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

<h3>Step 1 : Select Image File</h3>

<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
<label for="jpeg"><b>JPEG Image File</b></label>
<input id="jpeg" name="jpeg" type="file" accept="image/jpeg"/><br/>

<p>(There is no resolution limit, but the file must be under 8 megabytes)</p>

        {literal}<script>
        document.getElementById("jpeg").onchange = function(e) {
            var file = e.target.files[0];
            if (file && file.size && file.size > 8192000) {
                alert('File appears to be '+file.size+' bytes, which is too big for final submission. Please downsize the image to be under 8 Megabytes.');
            } else if (file && file.type && file.type != "image/jpeg") {
                alert('File appears to not be a JPEG image. We only accept .jpg files');
            } else if (file && file.size && file.size < 50000) {
                alert('File appears to be '+file.size+' bytes, which is rather small. Please check selected right image.');
            }
        }
        </script>{/literal}

<input type="submit" name="next" value="Next &gt;" onclick="autoDisable(this);"/>

</form>

{/if}
{/dynamic}


<script type="text/javascript">
{literal}

function rotateImage(degrees,force) {
        //we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object.
        if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
                jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
        }

        $(function() { //will sill execute even after page load!
		
	        var theForm = document.forms['theForm'];
		var upload_id;
		if (theForm.elements['upload_id'])
			upload_id = escape(theForm.elements['upload_id'].value);
		if (theForm.elements['transfer_id'])
			upload_id = escape(theForm.elements['transfer_id'].value);
		if (!upload_id || upload_id.length<10) {
			alert("unable to rotate, please let us know");
			return;
		}
			
		if (!force)
			force=0; //just avoids 'undefined'
		$.getJSON("/submit.php?rotate="+upload_id+"&degrees="+degrees+"&force="+force,
                         function (result) {
				if (result.width && result.upload_id) {
					if (theForm.elements['upload_id'])
						theForm.elements['upload_id'].value = result.upload_id;
					if (theForm.elements['transfer_id'])
						theForm.elements['transfer_id'].value = result.transfer_id;

					var newLandscape = result.width > result.height;

					$('form[name=theForm] img').each(function() {
						//setup the orientation of the preview!
						var $this = $(this);
						var thisLandscape = $this.width() > $this.height();
						if (newLandscape != thisLandscape) {
							var tmp = $this.attr('width');
							$this.attr('width', $this.attr('height'));
							$this.attr('height', tmp);
						}
					}).attr('src',"/submit.php?preview="+result.upload_id);

					if (document.getElementById('rotation_warning')) {
						document.getElementById('rotation_warning').style.display = 'none';
					}

				} else if (result.lossy) {
					if (confirm("This image can not be rotated losslessly, there will be some small quality loss if continue")) {
						 rotateImage(degrees,1);
					}
				} else {
					alert("Rotation Failed, please try again. Or if persists, let us know!");
				}
			}
		);
	});
}

{/literal}
</script>


{include file="_std_end.tpl"}
