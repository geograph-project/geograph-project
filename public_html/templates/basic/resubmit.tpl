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

<h2>Add higher resolution image to <a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>


{if $error}
<h2><span class="formerror">check and correct errors below...<br/>{$error}</span></h2>
{/if}

{if $step eq -1}
<h2>Submission Abandoned</h2>
<p>Your upload has been aborted - if you have any
concerns or feedback regarding our licence terms, 
please <a title="contact us" href="/contact.php">contact us</a></p>

{elseif $step eq 4}
	<h3>Thank you</h3>
	
	<p>Your upload will be verified, and then made available via the 'more sizes' on the photo page soon.</p>

	<p>NOTE: Please check your larger size upload after 24 hours - if it has not appeared, <a href="/discuss/index.php?&action=vthread&forum=4&topic=28153">please report it</a></p>
	
	<p>Return to the <a href="/photo/{$image->gridimage_id}">photo page</a></p>

{elseif $step eq 2}



<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="upload_id" value="{$upload_id}"/>




<h3>Step 2 : Confirm Rotation and Image Size</h3>

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

			{if !$allow_same}
				{assign var="hide640" value=1}
			{/if}
			{include file="_submit_sizes.tpl"}

<script type="text/javascript">{literal}

function hideStep3() {
	document.getElementById("step3").style.display = 'none';
}
{/literal}
{if (!$user->upload_size || $user->upload_size == 640) && !$allow_same}
 AttachEvent(window,'load',hideStep3,false);
{/if}
</script>

<div id="step3">
<h3>Step 3 : Confirm Image Rights</h3>

	<p>
	Because we are an open project we want to ensure our content is licensed
	as openly as possible and so we ask that all images are released under a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
	Licence, including accompanying metadata.</p>
	
	<p>With a Creative Commons Licence, the photographer <b>keeps the copyright</b> but allows 
	people to copy and distribute the work provided they <b>give credit</b>.</p>
	
	<p>Since we want to ensure we can use your work to fund the running costs of
	this site, and allow us to create montages of grid images, we ask that you
	allow the following</p>
	
	<ul>
	<li>The right to use the work commercially</li>
	<li>The right to modify the work to create derivative works</li>
	</ul>
	
	<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
		
	<p>If you do
	not agree with these terms, click "I do not agree" and your upload will
	be abandoned.<br />
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

{if $repair}
	<ul>
		<li>Use this form to repair (and optionally add a higher resolution image to) the above submission</li>
		<li>You can just upload a 640px version, or as large as required. On the next screen will decide the actual resolution to release</li>
	</ul>
{else}
	<ul>
		<li>Use this form to add a higher resolution image to the above submission</li>
		<li>This should only be used to add the same exact image - although for example better tweaking of contrast and brightness is fine</li>
		<li>NOTE: This only adds a higher resolution version - it does NOT affect the photo shown on <a href="/photo/{$image->gridimage_id}">the photo page</a>
	</ul>
{/if}

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
<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

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
