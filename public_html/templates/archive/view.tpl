{include file="_std_begin.tpl"}

{if $image}

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="{$sitemap}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
 {place place=$place h3=true}
{/if}

{if $image->moderation_status eq 'rejected'}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">

<h3 style="color:black"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Rejected</h3>

<p>This photograph has been rejected by the site moderators, and is only viewable by you.</p>

<p>You can find any messages related to this image on the <a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">edit page</a>, where you can reply or raise new concerns in the "Please tell us what is wrong..." box, which will be communicated with site moderators. You may also like to read this general article on common <a href="/article/Reasons-for-rejection">Reasons for rejection</a>.

</div>
<br/>
{/if}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto">{$image->getFull()}</div>
  
  <div class="caption" style="font-weight:bold" xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title">{$image->title|escape:'html'}</div>

  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
  {/if}
  {if $snippets}
	{foreach from=$snippets item=item name=used}

		<div style="margin-left:auto;margin-right:auto;width:640px;text-align:left;font-size:0.8em;padding-top:3px">
			<small class="searchresults">{if $snippets_as_ref}{$smarty.foreach.used.iteration}. {/if}
			<a href="/snippet.php?id={$item.snippet_id}" class="text">{$item.title|escape:'html'}</a> {if $item.grid_reference != $image->grid_reference}<small> :: <a href="/gridref/{$item.grid_reference}" class="text">{$item.grid_reference}</a></small> {/if}<br/></small>
			<div style="font-size:0.9em">{$item.comment|escape:'html'}</div>
		</div>

	{/foreach}
  {/if}
</div>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="{$image->_getFullpath(true,true)}" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->

{if $image_taken && $image->imagetaken < 2009}
<div class="keywords" style="top:-3em;float:right;position:relative;font-size:0.8em;height:0em;z-index:-10" title="year photo was taken">year taken <div style="font-size:3em;line-height:0.5em">{$image->imagetaken|truncate:4:''}</div></div>
{/if}

<div class="buttonbar" style="border:1px solid silver">
</div>




<div class="picinfo">

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative">
	{$rastermap->getImageTag($image->subject_gridref)}
	{if $rastermap->getFootNote()}
	<div class="interestBox" style="margin-top:3px;margin-left:2px;padding:1px;"><small>{$rastermap->getFootNote()}</small></div>
	{/if}
	</div>

	{$rastermap->getScriptTag()}
{else}
	<div class="rastermap" style="width:{$rastermap->width}px;height:{$rastermap->width}px;position:relative">
		Map Coming Soon...
	
	</div>
{/if}

<div style="float:left;position:relative"><dl class="picinfo" style="margin-top:0px">



<dt>Grid Square</dt>
 <dd><a title="Grid Reference {$image->grid_reference}" href="{$sitemap}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if}
</dd>

{if $image->credit_realname}
	<dt>Photographer</dt>
	 <dd property="dc:creator">{$image->realname|escape:'html'}</dd>

	<dt>Contributed by</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}">{$image->user_realname|escape:'html'}</a></dd>
{else}
	<dt>Photographer</dt>
	 <dd><a title="View profile" href="{$image->profile_link}" property="dc:creator">{$image->realname|escape:'html'}</a></dd>
{/if}

<dt>Image classification</dt>
<dd>{if $image->ftf}
	Geograph (First for {$image->grid_reference})
{else}
	{if $image->moderation_status eq "rejected"}
	Rejected
	{/if}
	{if $image->moderation_status eq "pending"}
	Awaiting moderation
	{/if}
	{if $image->moderation_status eq "geograph"}
	Geograph
	{/if}
	{if $image->moderation_status eq "accepted"}
	Supplemental image
	{/if}
{/if}</dd>


{if $image_taken}
<dt>Date Taken</dt>
 <dd>{$image_taken}</dd>
{/if}
<dt>Submitted</dt>
	<dd>{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>

<dt>Category</dt>

<dd>{if $image->imageclass}
	{$image->imageclass}
{else}
	<i>n/a</i>
{/if}</dd>

<dt>Subject Location</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$image->subject_gridref} [{$image->subject_gridref_precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>

{if $image->photographer_gridref}
<dt>Photographer Location</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$image->photographer_gridref}</dd>
{/if}

{if $view_direction && $image->view_direction != -1}
<dt>View Direction</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{$view_direction} (about {$image->view_direction} degrees)</dd>
{/if}

</dl>

</div>

{if $overview}
  <div style="float:left; text-align:center; width:{$overview_width}px; position:relative">
	{include file="_overview.tpl"}
	<div style="width:inherit;margin-left:20px;"><br/>

	<a href="{$sitemap}">Text listing of Images in {$image->grid_reference}</a>


	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>







{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
