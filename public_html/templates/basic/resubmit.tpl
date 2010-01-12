{assign var="page_title" value="Add High-Res"}
{include file="_std_begin.tpl"}

{dynamic}

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
	
	<p>Return to the <a href="/photo/{$image->gridimage_id}">photo page</a></p>

{elseif $step eq 2}



<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="upload_id" value="{$upload_id}"/>




<h3>Step 2 : Confirm image size</h3>

		{if $original_width}
			
			{assign var="hide640" value=1}
			{include file="_submit_sizes.tpl"}

<script type="text/javascript">{literal}

function hideStep3() {
	document.getElementById("step3").style.display = 'none';
}

 AttachEvent(window,'load',hideStep3,false);

{/literal}</script>

<div id="step3">
<h3>Step 3 : Confirm image rights</h3>

	<p>
	Because we are an open project we want to ensure our content is licensed
	as openly as possible and so we ask that all images are released under a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
	licence, including accompanying metadata.</p>
	
	<p>With a Creative Commons licence, the photographer <b>keeps the copyright</b> but allows 
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



<ul>
	<li>Use this form to add a higher resolution image to the above submission</li>
	<li>This should only be used to add the same exact image - although for example better tweaking of contrast and brightness is fine</li>
	<li>NOTE: This only adds a higher resolution version - it does NOT affect the photo shown on <a href="/photo/{$image->gridimage_id}">the photo page</a>
</ul>

{if $exif}
	<p>Data from EXIF that might help locate the original:</p>
	<ul>
		{if $exif.filename}
			<li>Filename: <b>{$exif.filename|escape:'html'}</b></li>
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
<input id="jpeg" name="jpeg" type="file" /><br/>

<p>(There is no resolution limit, but the file must be under 8 Megabytes)</p>


<input type="submit" name="next" value="Next &gt;" onclick="autoDisable(this);"/>

</form>

{/if}
{/dynamic}

{include file="_std_end.tpl"}
