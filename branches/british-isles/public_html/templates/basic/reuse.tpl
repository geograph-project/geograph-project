{dynamic}
{assign var="page_title" value="Reuse Image"}
{include file="_std_begin.tpl"}
<style type="text/css">
{literal}
textarea {
	width:100%; 
	font-size:0.9em;
	background-color:#eeeeee
}
.checklist LI {
	padding:10px;
}
h3 {
	padding:10px;
	margin-top:30px;
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

{/literal}
</style>
<a name="top"></a>

<div style="float:right; position:relative; text-align:center; width:180px; border:1px solid red; padding:10px; background-color:lightgrey">Code for pasting into a Geograph related page <input type=text size="10" value="[[[{$image->gridimage_id}]]]" readonly="readonly"/></div>

<div style="float:left; position:relative; padding-right:10px;"><h2><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" align="top" /></a> <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px" class="nowrap"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>

<br style="clear:both;"/>

<div class="photoguide" style="margin-left:auto;margin-right:auto; ">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}">
		{$image->getThumbnail(213,160)}
		</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>
	</div>
	<div style="float:left;padding-left:20px; width:400px;">
		<span style="font-size:0.7em">{$image->comment|escape:'html'|nl2br|geographlinks|default:"<tt>no description for this image</tt>"}</span><br/>
		<br/>
		<small><b>&nbsp; &copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
		licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a></b></small>
	</div>
	
	<br style="clear:both"/>
</div>

   <div style="width:230px;float:right;position:relative;text-align:center;font-size:0.7em">
   	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img src="http://{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed"/></a><br/>
   	[ Click to see full Licence Deed ]
   </div>

<p><i>Thank you for your interest in this photo, you are more than welcome to use it as long as you follow a few basic requirements:</i></p>

<ul class="checklist">

<li style="border: 3px solid pink;"><b>Under the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image <b>must</b> be credited as specified by the contributor, an example of good wording is shown above.<small><br/>({if $image->credit_realname}The contributor <tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>{else}The contributor of this photos is <tt style="color:red;font-size:1.3em">{$image->realname|escape:'html'}</tt>{/if})</small></b></li>

<li><b>You should also mention that the photo is copyrighted but also licensed for further reuse.</b> <small>If you alter, transform, or build upon this work, you may distribute the resulting work only under a similar licence.</small></li>

</ul>

<p><i>and if a web based project:</i></p>

<ul class="checklist">

<li style="border: 3px solid pink;"><b>We do ask you be polite and not abuse the Geograph website resources.</b> <br/>
<i>Please do not {external href="http://en.wikipedia.org/wiki/Inline_linking" text="hotlink"} the fullsize image directly off our servers, this will likely be blocked.</i><br/>
Instead download a copy, and upload it to your own webspace. <b>
{if $image->isLandscape()}{/if}
<a href="{$script_name}?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">Download {if !$image->original_width}fullsize{/if} {$image->cached_size.0}x{$image->cached_size.1}px JPEG file</a>
{if $image->original_width}
or <a href="/more.php?id={$image->gridimage_id}">View other Sizes Available...</a>
{/if}
</b></li>

<li><small>Ideally you should link back to the main photo page, at <a href="http://{$http_host}/photo/{$image->gridimage_id}">http://{$http_host}/photo/{$image->gridimage_id}</a>, where the latest information for the photo will be available.</small></li>
</ul>

<p><i>For offline reproduction, wording similar to the following is recommended:</i></p>

<form><textarea rows="3">Image Copyright {$image->realname|escape:'html'}. This work is licensed under the Creative Commons Attribution-Share Alike 2.0 Generic License. To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/2.0/ or send a letter to Creative Commons, 171 Second Street, Suite 300, San Francisco, California, 94105, USA.</textarea></form>

<br/><br/>

<div style="text-align:right; border-bottom:1px solid gray"><i>Thank you for your attention in this matter.</i></div>
<br/><br/>

<p>We have created some snippets of code to make using the photo easy on various websites or similar:</p>
<br/>
<div class="interestBox">Jump snippets for: <a href="#html">HTML</a>, <a href="#bbcode">Forums</a> or <a href="#wikipedia">Wikipedia</a>. Or get <a href="#meta">CC-metadata</a> (RDF) or <a href="#kml">KML File</a></div>
<br/>



<div id="html">
<a name="html"></a>
<h3>HTML text link for this Image</H3>

<div class="ccmessage" style="border:2px solid yellow;padding:10px;"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<form><textarea rows="3"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &amp;nbsp; &amp;copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</textarea></form>


<h3>HTML for reusing image on a webpage</H3>

<div style="border:2px solid yellow;padding:10px;width:230px; text-align:center; float:left; position:relative">
<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a><br/>
<a href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a><br/>
&nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<form><textarea rows="3"><div style="width:210px; text-align:center">
<a title="{$image->title|escape:'html'} - click to view full size image" href="http://{$http_host}/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a><br/>
<a href="http://{$http_host}/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a><br/>
&amp;nbsp; &amp;copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div></textarea></form>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="bbcode">
<a name="bbcode"></a>
<h3>BBCode for reusing image on a forum (non Geograph)</H3>

<form><textarea rows="3">[b][url=http://{$http_host}/photo/{$image->gridimage_id}]{$image->title|escape:'html'}
[img]{$image->getThumbnail(213,160,true)}[/img][/url][/b]
&copy; Copyright [url=http://{$http_host}{$image->profile_link}]{$image->realname|escape:'html'}[/url] and  
licensed for reuse under this [url=http://creativecommons.org/licenses/by-sa/2.0/]Creative Commons Licence[/url].</textarea></form>

<h3>BBCode for reusing image on a forum (with shorter message)</H3>

<form><textarea rows="3">[url=http://{$http_host}/photo/{$image->gridimage_id}][b]{$image->title|escape:'html'}[/b]
[img]{$image->getThumbnail(120,120,true)}[/img][/url]
&copy; [url=http://{$http_host}{$image->profile_link}]{$image->realname|escape:'html'}[/a], [url=http://creativecommons.org/licenses/by-sa/2.0/]cc-by-sa[/url].</textarea></form>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="wikipedia">
<a name="wikipedia"></a>
<h3>Wikipedia Template for image page.</h3>

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
Wikimedia Commons has recently undertaken to upload Geograph images in bulk, so please make sure the image hasn't already been uploaded. 
<b>{external href="http://commons.wikimedia.org/w/index.php?title=Special:Search&search=geograph+_`$image->gridimage_id`.jpg&amp;fulltext=Search&amp;ns6=1" text="This search should find it if it has been"}</b>.</div>
<br/>

The following is the recommended template for using on the photo page. You should <a href="{$script_name}?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}">download the image</a>, and upload to {external href="http://commons.wikimedia.org/wiki/Main_Page" text="wikimedia commons"}.

<form><textarea rows="7" style="font-size:0.8em">== {literal}{{int:filedesc}}{/literal} ==
{literal}{{{/literal}Information
|Description={literal}{{{/literal}en|1={$image->title|escape:'html'}{literal}}}{/literal}
|Source=From [http://{$http_host}/photo/{$image->gridimage_id} geograph.org.uk]
{if $image->imagetaken && strpos($image->imagetaken,'0000') !== 0}
|Date={$image->imagetaken|replace:'-00':''}
{else}
|Date={$image->submitted|date_format:'%Y-%m-%dT%H:%M:%S+00:00'}
{/if}
|Author=[http://{$http_host}{$image->profile_link} {$image->realname|escape:'html'}]
|Permission=Creative Commons Attribution Share-alike license 2.0
{literal}}}{/literal}
{if $photographer_lat}
{literal}{{{/literal}Location dec|{$photographer_lat|string_format:"%.6f"}|{$photographer_long|string_format:"%.6f"}{if $image->view_direction > -1}|heading:{$image->view_direction}{/if}{literal}}}{/literal}
{else}
{literal}{{{/literal}Location dec|{$lat|string_format:"%.5f"}|{$long|string_format:"%.5f"}{if $image->view_direction > -1}|heading:{$image->view_direction}{/if}{literal}}}{/literal}
{/if}

== {literal}{{int:license}}{/literal} ==
{literal}{{{/literal}geograph|{$image->gridimage_id}|{$image->realname|escape:'html'}{literal}}}{/literal}</textarea><br/>
<small>This template includes the {external href="http://commons.wikimedia.org/wiki/Template:Information" text="information box"} with the relevent data (title, links and licence), {external href="http://commons.wikimedia.org/wiki/Template:Location" text="geotags the image"}, as well as the specific {external href="http://commons.wikimedia.org/wiki/Template:Geograph" text="Geograph Template"}</small></form>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="meta">
<a name="meta"></a>
<h3>Creative Commons Metadata for this image</h3>
<form><textarea rows="20" style="width:100%; font-size:0.8em"><!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &amp;nbsp; &amp;copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

--></textarea></form>
<div style="text-align:right"><i>this metadata is also embedded in all photo pages</i></div>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="kml">
<a name="kml"></a>
<h3>{external href="http://en.wikipedia.org/wiki/Keyhole_Markup_Language" text="KML"} - geographic exchange format</h3>
<p>The automatic
<a title="Open in Google Earth" href="http://{$http_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> file we produce for the image, is another great way of sharing the image.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}
{include file="_std_end.tpl"}
