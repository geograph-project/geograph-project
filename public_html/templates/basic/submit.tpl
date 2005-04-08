{assign var="page_title" value="Submit"}
{include file="_std_begin.tpl"}

{dynamic}

    <form enctype="multipart/form-data" action="{$script_name}" method="post">

{if $step eq 1}	

	<h2>Submit Step 1 of 4 : Choose grid square</h2>

	<p>Begin by choosing the grid square you wish to submit. <br/>
	If you're new, you may like to check our <a href="/help/guide">guide to geographing</a> first.</p>

	<label for="gridsquare">Choose 1km Grid square...</label><br/>
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
	
	{if $errormsg}
	<p><b>{$errormsg}</b></p>
	{/if}
	{if $step eq 1}
		<p><label for="gridreference">...or enter an exact grid reference (4,6,8 or 10 figure) for this picture location</label><br />
		<input id="gridreference" type="text" name="gridreference" value="{$gridreference|escape:'html'}" size="12"/>
		<input type="submit" name="setpos" value="Next &gt;"/><br/>
		</p>
		
		<p>If you are unsure of the photo location there are a number of online 
		sources available to help:</p>
		
		<ul>
		<li>{getamap} provides a search by 
		placename or postcode. Once you have centered the map on the picture location 
		return here, and enter the 'Grid reference at centre' value shown into the box 
		above.</li>
		
		<li>If you have a WGS84 coordinate from a GPS receiver, this 
		<a href='http://www.trigpointinguk.com/info/convert-wgs.php' onClick="window.open(href,'wgs','height=300,width=600,status,scrollbars');return false;" target="_blank">WGS84 to OSGB36 Grid Ref Convertor</a> may be useful.</li>
		</ul>
		
		
	{else}
		<input type="hidden" name="gridreference" value="{$gridreference|escape:'html'}">
	{/if}

{else}
	<input type="hidden" name="gridsquare" value="{$gridsquare|escape:'html'}">
	<input type="hidden" name="eastings" value="{$eastings|escape:'html'}">
	<input type="hidden" name="northings" value="{$northings|escape:'html'}">
	<input type="hidden" name="gridreference" value="{$gridreference|escape:'html'}">
{/if}


{if $step eq 2}

	<h2>Submit Step 2 of 4 : Upload photo for {$gridref}</h2>
{if $rastermap->enabled}
	<div style="float:left;width:50%;position:relative">
{else}
	<div>
{/if}
	{if $imagecount gt 0}
		<p style="color:#440000">We already have 
		{if $imagecount eq 1}an image{else}{$imagecount} images{/if} 
		uploaded for {$gridref}, but you are welcome to upload 
		another one.</p>
	{else}
		<p style="color:#004400">Fantastic! We don't yet have an image for {$gridref}!</p>
	{/if}
	
	
	<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
	<label for="jpeg">JPEG Image File</label>
	<input id="jpeg" name="jpeg" type="file" />
	{if $error}<br /><p>{$error}</p>{/if}
	<br />
	<p>You might like to check you've selected the correct square by
	viewing the Modern {getamap gridref=$gridref text="OS Map for $gridref"}</p>
</div>

{if $rastermap->enabled}
	<div class="rastermap" style="width:45%;position:relative">
	<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
	{$rastermap->getImageTag()}<br/>
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	</div>
{/if}

	

	<br/>
	<input type="submit" name="goback" value="&lt; Back"/> <input type="submit" name="upload" value="Next &gt;"/>
	<br style="clear:right"/>
	
{/if}

{if $step eq 3}

<h2>Submit Step 3 of 4 : Check photo</h2>
<p>
Below is a full-size preview of the image we will store for grid reference 
{$gridref}.<br/><br/>

<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}">
<br/><br/>

<h3>Is the image a &quot;geograph&quot;?</h3>
<p>If you're the first to submit a proper &quot;geograph&quot; for {$gridref}
you'll get a geograph point added to your profile and the warm glow that comes
with it. So what makes an image a genuine geograph?</p>
<ul>
<li>The image must be taken within grid square {getamap gridref=$gridref}</li>
<li>You must clearly show at close range one of the main geographical features within the square</li>
<li>You should include a short description relating the image to the map square</li>
</ul>

<p>Good quality, visually appealing and historically relevant pictures (eg wide area views
covering many square kilometers) may also be accepted as supplemental images 
for {$gridref} provided they are accurately located, but may not qualify as geographs.
.</p>

<p>If you like, you can provide more images or extra information (which
can be edited at any time) but to activate a square you need to be first to meet the
criteria above!</p>


	
	

<h3>Title and Comments</h3>
<p>Please provide a short title for the image, and any other comments about where
it was taken or other interesting geographical information.</p>

<label for="title">Title</label><br/>
<input size="50" id="title" name="title" value="{$title|escape:'html'}" />

<br/><br/>

<label for="comment">Comment</label><br/>
<textarea id="comment" name="comment" rows="3" cols="50">{$comment|escape:'html'}</textarea></p>


<h3>Further Image Information</h3>

{literal}
<script type="text/javascript">
<!--
function onChangeImageclass()
{
	var sel=document.getElementById('imageclass');
	var idx=sel.selectedIndex;
	
	var isOther=idx==sel.options.length-1

	
	var otherblock=document.getElementById('otherblock');
	otherblock.style.display=isOther?'block':'none';
	
}


//-->
</script>
{/literal}

<p><label for="imageclass">Primary geographical category</label><br />	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()">
		<option value="">--please select feature--</option>
		{html_options options=$classes selected=$imageclass}
	</select>

<span id="otherblock" {if $imageclass ne 'Other'}style="display:none;"{else}style="display:inline;"{/if}>
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32"/></p>
	</span>
	
	
	
	
<p><label>Date picture taken</label><br/>
	{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	{if $imagetakenmessage}
	    {$imagetakenmessage}
	{/if}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>

<input type="hidden" name="upload_id" value="{$upload_id}">

<br />
<input type="hidden" name="savedata" value="1">
<input type="submit" name="goback" value="&lt; Back"/>
<input type="submit" name="next" value="Next &gt;"/>
{/if}

{if $step eq 4}
	<input type="hidden" name="upload_id" value="{$upload_id}">
	<input type="hidden" name="title" value="{$title|escape:'html'}">
	<input type="hidden" name="comment" value="{$comment|escape:'html'}">
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}">
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}">
	
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
	
	
	<p>If you do
	not agree with these terms, click "I do not agree" and your upload will
	be abandoned.<br />
	<input style="width:200px" type="submit" name="abandon" value="I DO NOT AGREE"/>
	
	</p>


	<p>If you agree with these terms, click "I agree" and your image will be
	stored in grid square {$gridref}.<br />
	<input type="submit" name="goback3" value="&lt; Back"/>
	<input style="width:200px" type="submit" name="finalise" value="I AGREE &gt;"/>
	</p>
	


{/if}

{if $step eq 5}
<h2>Submission Complete!</h2>
<p>Thank you very much - your photo has now been added to grid square 
<a title="Grid Reference {$gridref}" href="/browse.php?gridref={$gridref}">{$gridref}</a></p>
<p><a title="submit another photo" href="submit.php">Click here to submit a new photo...</a></p>
{/if}

{if $step eq 6}
<h2>Submission Abandoned</h2>
<p>Your upload has been aborted - if you have any
concerns or feedback regarding our licence terms, 
please <a title="contact us" href="/contact.php">contact us</a></p>
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

{/dynamic}
{include file="_std_end.tpl"}
