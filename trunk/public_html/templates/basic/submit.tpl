{assign var="page_title" value="Submit"}
{include file="_std_begin.tpl"}

{dynamic}

    <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;">

{if $step eq 1}	

	<h2>Submit Step 1 of 4 : Choose grid square</h2>


<div style="width:180px;margin-left:10px;margin-bottom:100px;float:right;font-size:0.8em;padding:10px;background:#dddddd;position:relative">
<h3 style="margin-bottom:0;margin-top:0">Need Help?</h3>

<p>If you enter the exact location, e.g. <b>TL 246329</b> we'll figure 
out that it's in the <b>TL 2432</b> 1km square, but we'll also retain 
the more precise coordinate for accurately mapping the location of the 
photograph.</p>

<p>When you press Next, we'll find out if there are any existing photographs 
for that square</p>

<p>If you're new, you may like to check our <a href="/help/guide">guide to 
geographing</a> first.</p>

</div>


	<p>Begin by choosing the grid square you wish to submit.</p>

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}
	
	<p><b>Note:</b> this should be the location of the primary <i>subject</i> of the photo, if you wish you can specify a photographer location in the next step.</p>

	
	<p><label for="grid_reference">Enter an exact grid reference 
	(<u title="e.g. TQ4364 or TQ 43 64">4</u>,
	<u title="e.g. TQ435646 or TQ 435 646">6</u>,
	<u title="e.g. TQ43526467 or TQ 4352 6467">8</u> or 
	<u title="e.g. TQ4352364673 or TQ 43523 64673">10</u> figure) for the picture subject</label><br />
	<input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14"/>
	<input type="submit" name="setpos" value="Next &gt;"/><br/>
	</p>
		
	
	<label for="gridsquare">Alternatively, you can select the 1km grid square below...</label><br/>
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
	
	<input type="submit" name="setpos" value="Next &gt;"/>
	
	
	<p>If you are unsure of the photo location there are a number of online 
		sources available to help:</p>
		
	<ul>
		<li><b>{getamap} provides a search by 
		Placename or Postcode.</b><br/> Once you have centred the map on the picture location, 
		return here and enter the <i>Grid reference at centre</i> value shown into the box 
		above.<br/><br/></li>
		<li>{external href="http://www.multimap.com/map/browse.cgi?lat=54.5445&lon=-6.8228&scale=1000000" text="multimap.com"} now displays 1:50,000 <b>Mapping for Northern Ireland</b>. Use our handy <a href="/latlong.php">Lat/Long Convertor</a> to get the correct Grid Square for a picture.<br/><br/></li>
		
		<li><b>If you have a WGS84 latitude &amp; longitude coordinate</b>
		(e.g. from a GPS receiver, or from multimap site), then see our 
		<a href="/latlong.php">Lat/Long to Grid Reference Convertor</a><br/><br/></li>
		<li><b>For information on {external href="http://en.wikipedia.org/wiki/Grid_reference" text="Grid References"}</b> <br/>see 
		{external title="Guide to the National Grid" text="Interactive Guide to the National Grid in Great Britain" href="http://www.ordnancesurvey.co.uk/oswebsite/gi/nationalgrid/nghelp1.html"}.
		The {external href="http://en.wikipedia.org/wiki/Irish_national_grid_reference_system" text="Irish National Grid"} is very similar, but using a single letter prefix, 
		see <a href="/mapbrowse.php">Overview Map</a> for the layout of the squares.
		</li>
	</ul>


{else}
	<input type="hidden" name="gridsquare" value="{$gridsquare|escape:'html'}">
	<input type="hidden" name="eastings" value="{$eastings|escape:'html'}">
	<input type="hidden" name="northings" value="{$northings|escape:'html'}">
{/if}
{if $step > 2}
	<input type="hidden" name="grid_reference" value="{$grid_reference|escape:'html'}">
{/if}

{if $step eq 2}

	<h2>Submit Step 2 of 4 : Upload photo for {$gridref}</h2>
	{if $rastermap->enabled}
		<div style="float:left;width:50%;position:relative">
	{else}
		<div>
	{/if}
		{if $imagecount gt 0}
			<p style="color:#440000">We currently have 
			{if $imagecount eq 1}an image{else}{$imagecount} images{/if} {if $totalimagecount && $totalimagecount > $imagecount} ({$totalimagecount} including hidden){/if} (preview shown below)
			uploaded for <a title="View Images for {$gridref} (opens in new window)" href="/gridref/{$gridref}" target="_blank">{$gridref}</a>, add yours now!</p>
		{else}
			<p style="color:#004400">Fantastic! We don't yet have an image for {$gridref}! {if $totalimagecount && $totalimagecount ne $imagecount} (but you have {$totalimagecount} hidden){/if}</p>
		{/if}


		<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
		<label for="jpeg">JPEG Image File</label>
		<input id="jpeg" name="jpeg" type="file" />
		<div><small><small style="color:gray"><i>If your image is over 640 pixels in either direction, it will be resized. If you have presized please aim to have the filesize under 100kb and in anycase under 200kb, thanks!</i></small></small></div>
		{if $error}<br /><p style="color:#990000;font-weight:bold;">{$error}</p>{/if}
		<br />
		<p>You might like to check you've selected the correct square<br/> by
		viewing the Modern {getamap gridref="document.theForm.grid_reference.value" text="OS Get-a-map&trade;"}</p>

		{if $reference_index == 2} 
		{external href="http://www.multimap.com/p/browse.cgi?scale=25000&lon=`$long`&lat=`$lat`&GridE=`$long`&GridN=`$lat`" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland" target="_blank"} includes 1:50,000 mapping for Northern Ireland.
		{/if}
		
		<p><b>Grid References:</b> (recommended)<br/><br/><label for="grid_reference">Primary Photo Subject</label> <input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)"/><img src="/templates/basic/img/crosshairs.gif" alt="Marks the Subject" width="16" height="16" style="opacity: .5; filter: alpha(opacity=50);"/> <a href="javascript:void(document.theForm.photographer_gridref.value = document.theForm.grid_reference.value);void(updateMapMarker(document.theForm.photographer_gridref,false));" style="font-size:0.8em">Duplicate</a></p>
	
		<p><label for="photographer_gridref">Photographer Position</label> <input id="photographer_gridref" type="text" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}" size="14"  onkeyup="updateMapMarker(this,false)"/><img src="/templates/basic/img/camera.gif" alt="Marks the Photographer" width="16" height="16" style="opacity: .5; filter: alpha(opacity=50);"/><br/><small style="color:gray;"><i>Blank assumes very close to the subject</i></small>
		{if $rastermap->enabled}
			<br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1"/> <label for="use6fig">Only display 6 figure grid reference (<a href="/help/map_precision" target="_blank">Explanation</a>)</label>
		{/if}
		</p>
	
		<p><label for="view_direction">View Direction</label> <small>(photographer facing)</small><br/>
		<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
			{foreach from=$dirs key=key item=value}
				<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$view_direction} selected="selected"{/if}>{$value}</option>
			{/foreach}
		</select></p>
	</div>

	{if $rastermap->enabled}
		<div class="rastermap" style="width:45%;position:relative">
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				window.onload = function () {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
				}
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="/mapping.js?v={$javascript_version}"></script>
	{/if}

	

	<br/>
	<input type="submit" name="goback" value="&lt; Back"/> <input type="submit" name="upload" value="Next &gt;" onclick="{literal}if (checkGridReferences(this.form)) {return autoDisable(this);} else {return false}{/literal}"/>
	<br style="clear:right"/>

	{if $totalimagecount gt 0}
	<br/>
	<div style="background-color:#eeeeee; padding:10px;">
		<div><b>Latest {$shownimagecount} images for this square...</b></div>

	{foreach from=$images item=image}

	  <div class="photo33" style="float:left;width:150px; height:170px; background-color:white"><a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(120,120,false,true)}</a>
	  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a></div>
	  <div class="statuscaption">status:
		{if $image->ftf}first{/if}
		{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}
	  </div>
	  </div>

	{/foreach}
	<br style="clear:both"/>
	
	{if $imagecount gt 6 || $shownimagecount == 6}
		<div>See <a href="/gridref/{$gridref}" target="_blank">all {$imagecount} live image{if $imagecount!=1}s{/if} for {$gridref}</a> plus any images you have hidden (opens in new window)</div>
	{/if}&nbsp;
	</div>
	
	{/if}
{else}
	<input type="hidden" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}">
	<input type="hidden" name="view_direction" value="{$view_direction|escape:'html'}">
	<input type="hidden" name="use6fig" value="{$use6fig|escape:'html'}">

{/if}

{if $step eq 3}

<h2>Submit Step 3 of 4 : Check photo</h2>

{if $errormsg}
<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
{/if}

  {if $smallimage}
	<div style="background-color:red; color:white; border:1px solid pink; padding:10px;">We notice the image is quite small, you can continue, but we would welcome a bigger image. Note, small images are usually rejected unless there is something unique about the image.</div>
  {/if}

<p>
Below is a full-size preview of the image we will store for grid reference 
{$gridref}.<br/><br/>

<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
<br/><br/>

<div style="position:relative; background-color:#dddddd; padding-left:10px;padding-top:1px;padding-bottom:1px;">
<h3><a name="geograph"></a>Is the image a &quot;geograph&quot;?</h3>

<p>If you're the first to submit a proper &quot;geograph&quot; for {$gridref}
you'll get a geograph point added to your profile and the warm glow that comes
with it.</p>
<p>So what makes an image a genuine geograph? (there can be many geograph images per square)</p>
<ul>
<li>The image subject must be within grid square {getamap gridref=$gridref}, and ideally the photographer should be too.</li>
<li>You must clearly show at close range one of the main geographical features within the square</li>
<li>You should include a short description relating the image to the map square</li>
<li>The image should be a natural image as a human would see it, please avoid digitally altering the image to add dates, texts etc, or creating montages, similarly turn off date stamping performed by a camera. A little tweaking of brightness/contrast and/or cropping is fine and encouraged. </li>
</ul>

<p>Good quality, visually appealing and historically relevant pictures (eg wide area views
covering many square kilometres) may also be accepted as supplemental images 
for {$gridref} provided they are accurately located, but may not qualify as geographs.</p>
</div>

<div style="float:right;position:relative;">
<img src="{$preview_url}" width="{$preview_width*0.5|string_format:"%d"}" height="{$preview_height*0.5|string_format:"%d"}" alt="low resolution reminder image"/>	
</div>

<p>If you like, you can provide more images or extra information (which
can be edited at any time) but to activate a square you need to be first to meet the
criteria above!</p>

<a href="/submit_popup.php?t={$reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Reopen Map in a popup</a> (and view list of placenames)

<h3>Title and Comments</h3>
<p>Please provide a short title for the image, and any other comments about where
it was taken or other interesting geographical information. (<a href="/help/style" target="_blank">Open Style Guide</a>)</p>

<p><label for="title">Title</label> {if $error.title}
	<br/><span class="formerror">{$error.title}</span>
	{/if}<br/>
<input size="50" id="title" name="title" value="{$title|escape:'html'}" spellcheck="true"/></p>
 {if $place.distance}
 <p style="font-size:0.7em">Gazetteer info as will appear:<br/> <span style="color:silver;">{place place=$place}</span></p>
 {/if}

<p style="clear:both"><label for="comment">Comment</label><br/>
<textarea id="comment" name="comment" rows="7" cols="80" spellcheck="true">{$comment|escape:'html'}</textarea></p>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>


<h3>Further Information</h3>

<script type="text/javascript" src="/categories.js.php"></script>
{literal}
<script type="text/javascript">
<!--
//rest loaded in geograph.js
function mouseOverImageClass() {
	if (!hasloaded) {
		setTimeout("prePopulateImageclass2()",100);
	}
	hasloaded = true;
}

function prePopulateImageclass2() {
	var sel=document.getElementById('imageclass');
	sel.disabled = false;
	var oldText = sel.options[0].text;
	sel.options[0].text = "please wait...";

	populateImageclass();

	hasloaded = true;
	sel.options[0].text = oldText;
	if (document.getElementById('imageclass_enable_button'))
		document.getElementById('imageclass_enable_button').disabled = true;
}

window.onload = onChangeImageclass;
//-->
</script>
{/literal}

<p><label for="imageclass">Primary geographical category</label> {if $error.imageclass}
	<br/><span class="formerror">{$error.imageclass}</span>
	{/if}<br />	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onfocus="prePopulateImageclass()" onmouseover="mouseOverImageClass()">
		<option value="">--please select feature--</option>
		{if $imageclass}
			<option value="{$imageclass}" selected="selected">{$imageclass}</option>
		{/if}
		<option value="Other">Other...</option>
	</select>

<span id="otherblock">
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/>
	</span></p>
	
	
	
	
<p><label>Date photo taken</label> {if $error.imagetaken}
	<br/><span class="formerror">{$error.imagetaken}</span>
	{/if}<br/>
	{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	{if $imagetakenmessage}
	    {$imagetakenmessage}
	{/if}
	
	[ Use 
	<input type="button" value="Today's" onclick="setdate('imagetaken','{$today_imagetaken}',this.form);" class="accept"/>
	{if $last_imagetaken}
		<input type="button" value="Last Submitted" onclick="setdate('imagetaken','{$last_imagetaken}',this.form);" class="accept"/>
	{/if}
	{if $imagetaken != '--' && $imagetaken != '0000-00-00'}
		<input type="button" value="Current" onclick="setdate('imagetaken','{$imagetaken}',this.form);" class="accept"/>
	{/if}
	Date ]
	
	<br/><br/><span style="font-size:0.7em">(please provide as much detail as possible, if you only know the year or month then that's fine)</span></p>


<div style="position:relative; background-color:#dddddd; border: 1px solid red; padding-left:10px;padding-top:1px;padding-bottom:1px;">
<h3>Image Status</h3>

<p><label for="user_status">I wish to suggest this image become a supplemental:</label> <input type="checkbox" name="user_status" id="user_status" value="accepted" {if $user_status == "accepted"}checked="checked"{/if}/> (tick to apply)</p>

<p>Only use this tick box if you believe the subject matter is not quite a 'geograph', there can be many geograph images per square.</p>

<p>Remembering the points <a href="#geograph">above</a> about what makes a 'geograph', <span class="nowrap">more information can be found in the <a href="/help/geograph_guide" target="_blank">geograph guide</a> (opens in new window).</span></p>
</div>

<p>
<input type="hidden" name="upload_id" value="{$upload_id}"/>
<input type="hidden" name="savedata" value="1"/>
<input type="submit" name="goback" value="&lt; Back"/>
<input type="submit" name="next" value="Next &gt;"/></p>
{/if}

{if $step eq 4}
	<input type="hidden" name="upload_id" value="{$upload_id}"/>
	<input type="hidden" name="title" value="{$title|escape:'html'}"/>
	<input type="hidden" name="comment" value="{$comment|escape:'html'}"/>
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
	<input type="hidden" name="user_status" value="{$user_status|escape:'html'}"/>
	
	<h2>Submit Step 4 of 4 : Confirm image rights</h2>
	{if $user->rank && $user->rank < 250 && $last_imagetaken}

	<p>I've read this already, <input style="width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this)"/></p>
	{/if}
	
	<p>
	Because we are an open project we want to ensure our content is licensed
	as openly as possible and so we ask that you adopt a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons"  target="_blank"}
	licence for your image and accompanying metadata.</p>
	
	<p>With a Creative Commons licence, you <b>keep your copyright</b> but allow 
	people to copy and distribute your work provided they <b>give you credit</b></p>
	
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
	<input style="width:200px" type="submit" name="abandon" value="I DO NOT AGREE"/>
	
	</p>


	<p>If you agree with these terms, click "I agree" and your image will be
	stored in grid square {$gridref}.<br />
	<input type="submit" name="goback3" value="&lt; Back"/>
	<input style="width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this)"/>
	</p>
	


{/if}

{if $step eq 5}
<h2>Submission Complete!</h2>
<p>Thank you very much - your photo has now been added to grid square 
<a title="Grid Reference {$gridref}" href="/gridref/{$gridref}">{$gridref}</a>.</p>
<p>Your photo has identification number [<a href="/photo/{$gridimage_id}">{$gridimage_id}</a>]</p>
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
