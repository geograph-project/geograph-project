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
	 border-top: 1px solid lightgrey;
	 background-color:#f9f9f9;
}
{/literal}
</style>

<div style="float:right; position:relative; text-align:center; width:180px; border:1px solid red; padding:10px; background-color:lightgrey">Code for pasting into a Geograph related page <input type=text size="10" value="[[[{$image->gridimage_id}]]]" readonly="readonly"/></div>

<div style="float:left; position:relative; padding-right:10px;"><h2><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" align="top" /></a> <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px" class="nowrap"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>


<p>Thank you for your interest in this photo, you are more than welcome to use this image as long as you follow a few basic requirements:</p>

<ul class="checklist">
<li style="background-color: yellow;">We do ask you be polite and not abuse Geograph's resources in using the image irresponsibly on your own site. For your convenience we have created some approved ways to reuse this image, such as mentioning this image in forum posts.</li>

<li>So <b>do not hotlink</b> the fullsize image directly off our servers, this will likely be blocked.</li>

<li>Instead download a copy, and upload it to your own site. Click: <b><a href="{$script_name}?id={$image->gridimage_id}&amp;download">Download Fullsize .jpg file</a></b></li>

<li style="font-size:1.1em; background-color:pink;"><b>Under the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image MUST be credited as specified by the contributor ({if $image->credit_realname}<tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>{else}credited to {$image->realname|escape:'html'}{/if}).</b></li>

<li><b>You should also mention that the photos is copyrighted but licenced as such for further reuse.</b> If you alter, transform, or build upon this work, you may distribute the resulting work only under a similar license.</li>

<li>Ideally you could link back to the main photo page, at <a href="http://{$http_host}/photo/{$image->gridimage_id}">http://{$http_host}/photo/{$image->gridimage_id}</a></li>
</ul>

<div style="text-align:right"><i>Thank you for your attention in this matter.</i></div>

<br/>

<div class="photoguide" style="margin-left:auto;margin-right:auto">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}">
		{$image->getThumbnail(213,160)}
		</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>
	</div>
	<div style="float:left;padding-left:20px; width:400px;">{$image->comment|escape:'html'|nl2br|geographlinks|default:"<tt>no description for this image</tt>"}</div>
	
	<br style="clear:both"/>
</div>

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



<h3>BBCode for reusing image on a forum</H3>

<form><textarea rows="3">[url=http://{$http_host}/photo/{$image->gridimage_id}][b]{$image->title|escape:'html'}[/b]
[img]{$image->getThumbnail(213,160,true)}[/img][/url]
&copy; Copyright [url=http://{$http_host}{$image->profile_link}]{$image->realname|escape:'html'}[/a] and  
licensed for reuse under this [url=http://creativecommons.org/licenses/by-sa/2.0/]Creative Commons Licence[/url].</textarea></form>

<h3>BBCode for reusing image on a forum (with shorter message)</H3>

<form><textarea rows="3">[url=http://{$http_host}/photo/{$image->gridimage_id}][b]{$image->title|escape:'html'}[/b]
[img]{$image->getThumbnail(120,120,true)}[/img][/url]
&copy; [url=http://{$http_host}{$image->profile_link}]{$image->realname|escape:'html'}[/a], [url=http://creativecommons.org/licenses/by-sa/2.0/]cc-by-sa[/url].</textarea></form>


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

<h3>{external href="http://en.wikipedia.org/wiki/Keyhole_Markup_Language" text="KML"} - geographic exchange format</h3>
<p>The automatic
<a title="Open in Google Earth" href="http://{$http_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> file we produce for the image, is another great way of sharing the image.</p>


<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}
{include file="_std_end.tpl"}
