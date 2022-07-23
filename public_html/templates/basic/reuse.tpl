{dynamic}
{assign var="page_title" value="Reuse Image"}
{include file="_std_begin.tpl"}
<style type="text/css">
{literal}
textarea {
        width:90%;
	padding:5px;
        font-size:0.9em;
        background-color:#eeeeee
}
ul.checklist {
        width:750px;
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

.previewHover > div {
	display:inline-block;
	position:relative;width:580px;height:22px;overflow:hidden;
	background-position:right bottom;
	background-repeat:no-repeat;
	box-shadow: inset 0px 5px 3px 0px rgba(255,255,255,1);
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
    <p align=center style="width:750px;margin-left:auto;margin-right:auto;text-align:center">All photos on Geograph are Creative Commons licensed.
    In general, as long as you credit the photographer (see below) when you use the image, you can use it for most purposes. </p>

	<div class="interestBox" style="width:750px;margin-left:auto;margin-right:auto;text-align:center">
	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
	alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="left" /></a>
	<b>&copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a></b> and  
	licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.<br style=clear:both>
	</div>
<br>

{if $image->isLandscape()}{/if}

<table width=750 align=center>
	<tr>
		<td valign=top><h3>Stamped Image</h3>
			<p>Image file with credit added (to comply with Creative Commons licence). As long as don't crop this off, can just use this image without needing to add any further text etc. </p>
			<p>Don't like size/placement of text? Can <a href="/stamp.php?id={$image->gridimage_id}">customize the text &gt;</a></p>
		</td>

		<td>
			<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&download=1"><img 
                            src="{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}" width=250></a>
		</td>
	</tr>

{if $image->cached_size.0 > 300}
	<tr>
		<td colspan=2 align=right class="previewHover">
			Actual size:
			<div style='background-image:url("{$tile_host}/stamp.php?id={$image->gridimage_id}{if $image->cached_size.0 > 500}&title=on{/if}&gravity=SouthEast&hash={$image->_getAntiLeechHash()}")'>
			</div>
		</td>
	</tr>
{/if}

	<tr>
		<td valign=top><h3>Standard Image</h3>
			<p>You can download the image without the credit, but <b>will need to make sure you credit the image manually</b>. 

			<small>Using this image requires you comply with the Licence requirements, unless have <b>explicit</b> permission from the copyright holder. <br><br>

			<i>Here is a sample credit, can directly copy/paste, with or without the links:</i></small>

			<p style="border:2px solid lightgreen;padding:5px"><b>{$image->title|escape:'html'}</b><br>
			<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a> - 
			&copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> - <a href="{$self_host}/photo/{$image->gridimage_id}">geograph.org.uk/p/{$image->gridimage_id}</a></p>

			<small><i>or more minimalistic version:</i></small>
			<p style="border:2px solid lightgreen;padding:5px;text-align:right">
				Photo &copy; <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a>
				(<a href="http://creativecommons.org/licenses/by-sa/2.0/">cc-by-sa/2.0</a>)
			</p>
			(scroll down the page, for more detailed instructions)
		</td>

		<td valign=top align=center>
			<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}"><img src="{$image->_getFullpath(true,true)}" width=250></a><br>
			<small class="sized">(<b>{$image->cached_size.0}</b>x<b>{$image->cached_size.1}</b>px jpeg)</small>

	                <p style="color:red">
				<b>Right click the Image and<br> select 'Save Image as'<br> (or similar)</b>
	                </o>

		</td>
	</tr>
{if $image->original_width}
        <tr>
                <td valign=top><h3>High-Resolution</h3>
                        <p>Versions <a href="/more.php?id={$image->gridimage_id}" class="sized">upto <b>{$image->original_width}</b>x<b>{$image->original_height}</b>px are available &gt;</a>
                </td>

                <td align=center>
			or quick download of a<br>
			 <a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&large=1{if $image->original_width > 800}&pointsize=24{/if}&download=1" class="sized"><b>stamped</b> <b>{$image->original_width}</b>x<b>{$image->original_height}</b> image</a><br>
			(<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=original">unstamped</a>)
                </td>
        </tr>
{/if}
</table>

	<div class="interestBox" style="width:750px;margin-left:auto;margin-right:auto;text-align:center">
	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
	alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="left" /></a>
	<b>&nbsp; &copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a> and  
	licensed for reuse under <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">creativecommons.org/licenses/by-sa/2.0</a></b>
	</div>


<br><br>

<div  style="width:750px;margin-left:auto;margin-right:auto;">
	You are more than welcome to use it as long as you <b>follow a few basic requirements:</b>

   <div style="width:230px;float:right;position:relative;text-align:center;font-size:0.7em">
   	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img src="{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed" style="padding-left:20px"/></a><br/>
   	[ Click to see full Licence Deed ]
   </div>

<ul class="checklist">

<li>Under the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image <b>must</b> be credited to <b>{$image->realname|escape:'html'}</b>. An example of good wording for web use (including the hyperlinks) is shown in the above box. {if $image->credit_realname}<small><br/>(The contributor <tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>)</small>{/if}</li>

<li>You should also mention that the photo is copyrighted but also licensed for further reuse.</li>

<li>If you alter, transform, or build upon this work, you may distribute the resulting work only under a similar licence.</li>

<li><b>CAUTION:</b> You may require further permissions to reuse images, especially those taken on private property, for instance the National Trust. <b>Do not assume</b> that the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a> is sufficient permission for your intended reuse, particularly if that reuse is commercial.</li>

</ul>

<p><b>Web based project?</b></p>

<ul class="checklist">

<li><b>We do ask you to be polite and not abuse the Geograph website resources.</b> <br/>
<small>&nbsp; <img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="16" height="16"/>
{external href="http://en.wikipedia.org/wiki/Inline_linking" text="Hotlinking"} the image directly off our servers will trigger automatic watermarks and may be blocked</small><br/><br/>
</li>

<li>ideally include a link to our photo page, at <a href="{$self_host}/photo/{$image->gridimage_id}">{$self_host}/photo/{$image->gridimage_id}</a>, where the latest information will be available.</li>

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
<img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
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
<small>This template includes the {external href="http://commons.wikimedia.org/wiki/Template:Information" text="information box"} with the relevent data (title, links and licence), {external href="http://commons.wikimedia.org/wiki/Template:Location" text="geotags the image"}, as well as the specific {external href="http://commons.wikimedia.org/wiki/Template:Geograph" text="Geograph Template"}</small></form>
</div>
<div class="top"><a href="#top">back to top</a></div>



<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}

<p>We've recently revamped this page. If you have any feedback on this, please let us know below!

		<div id="showfeed2" class="interestBox"><form method="post" action="/stuff/feedback.php">
		<label for="feedback_comments"><b>Your Feedback</b>:</label><br/>
		<input type="text" name="comments" size="80" id="feedback_comments"/><input type="submit" name="submit" value="send"/>
		{dynamic}{if $user->registered}<br/>
		<small>(<input type="checkbox" name="nonanon" checked/> <i>Tick here to include your name with this comment, so we can then reply</i>)</small>
		{else}<br/>
		<i><small>If you want a reply please use the <a href="/contact.php">Contact Us</a> page. We are <b>unable</b> to reply to comments left here.</small></i>
		{/if}{/dynamic}
		<input type="hidden" name="template" value="{$smarty_template}"/>
		<input type="hidden" name="referring_page" value="{$smarty.server.HTTP_REFERER}"/>
		    <div style="display:none">
		    <br /><br />
		    <label for="name">Leave Blank!</label><br/>   
			<input size="40" id="name" name="name" value=""/>
		    </div>
		</form></div>
		<br/>



{include file="_std_end.tpl"}
