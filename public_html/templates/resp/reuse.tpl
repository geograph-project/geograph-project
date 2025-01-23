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
        width:90%;
	padding:5px;
        font-size:0.9em;
        background-color:#eeeeee
}
ul.checklist {
        max-width:750px;
}
ul.checklist li {
        padding:10px;
}
h3 {
        padding:10px;
        border-top: 1px solid lightgrey;
        background-color:#f9f9f9;
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

.imagePreview {
	max-width:650px;
	margin:auto;
	text-align:center;
}
.imagePreview img {
	max-width:100%;
	height:auto;
}

#wikipedia span.nowrap { /* has some external links, that automatically nowrap links! */
	white-space: normal;
	word-break: normal;
}

{/literal}
</style>
<a name="top"></a>

<div style="float:right">
	Geograph ID:<br/> [[[{$image->gridimage_id}]]]
</div>

<div style="float:left; position:relative; padding-right:10px;"><h2><a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>

<br style="clear:both;"/>
    <p align=center style="max-width:750px;margin-left:auto;margin-right:auto;text-align:center">All photos on Geograph are Creative Commons licensed.
    In general, as long as you credit the photographer (see below) when you use the image, you can use it for most purposes. </p>

	<div class="interestBox" style="max-width:750px;margin-left:auto;margin-right:auto;text-align:center">
	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
	alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="left" /></a>
	<b>&copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a></b> and  
	licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.<br style=clear:both>
	</div>
<br>

{assign var="tab" value="1"}
<div class="tabHolder" style="margin:auto;max-width:940px">
	<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,2)">Normal Image</a>
	<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,2)">Stamped Image</a>
</div>
<div style="position:relative;{if $tab != 1}display:none{/if};margin:auto;max-width:940px" class="interestBox" id="div1">

	<p>You can download the image without the credit, but <b>will need to make sure you credit the image manually</b>. 

	Using this image requires you comply with the Licence requirements, unless have <b>explicit</b> permission from the copyright holder. <br><br>

	<i>Here is a sample credit, can directly copy/paste, with or without the links:</i> (scroll down the page, for more detailed instructions)

	<p style="background-color:white;border-radius:10px;padding:10px"><b>{$image->title|escape:'html'}</b>{if $image->imagetakenString}, taken {$image->imagetakenString}{/if}<br>
	<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a> - 
	<b>&copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></b> - <a href="{$self_host}/photo/{$image->gridimage_id}">geograph.org.uk/p/{$image->gridimage_id}</a></p>

	<i>or more minimalistic version:</i>
	<p style="background-color:white;border-radius:10px;padding:5px;text-align:right">
		Photo <b>&copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></b>
		(<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)
	</p>
	<br>

	<div class="imagePreview">
	{$image->getFull()}<br>

	{if $image->original_width}
		Download:
		{assign var="original_width" value=$image->original_width}
		{assign var="original_height" value=$image->original_height}
		<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}"><b>{$image->cached_size.0}</b>x<b>{$image->cached_size.1}</b>px jpeg</a>
		{if basename($image->altUrl) != "error.jpg"}
			&middot; <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=640">640</a>
		{/if}
		{if $original_width > 800 || $original_height > 800}
			&middot; <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=800">800</a>
		{/if}
		{if $original_width > 1024 || $original_height > 1024}
			&middot; <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=1024">1024</a>
		{/if}
		{if $original_width > 1600 || $original_height > 1600}
			&middot; <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=1600">1600</a>
		{/if}
		{if $original_width > 3000 || $original_height > 3000}
			&middot; <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=3000">3000</a>
		{/if}
		{if $image->originalSize}
			&middot; <b><a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=original">FullSize ({$original_width}x{$original_height} px)</a></b>
			(Filesize: {$image->originalSize|thousends} bytes)
		{/if}
	{else} 
		<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">Download</a>
		(<b>{$image->cached_size.0}</b>x<b>{$image->cached_size.1}</b>px jpeg)
	{/if}
	</div>

</div>
<div style="position:relative;{if $tab != 2}display:none{/if};margin:auto;max-width:940px" class="interestBox" id="div2">

	Image file with credit added (to comply with Creative Commons licence). As long as don't crop this off, can just use this image without needing to add any further text etc.

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

	Size: <select name=large>
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
	&nbsp; Options:
	<input type=checkbox name=title id=title{if $image->cached_size.0 > 500} checked{/if}><label for=title>Include image title</label>,
	<input type=checkbox name=link id=link value=0><label for=link>Hide geograph link</label>,
	{if $image->grid_square->reference_index == 2}
		<input type=checkbox name=ie id=ie value=1><label for=ie>.ie link</label>
	{/if}
	<hr/>

	font:<select name=font><option></option><option value="Bookman-Demi">Bookman-Demi</option><option value="Bookman-Light">Bookman-Light</option><option value="Courier">Courier</option><option value="fixed">fixed</option><option value="Helvetica">Helvetica</option><option value="Helvetica-Narrow">Helvetica-Narrow</option><option value="Times-Roman">Times-Roman</option><option value="Century-Schoolbook-L-Roman">Century-Schoolbook-L-Roman</option><option value="DejaVu-Sans">DejaVu-Sans</option><option value="DejaVu-Sans-Mono">DejaVu-Sans-Mono</option><option value="URW-Bookman-L-Light">URW-Bookman-L-Light</option><option value="URW-Gothic-L-Book">URW-Gothic-L-Book</option><option value="URW-Gothic-L-Demi">URW-Gothic-L-Demi</option><option value="URW-Palladio-L-Roman">URW-Palladio-L-Roman</option></select> &nbsp; gravity:<select name=gravity><option></option><option value="Center">Center</option><option value="East">East</option><option value="NorthEast">NorthEast</option><option value="North">North</option><option value="NorthWest">NorthWest</option><option value="SouthEast">SouthEast</option><option value="South" selected>South</option><option value="SouthWest">SouthWest</option><option value="West">West</option><option value="left">left</option><option value="right">right</option></select> &nbsp; pointsize:<select name=pointsize><option></option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="48">48</option><option value="64">64</option><option value="96">96</option><option value="128">128</option></select> &nbsp; 		<input type=checkbox name=invert id=invert value=1 /><label for=invert>invert text color</label>
	<hr/>
	<input type=hidden name=download value=1>
	<input type="submit" value="Get Stamped Image &gt;&gt;"/>
	(right click image below and select 'Save As...' to download)
	</form>

	<div class="imagePreview">

	<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&download=1" id="stamp_link"><img
            src="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}"
            loading="lazy" id="stamp_preview" style="max-width:100%" crossorigin onload="this.style.opacity=1;" onerror="retryCross(this)"></a>
	{if  $image->original_width}
	<br>Note: if the image is high resolution, the text may be too small to see in this preview, but should be in the full version when download.
	{/if}

	</div>

</div>

<br><br>


	<div class="interestBox" style="max-width:750px;margin-left:auto;margin-right:auto;text-align:center">
	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
	alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="left" /></a>
	<b>&nbsp; &copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a> and  
	licensed for reuse under <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" style="word-break: break-all">creativecommons.org/licenses/by-sa/2.0</a></b>
	</div>


<br><br>

<div  style="max-width:750px;margin-left:auto;margin-right:auto;">
	You are more than welcome to use it as long as you <b>follow a few basic requirements:</b>

   <div style="width:230px;float:right;position:relative;text-align:center;font-size:0.7em">
	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img loading="lazy" src="{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed" style="padding-left:20px"/></a><br/>
	[ Click to see full Licence Deed ]
   </div>

<ul class="checklist">

<li>Under the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image <b>must</b> be credited to <b>{$image->realname|escape:'html'}</b>. An example of good wording for web use (including the hyperlinks) is shown in the above box. {if $image->credit_realname}<br/>(The contributor <tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>){/if}</li>

<li>You should also mention that the photo is copyrighted but also licensed for further reuse.</li>

<li>If you alter, transform, or build upon this work, you may distribute the resulting work only under a similar licence.</li>

<li><b>CAUTION:</b> You may require further permissions to reuse images, especially those taken on private property, for instance the National Trust. <b>Do not assume</b> that the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a> is sufficient permission for your intended reuse, particularly if that reuse is commercial.</li>

</ul>

<p><b>Web based project?</b></p>

<ul class="checklist" style="list-style-type: &quot;\26A0&quot;">

<li><b>We do ask you to be polite and not abuse the Geograph website resources.</b> <br/><br>
{external href="http://en.wikipedia.org/wiki/Inline_linking" text="Hotlinking"} the image directly off our servers will trigger automatic watermarks and may be blocked
</li>

<li>Reading this page wanting to figure how to scrape images? 
DON'T! We have proper <a href="/help/api">APIs</a> if you want to download images.
Automated access to this page is likly to be blocked, not to mention unreliable.</li>

<li>ideally include a link to our photo page, at <a href="{$self_host}/photo/{$image->gridimage_id}" style="word-break: break-all">{$self_host}/photo/{$image->gridimage_id}</a>, where the latest information will be available.</li>

</ul>

<br/><br/>
</div>

<div style="text-align:center;">Thank you for your attention in this matter.</div>
<br/><br/>


<div style="background-color:#ffffae;padding:10px;text-align:center;">

	Found Geograph useful? Please consider <a href="https://www.geograph.org.uk/help/donate">donating</a> to support the Geograph Project!
	&nbsp;&nbsp;
	<a href="https://cafdonate.cafonline.org/18714" target="_blank" title="Donate to us (Link opens in a new window)">Make a donation via Charities Aid Foundation</a>

</div>

<br/><br/>
<br/><br/>
<br/><br/>

<div id="html">
<a name="html"></a>
<h3>Example HTML snippet, for use on a website</h3>

<form><textarea rows="7"><div style="display:inline-block; font-size:0.9em">
	&lt;img src&#61;&quot;geograph-{$image->gridimage_id}-by-{$image->realname|escape:'html'|replace:' ':'-'}.jpg" alt="{$image->title|escape:'html'}, by {$image->realname|escape:'html'}" width="{$image->cached_size.0}" height="{$image->cached_size.1}"&gt;<br>
	<div style="float:right;">Photo &amp;copy; <a href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>
	<a href="{$self_host}/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>{if $image->imagetakenString}, {$image->imagetakenString}{/if}

	<div style="text-align:right; font-size:0.9em">Available for reuse under this <a href="https://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a></div>
</div></textarea></form>
(please remember to <a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">download</a> and host your own copy of the image, rather than hotlinking it from Geograph servers)
</div>
<div class="top"><a href="#top">back to top</a></div>
<br><br>


<div id="bbcode">
<a name="bbcode"></a>
<h3>BBCode for reusing image on a non-Geograph forum</H3>

<form><textarea rows="4">[b][url={$self_host}/photo/{$image->gridimage_id}]{$image->title|escape:'html'}
[img]{$image->getThumbnail(213,160,true)}[/img][/url][/b]
&copy; Copyright [url={$self_host}{$image->profile_link}]{$image->realname|escape:'html'}[/url] and  
licensed for reuse under this [url=http://creativecommons.org/licenses/by-sa/2.0/]Creative Commons Licence[/url].</textarea></form>

<h3>BBCode with shorter creative commons message</H3>

<form><textarea rows="3">[url={$self_host}/photo/{$image->gridimage_id}][b]{$image->title|escape:'html'}[/b]
[img]{$image->getThumbnail(120,120,true)}[/img][/url]
&copy; [url={$self_host}{$image->profile_link}]{$image->realname|escape:'html'}[/a], [url=http://creativecommons.org/licenses/by-sa/2.0/]cc-by-sa[/url].</textarea></form>
</div>
<div class="top"><a href="#top">back to top</a></div>
<br><br>




<div id="wikipedia">
<a name="wikipedia"></a>
<h3>Wikipedia Template for image page.</h3>

<div class="interestBox" style="padding:10px">
<img loading="lazy" src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
Wikimedia Commons has recently undertaken to upload Geograph images in bulk, so please make sure the image hasn't already been uploaded. 
<b>{external href="http://commons.wikimedia.org/w/index.php?title=Special:Search&search=insource%3A%22geograph+`$image->gridimage_id`%22&amp;fulltext=Search&amp;ns6=1" text="This search should find it if it has been"}</b>.</div>
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

You can {external href="https://commons.wikimedia.org/wiki/Special:Upload?`$smarty.capture.wikiuploadparams`"|escape:'html' text="directly upload this image to Wikimedia Commons"}.  You will need to add some categories, but that link will automatically fill in the <a href="{$smarty.capture.wikiurl|escape:'html'}">download link</a> and the file description template below.<br/><br/>

<form><textarea rows="17" id="wikitext">{$smarty.capture.wikitext|escape:'html'}</textarea><br/>
This template includes the {external href="http://commons.wikimedia.org/wiki/Template:Information" text="information box"} with the relevent data (title, links and licence), {external href="http://commons.wikimedia.org/wiki/Template:Location" text="geotags the image"}, as well as the specific {external href="http://commons.wikimedia.org/wiki/Template:Geograph" text="Geograph Template"}</form>
</div>
<div class="top"><a href="#top">back to top</a></div>



<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}


{include file="_std_end.tpl"}
