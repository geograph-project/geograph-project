{dynamic}
{assign var="page_title" value="Reuse Image"}
{include file="_std_begin.tpl"}

<!-- **************************************************
!! ATTENTION SCRAPERS !!

... reading this page wanting to figure how to download images? 

DON'T! We have proper APIs if you want to download images. 
https://www.geograph.org.uk/help/api

Automated access to this page is likly to be blocked, not to mention unreliable.
**************************************************** -->

<style type="text/css">
{literal}
textarea {
  margin: auto;
  display: block;
  width: 90%;
	padding: 5px;
  font-size: 0.9em;
  background-color: white;
}
ul.checklist {
        max-width:750px;
}
ul.checklist li {
        padding:10px;
}

div:target {
        border:2px solid orange;
        padding:20px;
        background-color: lightgrey;
}
.top { text-align:right;font-size:0.7em; }
.top A { text-decoration:none; }

.sized b {
	font-family:times;
}


#wikipedia span.nowrap { /* has some external links, that automatically nowrap links! */
	white-space: normal;
	word-break: normal;
}


{/literal}
</style>
<a name="top"></a>

{*Image basics*}

<div class="reuse-header-grid">

<div class="reuse-header-title"><h2><a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : <a title="View this image on its photo page" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2></div>

<div class="reuse-header-copyright">&copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a></b> and	licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</div>

<div class="reuse-header-geoid">Geograph image ID:<wbr/> [[[{$image->gridimage_id}]]]</div>

</div>


<div class="reuse-image">

{*
{$image->getFull($returnfull,$responsive,$larger)}

1. Always pass true to the first param in a smarty template. 
2. Make image responsive
3. Use large image if available (2 must be true for 3 to be true) 
*}
<a href="/photo/{$image->gridimage_id}"{if $image->original_width} title="Scroll down for high resolution version"{/if}>{$image->getFull(true,true,false)}</a>
</div>

<div class="reuse-ccbox-grid">
<div class="reuse-ccbox-logo"><a href="http://creativecommons.org/licenses/by-sa/2.0/"><img	alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="left" /></a></div>
<div class="reuse-ccbox-message"><b>&copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a></b> and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</div>
</div>




<br/><br/>


{*Setup tabs*}
{assign var="tab" value="1"}
<div class="tabHolder" style="margin:auto;max-width:940px">
<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,5)">License</a>
<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,5)">Crediting</a>
<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,5)">Download original</a>
<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,5)">Stamped image</a>
<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab5" onclick="tabClick('tab','div',5,5)">Reusable code</a>
</div>

{*Licence tab*}
<div style="{if $tab != 1}display:none{/if};" class="reuse-tabs" id="div1">
<h3>Image license</h3>

<p>All photos on Geograph are <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licensed</a>. In general, as long as you credit the photographer when you use the image, you can use it for most purposes.</p>

<div class="reuse-copyright-warning">Using this image requires you comply with the Licence requirements, unless <b>explicit</b> permission has been obtained from the copyright holder.</div>

<br/>

<div class="reuse-ccdeed-grid">
<div class="reuse-ccdeed-requirements">
<ul class="checklist">

<li>Under the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image <b>must</b> be credited to <b>{$image->realname|escape:'html'}</b>. {if $image->credit_realname}<br/>(The contributor <tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>){/if}</li>

<li>You <i>should</i> mention that whilst the photo is copyrighted it is licensed for further reuse.</li>

<li>If you alter, transform, or build upon this work, you may only distribute the resulting work under a similar licence.</li>

<li><b>CAUTION:</b> You may require additional permissions to reuse images. <b>Do not assume</b> that the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a> is sufficient permission for your intended reuse, particularly where the reuse is commercial.<br/>For example, images taken on private property may require additional permission (for instance this is a requirement for images taken on National Trust land).</li>

</ul>
</div>
<div class="reuse-ccdeed-image"><a href="http://creativecommons.org/licenses/by-sa/2.0/"><img loading="lazy" src="{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed"/></a><br/>
<a href="http://creativecommons.org/licenses/by-sa/2.0/">Full Licence Deed</a>
</div>

</div>

<p>Examples of good wording for compliant use of the creative commons licensing can be found in the 'Credits' tab. There are also examples of code to copy and paste for reuse online in the 'Reusable code' box.</p>



</div>

{*Crediting tab*}
<div style="{if $tab != 2}display:none{/if};" class="reuse-tabs" id="div2">
<h3>Crediting images</h3>

<p>This page provides examples of credits for images which are compliant with the terms of the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons License</a>. You may choose the form which best suits your usage of the photo. You may also find the 'stamped image' tab useful, where you can download versions of this photo with a credit directly added to the image.</p>

<ul>
<li>You may copy and paste any of the text credits below and use them with or without the hyperlinks.</li>
<li>We would encourage that you include a link to the photo page, at <a href="{$self_host}/photo/{$image->gridimage_id}" style="word-break: break-all">{$self_host}/photo/{$image->gridimage_id}</a> when reusing the image.</li>
<li>You may also wish to use the <a href="https://creativecommons.org/mission/downloads/">Creative Commons badges</a> to go alongside the copyright text.</li>
</ul>


<h4>Creative Commons</h4>
<div class="reuse-credit-example">
<b><a href="{$self_host}/photo/{$image->gridimage_id}">Photo</a> &copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a></b> and licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a> Creative Commons Licence.
</div>

<h4>With title and date</h4>

<div class="reuse-credit-example">
<b>&copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></b> (<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)
<a href="{$self_host}/photo/{$image->gridimage_id}">geograph.org.uk/p/{$image->gridimage_id}</a><br/>
{$image->title|escape:'html'}</b>{if $image->imagetakenString}, taken {$image->imagetakenString}{/if}
</div>

<h4>Minimalist<h4>

<div class="reuse-credit-example">
<a href="{$self_host}/photo/{$image->gridimage_id}">Photo</a> <b>&copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></b> (<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)
</div>

</div>

{*Download tab*}
<div style="{if $tab != 3}display:none{/if};" class="reuse-tabs" id="div3">
<h3>Download</h3>

<p>Links are provided below to download the image previewed above. Any images downloaded need to be displayed alongside an appropriate image credit in order to comply with the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</p>

<div class="reuse-reminders">
<ul>
<li>When displaying this photo, your <b>must include the copyright attribution</b> in line with the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</li>
<li>We would encourage that you include a link to the photo page, at <a href="{$self_host}/photo/{$image->gridimage_id}" style="word-break: break-all">{$self_host}/photo/{$image->gridimage_id}</a> when reusing the image.</li>
</ul>
</div>

{if basename($image->altUrl) != "error.jpg"}
<div class="reuse-diffimage">
Note that we have two versions for this image, which differ slightly. This is likely to have arisen from submission of the image at a smaller size, and a larger version has subsequently been uploaded with minor differences. The initial set of download links are provided for the larger image. A preview for the image which was originally submitted is then shown, along with a download link for this version.</div>
{/if}

{if $image->original_width}
{assign var="original_width" value=$image->original_width}
{assign var="original_height" value=$image->original_height}
    
<ul>

{if $image->originalSize}<li>Largest available size: <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=original">{$original_width} &#x00d7; {$original_height} px</a> <wbr>(Filesize: {$image->originalSize|thousends} bytes)</li>{/if} 

{if $original_width > 3000 || $original_height > 3000}<li>Longest side: <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=3000">3000 px</a></li>{/if}
{if $original_width > 1600 || $original_height > 1600}<li>Longest side: <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=1600">1600 px</a></li>{/if}
{if $original_width > 1024 || $original_height > 1024}<li>Longest side: <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=1024">1024 px</a></li>{/if}
{if $original_width > 800 || $original_height > 800}<li>Longest side: <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=800">800 px</a></li>{/if}

<li><a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">{$image->cached_size.0} &#x00d7; {$image->cached_size.1} px</a></li>

</ul>


{if basename($image->altUrl) != "error.jpg"}<img src="{$image->altUrl}" crossorigin onerror="retryCross(this)"/><br>
<ul>
<li><a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=640">max 640 px</a>{/if}</li>
</ul>

{else}
<ul>
<li><a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">{$image->cached_size.0} &#x00d7; {$image->cached_size.1} px</a></li>
</ul>
{/if}



<p>We also offer images where the required attribution has been added to the image. See the 'stamped images' tab to obtain an image with the attribution displayed on the image.</p>

</div>

{*Stamped tab*}
<div style="{if $tab != 4}display:none{/if};" class="reuse-tabs" id="div4">

<h3>Stamped images</h3>

<p>You can download versions of this image with the required attribution directly placed onto the image. These image files comply with the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a>, provided that the attribution is visible and isn't removed from the image (eg through cropping).</p>

<div class="reuse-stamped-image">
<h3>Stamped image preview</h3>

<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&download=1" id="stamp_link">
<img src="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}" loading="lazy" id="stamp_preview" style="max-width:100%" crossorigin onload="this.style.opacity=1;" onerror="retryCross(this)"></a>

<ul>
<li>To download the image either:
<ul>
<li>Left click on the image to to start the download</li>
<li>Or right click on the image and select '<i>Save image as...</i>'</li>
</ul>
</li>
</ul>

</div>

<div class="reuse-stamping-options">
<h3>Stamping options</h3>

<p>You can configure options for the attribution which appears on the stamped image. You can edit any of the options below, then click 'Update stamped image' to apply the settings and update the preview image above.</p>


	<form name=theForm action="{$tile_host}/stamp.php" method="get" target="_blank" onsubmit="return submit_stamp()">
	<script>
	let tile_host = '{$tile_host}';
	{literal}
	function submit_stamp() {
	        //we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object.
		if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
			jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');

			$(function() {
				submit_stamp_inner();
			});
		} else {
			submit_stamp_inner();
		}
		return false;
	}
	function submit_stamp_inner() {
		document.getElementById('stamp_preview').style.opacity=0.1; //onload event will undo this
		let str = $('form[name="theForm"]').serialize();
		document.getElementById('stamp_preview').src = tile_host+'/stamp.php?'+str;
		document.getElementById('stamp_link').href = tile_host+'/stamp.php?'+str;
	}
	</script>
	{/literal}


<input type=hidden name=id value="{$image->gridimage_id}"/>


<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=large>Image size</label></div>
<div class="reuse-stampingopt-input">
  <select name=large>
		<option value=0>Base size ({$image->cached_size.0}x{$image->cached_size.1})</option>
		{if $original_width > 800 || $original_height > 800}
			<option value="800">800 Nominal</option>
		{/if}
		{if $original_width > 1024 || $original_height > 1024}
			<option value="1024">1024 Nominal</option>
		{/if}
		{if $original_width > 1600 || $original_height > 1600}
			<option value="1600">1600 Nominal</option>
		{/if}
		{if  $image->original_width && $image->originalSize}
			<option value="1">Full Size ({$original_width}x{$original_height} px)</option>
		{/if}
	</select>
</div>
<div class="reuse-stampingopt-desc">Select the size of the image.</div>
</div>
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=title>Include image title</label></div>
<div class="reuse-stampingopt-input"><input type=checkbox name=title id=title{if $image->cached_size.0 > 500} checked{/if}></div>
<div class="reuse-stampingopt-desc">Select whether the image title appears in the watermark.</div>
</div>
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=link>Hide geograph link</label></div>
<div class="reuse-stampingopt-input"><input type=checkbox name=link id=link value=0></div>
<div class="reuse-stampingopt-desc">Remove the link to the photo page on Geograph.</div>
</div>
{if $image->grid_square->reference_index == 2}
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=ie>Use Irish webdomain link (geograph.ie)</label></div>
<div class="reuse-stampingopt-input"><input type=checkbox name=ie id=ie value=1></div>
<div class="reuse-stampingopt-desc">Toggle between geograph.org.uk and geograph.ie web addresses.</div>
</div>
{/if}
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=invert>Invert text color</label></div>
<div class="reuse-stampingopt-input"><input type=checkbox name=invert id=invert value=1 /></div>
<div class="reuse-stampingopt-desc">Invert the text colour for the label.</div>
</div>
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=font>Font</label></div>
<div class="reuse-stampingopt-input">
<select name=font><option></option><option value="Bookman-Demi">Bookman-Demi</option><option value="Bookman-Light">Bookman-Light</option><option value="Courier">Courier</option><option value="fixed">fixed</option><option value="Helvetica">Helvetica</option><option value="Helvetica-Narrow">Helvetica-Narrow</option><option value="Times-Roman">Times-Roman</option><option value="Century-Schoolbook-L-Roman">Century-Schoolbook-L-Roman</option><option value="DejaVu-Sans">DejaVu-Sans</option><option value="DejaVu-Sans-Mono">DejaVu-Sans-Mono</option><option value="URW-Bookman-L-Light">URW-Bookman-L-Light</option><option value="URW-Gothic-L-Book">URW-Gothic-L-Book</option><option value="URW-Gothic-L-Demi">URW-Gothic-L-Demi</option><option value="URW-Palladio-L-Roman">URW-Palladio-L-Roman</option></select>
</div>
<div class="reuse-stampingopt-desc">Select the font to be used for the label.</div>
</div>
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=gravity>Text location</label></div>
<div class="reuse-stampingopt-input">
<select name=gravity><option></option><optgroup label="Horizontal text"><option value="Center">Center</option><option value="East">East</option><option value="NorthEast">NorthEast</option><option value="North">North</option><option value="NorthWest">NorthWest</option><option value="SouthEast">SouthEast</option><option value="South" selected>South</option><option value="SouthWest">SouthWest</option><option value="West">West</option></optgroup><optgroup label="Vertical Text"><option value="left">left</option><option value="right">right</option></optgroup></select>
</div>
<div class="reuse-stampingopt-desc">Select the location on the photo for the label to appear.</div>
</div>
<br/>
<div class="reuse-stampingopt-grid">
<div class="reuse-stampingopt-param"><label for=pointsize>Type size</label></div>
<div class="reuse-stampingopt-input">
<select name=pointsize><option></option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="48">48</option><option value="64">64</option><option value="96">96</option><option value="128">128</option></select> pt
</div>
<div class="reuse-stampingopt-desc">Select the type size in points for the label.</div>
</div>
<br/>

<input type=hidden name=download value=1>
<div style="text-align:center;"><input type="submit" value="Update stamped image" style="width:600px; max-width:100%; cursor: pointer; line-height: 2em; font-size: 1.1em; font-weight:bold; background-color: #b7e1cd;"/></div>
</form>

</div>

{if $image->original_width}
<p>Please note that the text may be difficult to see in the preview above if a large image size and/or a small font size are selected.</p>
{/if}

</div>

{*Reuse tab*}
<div style="{if $tab != 5}display:none{/if};" class="reuse-tabs" id="div5">
<h3>Obtain code for use</h3>

<p>This page provides preformatted code which can be used to display this image on websites with attribution which is compliant with the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</p>

<div class="reuse-reminders">
<ul>
<li>When displaying this photo, your <b>must include the copyright attribution</b> in line with the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</li>
<li>We would encourage that you include a link to the photo page, at <a href="{$self_host}/photo/{$image->gridimage_id}" style="word-break: break-all">{$self_host}/photo/{$image->gridimage_id}</a> when reusing the image.</li>
</ul>
</div>
<br/>

<div class="reuse-hotlink-warning">
<h3>&#x26A0; Hotlinking and bulk downloads &#x26A0;</h3>

<ul class="checklist">

<li><b>We do ask you to be polite and not abuse the Geograph website resources.</b></li>
<li><b>{external href="http://en.wikipedia.org/wiki/Inline_linking" text="Hotlinking"}</b> the image directly off our servers will trigger automatic watermarks and may be blocked</li>

<li><b>Don't bulk download images from this page</b> - Automated access to this page is likly to be blocked (and unreliable). We have proper <a href="/help/api">APIs</a> if you want to bulk download images.</li>


</ul>

</div>

<br/><br/>

<div class="input-toggle-accordion">

<input type="checkbox" id="accordion1" />
<label for="accordion1">HTML Embed Code</label>

<div class="content" id="html">



<h4>Example HTML snippet, for use on a website</h4>

<div class="reuse-codepreview">
<div style="text-align:center; max-width: 100%;">
<div>{$image->getFull(true,true,false)}</div>
<div><a href="{$self_host}/photo/{$image->gridimage_id}">Photo</a> &copy; <a href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> (<a href="https://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)</div>
<div>{$image->title|escape:'html'}{if $image->imagetakenString}, {$image->imagetakenString}{/if}</div>
<div style="font-size:0.9em;">Photo available for reuse under this <a href="https://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a>.</div>
</div>
</div>

<br/><br/>

<form>
<textarea rows="8">
<div style="text-align:center; max-width: 100%;">
<div>&lt;img src&#61;&quot;geograph-{$image->gridimage_id}-by-{$image->realname|escape:'html'|replace:' ':'-'}.jpg" alt="{$image->title|escape:'html'}, by {$image->realname|escape:'html'}" width="{$image->cached_size.0}" height="{$image->cached_size.1}" style="height: auto; max-width:95%;"&gt;</div>
<div><a href="{$self_host}/photo/{$image->gridimage_id}">Photo</a> &amp;copy; <a href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> (<a href="https://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)</div>
<div>{$image->title|escape:'html'}{if $image->imagetakenString}, {$image->imagetakenString}{/if}</div>
<div style="font-size:0.9em;">Photo available for reuse under this <a href="https://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a>.</div>
</textarea>
</form>

<p>Please remember to <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">download ({$image->cached_size.0} &#x00d7; {$image->cached_size.1} px)</a> and host your own copy of the image, rather than hotlinking it from Geograph servers. {if $original_width}Additional image sizes are available from the download tab.{/if}</p>
</div>


<input type="checkbox" id="accordion2" />
<label for="accordion2">Forum code for BB forums</label>


<div class="content" id="bbcode">
<h4>BBCode for reusing image on a non-Geograph forum</H4>

<form><textarea rows="4">[b][url={$self_host}/photo/{$image->gridimage_id}]{$image->title|escape:'html'}
[img]{$image->getThumbnail(213,160,true)}[/img][/url][/b]
&copy; Copyright [url={$self_host}{$image->profile_link}]{$image->realname|escape:'html'}[/url] and  
licensed for reuse under this [url=http://creativecommons.org/licenses/by-sa/2.0/]Creative Commons Licence[/url].</textarea></form>

<h4>BBCode with shorter creative commons message</h4>

<form><textarea rows="3">[url={$self_host}/photo/{$image->gridimage_id}][b]{$image->title|escape:'html'}[/b]
[img]{$image->getThumbnail(120,120,true)}[/img][/url]
&copy; [url={$self_host}{$image->profile_link}]{$image->realname|escape:'html'}[/a], [url=http://creativecommons.org/licenses/by-sa/2.0/]cc-by-sa[/url].</textarea></form>
</div>


<input type="checkbox" id="accordion3" />
<label for="accordion3">Wikipedia</label>

<div class="content" id="wikipedia">
<h4>Wikipedia Template for image page.</h4>

<p>You can {external href="https://commons.wikimedia.org/wiki/Special:Upload?`$smarty.capture.wikiuploadparams`"|escape:'html' text="directly upload this image to Wikimedia Commons"}.  You will need to add some categories, but that link will automatically fill in the <a href="{$smarty.capture.wikiurl|escape:'html'}">download link</a> and the file description template below.</p>

<div class="reuse-wikipedia-warning">
<h3>&#x26A0; Does Wikimedia already have the image? &#x26A0;</h3>

<ul class="checklist">
<li>Wikimedia Commons has carried out uploading of Geograph images to their servers in bulk.</li>
<li>Please make sure the image hasn't already been uploaded.</li>
<li>{external href="http://commons.wikimedia.org/w/index.php?title=Special:Search&search=geograph+`$image->gridimage_id`&amp;fulltext=Search&amp;ns6=1" text="This search should find it if it has been uploaded"}.</li>
</ul>
</div>

<br/>

{capture name=wikitext}== {literal}{{int:filedesc}}{/literal} ==
{literal}{{{/literal}Information
|Description={literal}{{{/literal}en|1={$image->title}{literal}}}{/literal}
|Source=From [{$self_host}/photo/{$image->gridimage_id} geograph.org.uk]
{if $image->imagetaken && strpos($image->imagetaken,'0000') !== 0}
|Date={$image->imagetaken|replace:'-00':''}
{else}
|Date={$image->submitted|date_format:'%Y-%m-%dT%H:%M:%S+00:00'}
{/if}
|Author=[{$self_host}{$image->profile_link} {$image->realname}]
|Permission=Creative Commons Attribution Share-alike license 2.0
|Other fields={literal}{{{/literal}Credit line
 |Author={$image->realname}
 |License=[https://creativecommons.org/licenses/by-sa/2.0/ CC BY-SA 2.0]
 |Other=''{$image->title}''
{literal} }}
}}{/literal}
{if $photographer_lat}
{literal}{{{/literal}Location|{$photographer_lat|string_format:"%.6f"}|{$photographer_long|string_format:"%.6f"}|source:geograph-{if $image->grid_square->reference_index==1}osgb36{else}irishgrid{/if}({$image->getPhotographerGridref(false)}){if $image->view_direction > -1}_heading:{$image->view_direction}{/if}|prec={$image->photographer_gridref_precision}{literal}}}{/literal}
{/if}
{literal}{{{/literal}Object location|{$lat|string_format:"%.5f"}|{$long|string_format:"%.5f"}|source:geograph-{if $image->grid_square->reference_index==1}osgb36{else}irishgrid{/if}({$image->getSubjectGridref(false)}){if $image->view_direction > -1}_heading:{$image->view_direction}{/if}|prec={$image->subject_gridref_precision}{literal}}}{/literal}

== {literal}{{int:license-header}}{/literal} ==
{literal}{{{/literal}geograph|{$image->gridimage_id}|{$image->realname}{literal}}}{/literal}{/capture}
{capture name=wikiurl}{$self_host}{$script_name}?id={$image->gridimage_id}&download={$image->_getAntiLeechHash()}{if $image->original_width}&size=original{/if}{/capture}
{capture name=wikiuploadparams}wpSourceType=url&wpUploadFileURL={$smarty.capture.wikiurl|escape:url}&wpUploadDescription={$smarty.capture.wikitext|escape:'url'}&wpDestFile={$image->title|escape:'url'}%20(geograph%20{$image->gridimage_id}).jpg{/capture}


<form><textarea rows="17" id="wikitext">{$smarty.capture.wikitext|escape:'html'}</textarea><br/>
This template includes the {external href="http://commons.wikimedia.org/wiki/Template:Information" text="information box"} with the relevent data (title, links and licence), {external href="http://commons.wikimedia.org/wiki/Template:Location" text="geotags the image"}, as well as the specific {external href="http://commons.wikimedia.org/wiki/Template:Geograph" text="Geograph Template"}</form>
</div>


</div>



</div>


{*Sticky footer*}
<br/><br/>


<div class="reuse-donate-grid">
<div class="reuse-donate-button"><a href="/help/donate" style="background-color:purple;color:white;text-decoration:none;font-size:1.1em;padding:4px;margin:5px;border-radius:4px">Donate/Support Us</a></div>
<div class="reuse-donate-useful">Found Geograph useful?</div>
<div class="reuse-donate-message">Please consider <a href="https://www.geograph.org.uk/help/donate">donating</a> to support the Geograph Project!</div>
<div class="reuse-donate-link"><a href="https://cafdonate.cafonline.org/18714" target="_blank" title="Donate to us (Link opens in a new window)">Donate via Charities Aid Foundation</a></div>
</div>

{/dynamic}


{include file="_std_end.tpl"}
