{include file="_std_begin.tpl"}

{if $image}
<div style="float:right; position:relative; width:5em; height:4em;"></div>
<div style="float:right; position:relative; width:2.5em; height:1em;"></div>

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
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
{dynamic}
{if $search_keywords && $search_count}
	<div class="interestBox" style="text-align:center; font-size:0.9em">
		We have a least <b>{$search_count} images</b> that match your query [{$search_keywords|escape:'html'}] in the area! <a href="/search.php?searchtext={$search_keywords|escape:'url'}&amp;gridref={$image->grid_reference}&amp;do=1">View them now</a>
	</div>
{/if}
{/dynamic}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto">{$image->getFull()}</div>
  
  {literal}
  <script type="text/javascript">
  
  function redrawMainImage() {
  	el = document.getElementById('mainphoto');
  	el.style.display = 'none';
  	el.style.display = '';
  }
  AttachEvent(window,'load',redrawMainImage,false);
  AttachEvent(window,'load',showMarkedImages,false);
  
  </script>
  {/literal}
  
  <div class="caption"><b>{$image->title|escape:'html'}</b></div>

  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
  {/if}

</div>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->

<div class="buttonbar">

<table style="width:100%">
<tr>
	<td colspan="6" align="center" style="background-color:#c0c0c0;font-size:0.7em;"><b><a href="/reuse.php?id={$image->gridimage_id}">Find out how to reuse this Image</a></b> For example on your webpage, blog, a forum, or Wikipedia. </td>
</tr>
<tr>
{if $enable_forums}
<td style="width:50px"><a href="/discuss/index.php?gridref={$image->grid_reference}"><img src="http://{$static_host}/templates/basic/img/icon_discuss.gif" alt="Discuss" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
{if $discuss}
	There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a
	<a href="/discuss/index.php?gridref={$image->grid_reference}">discussion<br/>on {$image->grid_reference}</a> (preview on the left)
{else}
	<a href="/discuss/index.php?gridref={$image->grid_reference}#newtopic">Start a discussion on {$image->grid_reference}</a>
{/if}
</td>
{/if}

<td style="width:50px"><a href="/editimage.php?id={$image->gridimage_id}"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	{if $user->user_id eq $image->user_id}
		<big><a href="/editimage.php?id={$image->gridimage_id}"><b>Change Image Details</b></a></big><br/>
		(or raise a query with a moderator)
	{else}
		<a href="/editimage.php?id={$image->gridimage_id}">Suggest an Update to this Image</a>
	{/if}
</td>
{if $user->user_id ne $image->user_id}
<td style="width:50px"><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}"><img  src="http://{$static_host}/templates/basic/img/icon_email.gif" alt="Email" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	<a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the contributor</a>
</td>
{/if}
</tr>
</table>

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
 <dd><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if} &nbsp; (<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->grid_reference}" rel="nofollow">more nearby</a>) 
</dd>

{if $image->credit_realname}
	<dt>Photographer</dt>
	 <dd>{$image->realname|escape:'html'}</dd>

	<dt>Contributed by</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}">{$image->user_realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->user_realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
{else}
	<dt>Photographer</dt>
	 <dd><a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
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
 <dd>{$image_taken} &nbsp; (<a title="pictures near {$image->grid_reference} taken on {$image_taken}" href="/search.php?gridref={$image->grid_reference}&amp;orderby=submitted&amp;taken_start={$image->imagetaken}&amp;taken_end={$image->imagetaken}&amp;do=1" class="nowrap" rel="nofollow">more nearby</a>)</dd>
{/if}
<dt>Submitted</dt>
	<dd>{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>

<dt>Category</dt>

<dd>{if $image->imageclass}
	{$image->imageclass} &nbsp; (<a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:'url'}" rel="nofollow">more nearby</a>)
{else}
	<i>n/a</i>
{/if}</dd>

<dt>Subject Location</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}Germany, MGRS 32{elseif $image->grid_square->reference_index eq 4}Germany, MGRS 33{elseif $image->grid_square->reference_index eq 5}Germany, MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links">{$image->subject_gridref}</a> [{$image->subject_gridref_precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>

{if $image->photographer_gridref}
<dt>Photographer Location</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}Germany, MGRS 32{elseif $image->grid_square->reference_index eq 4}Germany, MGRS 33{elseif $image->grid_square->reference_index eq 5}Germany, MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->photographer_gridref}/links">{$image->photographer_gridref}</a></dd>
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

	<a title="Send an Electronic Card" href="/ecard.php?image={$image->gridimage_id}">Forward to a<br/>Friend &gt; &gt;</a><br/><br/>

	<a href="{$sitemap}">Text listing of Images in {$image->grid_reference}</a>


	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>
<div class="interestBox" style="text-align:center">View this location: 

{if $image->moderation_status eq "geograph" || $image->moderation_status eq "accepted"}

<small><a title="Open in Google Earth" href="http://{$http_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</small>, 
{external title="Open in Google Maps" href="http://maps.google.co.uk/maps?q=http://`$http_host`/photo/`$image->gridimage_id`.kml" text="Google Maps"}, 

{/if}

{getamap gridref=$image->subject_gridref text="OS Get-a-map&trade;"},

{if $rastermap->reference_index == 1}<a href="/mapper/?t={$map_token}&amp;gridref_from={$image->grid_reference}">OS Map Checksheet</a>, {/if}

<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$image->grid_reference}">Geograph Map</a>, 

{if $image_taken}
	{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;taken=%Y-%m-%d"}
{/if}

<span class="nowrap"><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links?{$imagetakenurl}&amp;title={$image->title|escape:'url'}&amp;id={$image->gridimage_id}"><b>More Links for this image</b></a></span>
</div>


<div style="text-align:center;margin-top:3px" class="interestBox" id="styleLinks"></div>
<script type="text/javascript">
/* <![CDATA[ */
{literal}
function addStyleLinks() {
{/literal}
	document.getElementById('styleLinks').innerHTML = 'Background for photo viewing: {if $maincontentclass eq "content_photowhite"}<b>white</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex">White</a>{/if}/{if $maincontentclass eq "content_photoblack"}<b>black</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex">Black</a>{/if}/{if $maincontentclass eq "content_photogray"}<b>grey</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex">Grey</a>{/if}';
{literal}
}
{/literal}
 AttachEvent(window,'load',addStyleLinks,false);
/* ]]> */
</script>


<div style="width:100%;position:absolute;top:0px;left:0px;height:0px">
	<div class="interestBox" style="float: right; position:relative; padding:2px;">
		<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}">NW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}">N</a></td>
		<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}">NE</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}">W</a></td>
		<td><b>Go</b></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}">E</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}">SW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}">S</a></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}">SE</a></td></tr>
		</table>
	</div>
	<div style="float:right">
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}" title="Add this image to your site marked list">Mark</a>]&nbsp;
	</div>
</div>


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
