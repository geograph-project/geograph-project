{assign var="page_title" value="Submit"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.tab {
	cursor:pointer;
	cursor:hand;
}

.navButtons A {
	border: 1px solid lightgrey;
	padding: 2px;
}

</style>{/literal}
{dynamic}

    <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if $step eq 1}	

	<h2>Submit Step 1 of 4 : Choose grid square</h2>

{if $user->stats.images eq 0} 
	<div style="background-color:pink; color:black; border:2px solid red; padding:10px;"><b>First time here?</b> - if so you might like to have a look at our <a href="/faq.php">FAQ</a>.</div>

{/if}

{if $user->stats.images < 20} 
<div style="width:180px;margin-left:10px;margin-bottom:100px;float:right;font-size:0.8em;padding:10px;background:#dddddd;position:relative; border:1px solid gray; z-index:100">
<h3 style="margin-bottom:0;margin-top:0">Need Help?</h3>

<p>If you enter the exact location, e.g. <b>TPT 278695</b> we'll figure 
out that it's in the <b>TPT 2769</b> 1km square, but we'll also retain 
the more precise coordinate for accurately mapping the location of the 
photograph.</p>

<p>When you press Next, we'll find out if there are any existing photographs 
for that square</p>

<p>If you're new, you may like to check our <a href="/article/Anleitung">guide to 
geographing (German)</a> first.</p>

</div>
{/if} 

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}
	
	<p>Choose your submission method:</p>
	
<div style="position:relative;">
	<div class="tabHolder">
		<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,4)">Enter Grid Reference</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,4)">Choose Square</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,4)">Tagged Image</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,4); if (!document.getElementById('innerFrame4').src) document.getElementById('innerFrame4').src = '/submitmap.php?inner'">Map/Placename</a>
	</div>

	<div style="position:relative;{if $tab != 1}display:none{/if}" class="interestBox" id="div1">
		<p>Begin by choosing the grid square you wish to submit.</p>

		<p><b>Note:</b> this should be the location of the primary <i>subject</i> of the photo, specify the photographer location in the next step.</p>

		<p><label for="grid_reference">Enter the grid reference 
		(<u title="e.g. TPT2769 or TPT 27 69">4</u>,
		<u title="e.g. TPT277695 or TPT 277 695">6</u>,
		<u title="e.g. TPT27796951 or TPT 2779 6951">8</u> or 
		<u title="e.g. TPT2779269513 or TPT 27792 69513">10</u> figure) for the subject grid square</label><br /><br />
		{if $grid_reference}<small><small>(<a href="javascript:void(document.getElementById('grid_reference').value = '');">clear</a>)<br/></small></small>{/if}
		<input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14"/><small class="navButtons"><small><a href="javascript:doMove('grid_reference',-1,0);">W</a></small><sup><a href="javascript:doMove('grid_reference',0,1);">N</a></sup><sub><a href="javascript:doMove('grid_reference',0,-1);">S</a></sub><small><a href="javascript:doMove('grid_reference',1,0);">E</a></small></small>
		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="setpos" value="Next &gt;"/> {if $picnik_api_key}or <input type="submit" name="picnik" value="Upload via Picnik &gt;"/>{/if}
		</p>
		
		{if $picnik_api_key}<p>Clicking the <i>Upload via Picnik</i> button above allows submission via an online image manipulation service that allows tweaking of the image prior to automatically transfering it to Geograph.</p>{/if}
	</div>		

	<div style="position:relative;{if $tab != 2}display:none{/if}" class="interestBox" id="div2">
		<p>Begin by choosing the grid square you wish to submit.</p>

		<p><b>Note:</b> this should be the location of the primary <i>subject</i> of the photo, if you wish you can specify a photographer location in the next step.</p>

		<p><label for="gridsquare">Select the 1km grid square below...</label><br/><br/>
		<select id="gridsquare" name="gridsquare">
			{html_options options=$prefixes selected=$gridsquare}
		</select>&nbsp;&nbsp;
		<label for="eastings">E</label>
		<select id="eastings" name="eastings">
			{html_options options=$kmlist selected=$eastings}
		</select>
		<small><small><a href="javascript:doMove2(-1,0);">W</a></small><small><a href="javascript:doMove2(1,0);">E</a></small></small>&nbsp;&nbsp;
		<label for="northings">N</label>
		<select id="northings" name="northings">
			{html_options options=$kmlist selected=$northings}
		</select>
		<small><sup><a href="javascript:doMove2(0,1);">N</a></sup><sub><a href="javascript:doMove2(0,-1);">S</a></sub></small>
		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="setpos2" value="Next &gt;"/> {if $picnik_api_key}or <input type="submit" name="picnik" value="Upload via Picnik &gt;"/>{/if}
		</p>
	</div>

	<div style="position:relative;{if $tab != 3}display:none{/if}" class="interestBox" id="div3">
		<p><label for="jpeg_exif"><b>Upload an image with Locational information attached</b></label> <br/>
		
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
		
		<input type="submit" name="setpos" value="Next &gt;"/> <br/>
		
		<div>Currently understands:<ul>
		<li>GPS-EXIF tags based on WGS84 Lat/Long</li>
		<li>Subject grid-reference from the name of the file (eg "<tt>photo-<b style="padding:1px">TPT278695</b>A.jpg</tt>")</li>
		<li>Subject grid-reference in EXIF Comment tag</li>
		</ul></div>
	</div>

	<div style="position:relative;{if $tab != 4}display:none{/if}" class="interestBox" id="div4">
		<iframe {if $tab == 4}src="/submitmap.php?inner"{/if} id="innerFrame4" width="613" height="660" frameborder="0"><a href="/submitmap.php">Click here to open a Draggable Interactive Google Map</a></iframe>
	</div>
	
</div>
	<br/><br/><br/>
	<p>If you are unsure of the photo location reading the <a href="article/Anleitung">guide</a> (currently only available in German) might help.</p>

	<script type="text/javascript" src="{"/mapping1.js"|revision}"></script>
	<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
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
	
	{if !$user->stats.images || $user->stats.images < 100 || !$last_imagetaken}
	<div style="color:black; background-color:yellow; font-size:0.7em; padding:3px; border: 1px solid orange">Please avoid submitting images with overlaid text or borders; they should be cropped before submission. Thank you for your attention to this matter.<br/><br/>
	You should only submit photos you have taken yourself, or where you can specifically act as a Licensor on behalf of the original author.</div><br/>
	{/if}
	
	{if $rastermap->enabled}
		<div style="float:left;width:50%;position:relative">
	{else}
		<div>
	{/if}
		{if $imagecount gt 0}
			<div style="color:#440000">We currently have 
			{if $imagecount eq 1}an image{else}{$imagecount} images{/if} {if $totalimagecount && $totalimagecount > $imagecount} ({$totalimagecount} including hidden){/if} 
			uploaded for {newwin title="View Images for `$gridref`" href="/gridref/`$gridref`" text=`$gridref`}, add yours now!</div>
		{else}
			<p style="color:#004400">Fantastic! We don't yet have an image for {$gridref}! {if $totalimagecount && $totalimagecount ne $imagecount} (but you have {$totalimagecount} hidden){/if}</p>
		{/if}

		{if $transfer_id}
		<img src="{$preview_url}" width="{$preview_width*0.2|string_format:"%d"}" height="{$preview_height*0.2|string_format:"%d"}" alt="low resolution reminder image"/>	
		<input name="transfer_id" type="hidden" value="{$transfer_id|escape:"html"}"/>
		{elseif $jpeg_url}
		<label for="jpeg_url"><b>JPEG Image URL</b></label>
		<input id="jpeg_url" name="jpeg_url" type="text" size="40" value="{$jpeg_url|escape:"html"}"/>
		{else}
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
		<label for="jpeg"><b>JPEG Image File</b></label>
		<input id="jpeg" name="jpeg" type="file" />
		
		{if $picnik_api_key}<br/>or <input type="submit" name="picnik" value="Upload Image via Picnik.com"/><span style="color:red">New!</span>{/if}
		
		{/if}
		<div><small><small style="color:gray"><i>If your image is over 640 pixels in either direction, it will be resized. If you have presized please aim to have the filesize under 100kb and in anycase under 200kb, thanks!</i></small></small></div>
		{if $error}<br /><p style="color:#990000;font-weight:bold;">{$error}</p>{/if}
		<br />

		{if $reference_index == 2} 
		{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland" target="_blank"} includes 1:50,000 mapping for Northern Ireland.
		{/if}
		
		{*if $last_grid_reference || $last_photographer_gridref}
			<div style="font-size:0.8em">
			<a href="javascript:{if $last_photographer_gridref}void(document.theForm.photographer_gridref.value = '{$last_photographer_gridref}');void(updateMapMarker(document.theForm.photographer_gridref,false));{/if}{if $last_grid_reference}void(document.theForm.grid_reference.value = '{$last_grid_reference}');void(updateMapMarker(document.theForm.grid_reference,false));{/if}">Copy from Last Submission</a> <sup style="color:red">New</sup></div>
		{else}
		
		{/if*}

		<h4><b>Grid References:</b> (recommended)</h4>
		<p><label for="grid_reference"><b style="color:#0018F8">Primary Photo Subject</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{if $square->natspecified}{$grid_reference|escape:'html'}{/if}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks the Subject" width="20" height="34" align="middle"/>{/if}
		<span style="font-size:0.8em"><br/><a href="javascript:void(mapMarkerToCenter(document.theForm.grid_reference));void(updateMapMarker(document.theForm.photographer_gridref,false));" style="font-size:0.8em">Marker to Center</a></span>
		</p>
	
		<p><label for="photographer_gridref"><b style="color:#002E73">Photographer Position</b></label> <input id="photographer_gridref" type="text" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks the Photographer" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Marks the Photographer" width="20" height="34" align="middle"/>{/if}
		
		<span style="font-size:0.8em"><br/><a href="javascript:void(document.theForm.photographer_gridref.value = document.theForm.grid_reference.value);void(updateMapMarker(document.theForm.photographer_gridref,false));" style="font-size:0.8em">Copy from Subject</a></span>
		
		{if $rastermap->enabled}
			<br/><br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1"/> <label for="use6fig">Only display 6 figure grid reference ({newwin href="/help/map_precision" text="Explanation"})</label>
		{/if}
		</p>
	
		<p><label for="view_direction"><b>View Direction</b></label> <small>(photographer facing)</small><br/>
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
		{if $rastermap->service == 'Google'}
			<a href="#" onclick="this.style.display='none';document.getElementById('map').style.width = '100%';document.getElementById('map').style.height = '400px';map.checkResize();return false">Enlarge Map</a>
		{/if}{*FIXME move to FootNote?*}
		{if count($square->services) > 1}
		{*<form method="get" action="/gridref/{$gridref}">*}{*FIXME*}
		<p>Karte:
		<select name="sid">
		{html_options options=$square->services selected=$sid}
		</select>
		<input type="submit" name="newmap" value="Los"/></p>{*</form>*}
		{/if}
		
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					{/literal}{if $view_direction == -1}
						updateViewDirection();
					{/if}{literal}
				}
				AttachEvent(window,'load',updateMapMarkers,false);
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}

	<br/>
	<input type="submit" name="goback" value="&lt; Back"/> <input type="submit" name="upload" value="Next &gt;" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) {return autoDisable(this);} else {return false}{/literal}"/>
	<br style="clear:both"/>

	{if $totalimagecount gt 0}
	<br/>
	<div class="interestBox">
		<div><b>Latest {$shownimagecount} images for this square...</b></div>

	{foreach from=$images item=image}

	  <div class="photo33" style="float:left;width:150px; height:170px; background-color:white">{newwin title="`$image->title` by `$image->realname` - click to view full size image"|escape:'html' href="/photo/`$image->gridimage_id`" text=$image->getThumbnail(120,120,false,true)}
	  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a></div>
	  </div>

	{/foreach}
	<br style="clear:both"/>
	
	{if $imagecount gt 6 || $shownimagecount == 6}
		<div>{newwin href="/gridref/`$gridref`" text="View `$imagecount` live image(s) for `$gridref`"}<small> plus any images you have hidden</small></div>
	{/if}&nbsp;
	</div>
	{else}
		<br style="clear:both"/>
	{/if}
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
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
<p>So what makes an image a genuine geograph?</p>
<ul>
<li>The image subject must be within grid square {$gridref}, and ideally the photographer should be too.</li>
<li>You must clearly show at close range one of the main geographical features within the square</li>
<li>You should include a short description relating the image to the map square</li>
<li>The image should be a natural image as a human would see it, please avoid digitally altering the image to add dates, texts etc, or creating montages, similarly turn off date stamping performed by a camera. A little tweaking of brightness/contrast and/or cropping is fine and encouraged. </li>
</ul>

<p>Good quality, visually appealing and historically relevant pictures (eg wide area views
covering many square kilometres) may also be accepted as supplemental images 
for {$gridref} provided they are accurately located, but may not qualify as geographs.</p>

<ul>
<li>We welcome many Geograph or Supplemental images per square, so even if you don't get the point, you are still making a valuable contribution to the project.</li>
</ul>

</div>

<p>If you like, you can provide more images or extra information (which
can be edited at any time) but to activate a square you need to be first to meet the
criteria above!</p>

<div class="interestBox" style="width:30em;z-index:0"><a href="/submit_popup.php?t={$reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Reopen Map in a popup</a><br/>
{newwin href="/gridref/`$gridref`" text="Open `$gridref` Page"}</div>

<h3>Title and Comments</h3>
<p>Please provide a short title for the image, and any other comments about where
it was taken or other interesting geographical information. It is not required to provide texts in both languages. English texts may be translated by a moderator. <span id="styleguidelink">({newwin href="/help/style" text="Open Style Guide"})</span></p>

<p><label for="title"><b>German Title</b></label> {if $error.title}
	<br/><span class="formerror">{$error.title}</span>
	{/if}<br/>
<input size="50" maxlength="128" id="title" name="title" value="{$title|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);"/> <span class="formerror" style="display:none" id="titlestyle">Possible style issue. See Guide above. <span id="titlestylet" style="font-size:0.9em"></span></span></p>
<p><label for="title2"><b>English Title</b></label> {if $error.title2}
	<br/><span class="formerror">{$error.title2}</span>
	{/if}<br/>
<input size="50" maxlength="128" id="title2" name="title2" value="{$title2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title2',true);" onkeyup="checkstyle(this,'title2',false);"/> <span class="formerror" style="display:none" id="title2style">Possible style issue. See Guide above. <span id="title2stylet" style="font-size:0.9em"></span></span></p>
 {if $place.distance}
 <p style="font-size:0.7em">Gazetteer info as will appear:<br/> <span style="color:silver;">{place place=$place}</span></p>
 {/if}

<p style="clear:both"><label for="comment"><b>German Description/Comment</b></label> <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span></span><br/>
<textarea id="comment" name="comment" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$comment|escape:'html'}</textarea></p>
<p style="clear:both"><label for="comment2"><b>English Description/Comment</b></label> <span class="formerror" style="display:none" id="comment2style">Possible style issue. See Guide above. <span id="comment2stylet"></span></span><br/>
<textarea id="comment2" name="comment2" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment2',true);" onkeyup="checkstyle(this,'comment2',false);">{$comment2|escape:'html'}</textarea></p>
<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TPT2769]]</span> or <span style="color:blue">[[34]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>


<h3>Further Information</h3>

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
AttachEvent(window,'load',onChangeImageclass,false);
//-->
</script>
{/literal}

<p><label for="imageclass"><b>Primary geographical category</b></label> {if $error.imageclass}
	<br/><span class="formerror">{$error.imageclass}</span>
	{/if}<br />	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onfocus="prePopulateImageclass()" onmouseover="mouseOverImageClass()" style="width:300px">
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
	
	
	
	
<p><label><b>Date photo taken</b></label> {if $error.imagetaken}
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
<h3>Image Classification</h3>

<p><label for="user_status">I wish to suggest supplemental classification:</label> <input type="checkbox" name="user_status" id="user_status" value="accepted" {if $user_status == "accepted"}checked="checked"{/if}/> (tick to apply)</p>

<p>Tick this box only if you believe your photograph is not a 'geograph'. The moderator will just use the box as a suggestion, so if you are not sure, leave it unticked. Note: There can be many geograph images per square.</p>

<p>Remembering the points <a href="#geograph">above</a> about what makes a 'geograph', <span class="nowrap">more information can be found in the {newwin href="/faq.php#goodgeograph" text="FAQ"} or in this {newwin href="http://www.geograph.org.uk/article/Geograph-or-supplemental" text="article on how images are moderated"}.</span></p>
</div>

<p>
<input type="hidden" name="upload_id" value="{$upload_id}"/>
<input type="hidden" name="savedata" value="1"/>
<input type="submit" name="goback" value="&lt; Back"/>
<input type="submit" name="next" value="Next &gt;"/></p>

<script type="text/javascript" src="/categories.js.php"></script>
<script type="text/javascript" src="/categories.js.php?full=1&amp;u={$user->user_id}"></script>

{else}
	<input type="hidden" name="title" value="{$title|escape:'html'}"/>
	<input type="hidden" name="title2" value="{$title2|escape:'html'}"/>
	<input type="hidden" name="comment" value="{$comment|escape:'html'}"/>
	<input type="hidden" name="comment2" value="{$comment2|escape:'html'}"/>
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
	<input type="hidden" name="user_status" value="{$user_status|escape:'html'}"/>
{/if}

{if $step eq 4}
	<input type="hidden" name="upload_id" value="{$upload_id}"/>
	<input type="hidden" name="title" value="{$title|escape:'html'}"/>
	<input type="hidden" name="title2" value="{$title2|escape:'html'}"/>
	<input type="hidden" name="comment" value="{$comment|escape:'html'}"/>
	<input type="hidden" name="comment2" value="{$comment2|escape:'html'}"/>
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
	<input type="hidden" name="user_status" value="{$user_status|escape:'html'}"/>
	
	{if $original_width && $largeimages}
	
		<h2>Submit Step 4 of 4: Confirm image size and rights</h2>
		
		{include file="_submit_sizes.tpl"}
		
		<hr/>
	{else}
		<h2>Submit Step 4 of 4 : Confirm image rights</h2>
	{/if}

	{if $canclearexif}
		<input type="checkbox" name="clearexif" id="clearexif" {if $wantclearexif}checked{/if} value="1"/> <label for="clearexif">Clear any EXIF data from the image. Check this box to hide metadata such as exact creation time or camera type.</label><!--br/-->
		<hr/>
	{/if}

	{if $user->stats.images && $user->stats.images > 100 && $last_imagetaken}

	<div style="border:1px solid gray; padding:10px">I've read this already, <input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);autoDisable(this.form.finalise[1]);"/><br/> (saves scrolling to the bottom)</div>
	{/if}
	
	<p>
	Because we are an open project we want to ensure our content is licensed
	as openly as possible and so we ask that all images are released under a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
	licence, including accompanying metadata.</p>
	
	<p>With a Creative Commons licence, the photographer <b>keeps the copyright</b> but allows 
	people to copy, modify and distribute the work provided they <b>give credit</b>.</p>
	
	<p>Therefore, we ask that you
	allow the following</p>
	
	<ul>
	<li>The right to modify the work to create derivative works</li>
	<li>The right to distribute the work and derivative works</li>
	</ul>
	
	<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
	
	{assign var="credit" value=$user->credit_realname}
	{assign var="credit_default" value=0}
	{include file="_submit_licence.tpl"}
	
	<p>If you do
	not agree with these terms, click "I do not agree" and your upload will
	be abandoned.<br />
	<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
	
	</p>


	<p>If you agree with these terms, click "I agree" and your image will be
	stored in grid square {$gridref}.<br />
	<input type="submit" name="goback3" value="&lt; Back"/>
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);{if $user->stats.images && $user->stats.images > 100 && $last_imagetaken}autoDisable(this.form.finalise[0]);{/if}"/>
	</p>
	


{/if}

{if $step eq 5}
<h2>Submission Complete!</h2>
<p>Thank you very much - your photo has now been added to grid square 
<a title="Grid Reference {$gridref}" href="/gridref/{$gridref}">{$gridref}</a>.</p>
<p>Your photo has identification number [<a href="/photo/{$gridimage_id}">{$gridimage_id}</a>]</p>


<p><a title="submit another photo" href="/submit.php">Click here to submit a new photo...</a></p>
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

{if $step eq 3}

	<script type="text/javascript">{literal}
	function previewImage() {
		window.open('','_preview');//forces a new window rather than tab?
		var f1 = document.forms['theForm'];
		var f2 = document.forms['previewForm'];
		for (q=0;q<f2.elements.length;q++) {
			if (f2.elements[q].name && f1.elements[f2.elements[q].name]) {
				f2.elements[q].value = f1.elements[f2.elements[q].name].value;
			}
		}
		return true;
	}
	{/literal}</script>
	<form action="/preview.php" method="post" name="previewForm" target="_preview" style="background-color:lightgreen; padding:10px; text-align:center">
	<input type="hidden" name="grid_reference"/>
	<input type="hidden" name="photographer_gridref"/>
	<input type="hidden" name="view_direction"/>
	<input type="hidden" name="use6fig"/>
	<input type="hidden" name="title"/>
	<input type="hidden" name="title2"/>
	<textarea name="comment" style="display:none"/></textarea>
	<textarea name="comment2" style="display:none"/></textarea>
	<input type="hidden" name="imageclass"/>
	<input type="hidden" name="imageclassother"/>
	<input type="hidden" name="imagetakenDay"/>
	<input type="hidden" name="imagetakenMonth"/>
	<input type="hidden" name="imagetakenYear"/>
	<input type="hidden" name="upload_id"/>
	<input type="submit" value="Preview Submission in a new window" onclick="previewImage()"/> 
	
	<input type="checkbox" name="spelling"/>Check Spelling
	<sup style="color:red">Experimental!</sup>
	</form>
{/if}

{if $preview_url}
{if !$enable_forums}
	<div style="position:fixed;right:10px;bottom:10px;display:none;background-color:silver;padding:2px;font-size:0.8em;width:148px" id="hidePreview">
{else}
	<div style="position:fixed;left:10px;bottom:10px;display:none;background-color:silver;padding:2px;font-size:0.8em;width:148px" id="hidePreview">
{/if}
	<div id="previewInner"></div></div>

<script type="text/javascript">
{literal}
function showPreview(url,width,height,filename) {
	height2=Math.round((148 * height)/width);
	document.getElementById('previewInner').innerHTML = '<img src="'+url+'" width="148" height="'+height2+'" id="imgPreview" onmouseover="this.height='+height+';this.width='+width+'" onmouseout="this.height='+height2+';this.width=148" /><br/>'+filename;
	document.getElementById('hidePreview').style.display='';
}
 AttachEvent(window,'load',function () {showPreview({/literal}'{$preview_url}',{$preview_width},{$preview_height},'{$filename|escape:'javascript'}'{literal}) },false);

{/literal}
</script>

{/if}


{/dynamic}
{include file="_std_end.tpl"}
