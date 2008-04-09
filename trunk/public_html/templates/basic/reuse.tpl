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
{/literal}
</style>



<div style="border:1px solid red;background-color: yellow; padding:10px;">
	<div>Thank you for your interest in this photo, you are more than welcome to use this image as long as you follow a few basic requirements as laid out below in the link the creative commons deed. However we ask you be polite and not abuse Geograph's resources in using the image irresponsibly on your own site. For your convenience we have created some approved ways to quote this image in forum posts.</div><br/><br/>
	<div>So do not hotlink the fullsize image directly off our servers, this will likely be blocked. Instead download a copy, and upload it to your own site. <b><a href="?image={$image->gridimage_id}&amp;download">Download it here</a></b></div>
</div>

<div style="border:1px solid red;background-color: pink; padding:10px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/>
	<b>Under the <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>, the image MUST be credited as specified by the contributor <br/>({if $image->credit_realname}<tt>{$image->user_realname|escape:'html'}</tt> has specifed the image is credited to <tt>{$image->realname|escape:'html'}</tt>{else}{$image->realname|escape:'html'}{/if}).<br/><br/>
	You should also mention that the photos is licenced as such for further reuse.</b> The examples below show some ways to do that.<br/><br/>
	Ideally also you could link back to the main photo page, at <a href="http://{$http_host}/photo/{$image->gridimage_id}">http://{$http_host}/photo/{$image->gridimage_id}</a><br/><br/>
	<div style="float:right"><i>Thank you for your attention in this matter.</i></div>
	<br style="clear:both"/>
</div>

<h3>Here is a preview of the selected image</h3>
<div class="photoguide">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}">
		{$image->getThumbnail(213,160)}
		</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
	</div>
	<div style="float:left;padding-left:20px; width:400px;">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
	
	<br style="clear:both"/>
</div>

<h3>HTML text link for this Image</H3>

<div class="ccmessage" style="background-color:yellow"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<form><textarea rows="3"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &amp;nbsp; &amp;copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.</textarea></form>


<h3>HTML for reusing image on a webpage</H3>

<div style="background-color:yellow;padding:10px;width:230px; text-align:center; float:left">
<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a><br/>
<a href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a><br/>
&nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<form><textarea rows="3"><div style="width:210px; text-align:center">
<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a><br/>
<a href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a><br/>
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
<br/><br/>
<h3>Code for pasting into a Geograph related page <input type=text size="10" value="[[[{$image->gridimage_id}]]]"/></H3>


<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}
{include file="_std_end.tpl"}
