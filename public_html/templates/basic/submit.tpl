{assign var="page_title" value="Submit"}
{include file="_std_begin.tpl"}

    <form enctype="multipart/form-data" action="{$script_name}" method="post">

{if $step eq 1}<h2>Submit Step 1 of 4 : Choose grid square</h2>{/if}
{if $step eq 2}<h2>Submit Step 2 of 4 : Upload photo for {$gridref}</h2>{/if}

{if $step lt 3}	
	<label for="gridsquare">Grid square</label>
	<select id="gridsquare" name="gridsquare">
		{html_options options=$prefixes selected=$gridsquare}
	</select>
	<label for="eastings">E</label>
	<select id="eastings" name="eastings">
		{html_options options=$kmlist selected=$eastings}
	</select>
	<label for="northings">N</label>
	<select id="northings" name="northings">
		{html_options options=$kmlist selected=$northings}
	</select>

	{if $step eq 1}
		<input type="submit" name="setpos" value="Next &gt;"/>
	{else}
		<input type="submit" name="setpos" value="Change"/>
	{/if}

{else}
	<input type="hidden" name="gridsquare" value="{$gridsquare|escape:'html'}">
	<input type="hidden" name="eastings" value="{$eastings|escape:'html'}">
	<input type="hidden" name="northings" value="{$northings|escape:'html'}">
{/if}


{if $step eq 2}
	
	{if $imagecount gt 0}
		<p style="color:#440000">We already have 
		{if $imagecount eq 1}an image{else}{$imagecount} images{/if} 
		uploaded for {$gridref}, but you are welcome to upload 
		another one.</p>
	{else}
		<p style="color:#004400">Fantastic! We don't yet have an image for {$gridref}!</p>
	{/if}
	
	<label for="jpeg">JPEG Image File</label>
	<input id="jpeg" name="jpeg" type="file" />
	{if $error}<br /><p>{$error}</p>{/if}
	<br />
	<input type="submit" name="upload" value="Next &gt;"/>
	
{/if}

{if $step eq 3}

<h2>Submit Step 3 of 4 : Check photo</h2>
<p>
Below is a full-size preview of the image we will store for grid reference 
{$gridref}.<br/><br/>

<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}" border="0">
<br/><br/>

<p>If you like, you can provide a little extra information about the image and
where or how it was taken (you'll be able to edit this later if you prefer)</p>

<label for="title">Title</label><br/>
<input size="50" id="title" name="title" value="" />

<br/><br/>

<label for="common">Comment</label><br/>
<textarea id="comment" name="comment" rows="3" cols="50">
</textarea>
<br/><br/>

If you are happy with the image, click 'next' to continue...
</p>
<input type="hidden" name="upload_id" value="{$upload_id}">
<input type="submit" name="agreeterms" value="Next &gt;"/>
{/if}

{if $step eq 4}
	<input type="hidden" name="upload_id" value="{$upload_id}">
	<input type="hidden" name="title" value="{$title|escape:'html'}">
	<input type="hidden" name="comment" value="{$comment|escape:'html'}">
	
	<h2>Submit Step 4 of 4 : Confirm image rights</h2>
		
	<p>
	Because we are an open project we want to ensure our content is licenced
	as openly as possible and so we ask that you adopt a <a title="Learn more about Creative Commons" href="http://creativecommons.org">Creative Commons</a>
	licence for your image.</p>
	
	<p>With a Creative Commons licence, you <b>keep your copyright</b> but allow 
	people to copy and distribute your work provided they <b>give you credit</b></p>
	
	<p>Since we want to ensure we can use your image to fund the running costs of
	this site, and allow us to create montages of grid images, we ask that you
	allow the following</p>
	
	<ul>
	<li>The right to use the image commercially</li>
	<li>The right to modify the image to create derivative works</li>
	</ul>
	
	<p><a title="View licence" target="_blank" href="http://creativecommons.org/licenses/by-sa/2.0/">Here is the Commons Deed outlining the licence terms</a></p>
	
	<p>If you agree with these terms, click "I agree" and your image will be
	stored in grid square {$gridref}.<br />
	<input style="width:200px" type="submit" name="finalise" value="I AGREE &gt;"/>
	</p>
	

<p>If you do
not agree with these terms, click "I do not agree" and your upload will
be abandoned.<br />
<input style="width:200px" type="submit" name="abandon" value="I DO NOT AGREE"/>

</p>

{/if}

{if $step eq 5}
<h2>Submission Complete!</h2>
<p>Thank you very much - your photo has now been added to grid square {$gridref}</p>
<p><a title="submit another photo" href="submit.php">Click here to submit a new photo...</a></p>
{/if}

{if $step eq 6}
<h2>Submission Abandoned</h2>
<p>Your upload has been erased from our server - if you have any
queries regarding our licence terms, please <a title="contact us" href="/contact.php">contact us</a></p>
{/if}


{if $step eq 7}
<h2>Submission Problem</h2>
<p>{$errormsg}</p>
<p>Please <a title="submit a photo" href="/submit.php">try again</a>, and
<a title="contact us" href="/contact.php">contact us</a> if you continue to
have problems
</p>
{/if}


	</form> 


{include file="_std_end.tpl"}
