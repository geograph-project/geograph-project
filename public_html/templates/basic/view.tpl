{include file="_std_begin.tpl"}

{if $image}
<div style="float: right; position:relative;">
<table border="1" cellspacing="0">
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
 <h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} total images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 {if $place.distance}
 <h3>{if $place.distance > 3}{$place.distance} km from{else}near to{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && $place.adm1_name != $place.reference_name}, {$place.adm1_name}{/if}, {$place.reference_name}</i></small></h3>
 {/if}

{if $image->moderation_status eq 'rejected'}
<h3>Rejected</h3>
<p>This photograph has been rejected by the site moderators, and is only viewable by you.
Possible reasons for rejection include:
</p>
<ul>
<li>Doesn't offer much geographical context - closeups tend to fall into this category.
Shots don't have to be sweeping landscapes, but must provide a reasonable idea of
typical geography.</li>
<li>Family snap - while people can be in the photo, they must not <i>be</i> the photo</li>
<li>Inappropriate content - any image containing material inappropriate for minors</li>
</ul>
<p>We keep rejected submissions on file for a short period, so if you think your
image has been incorrectly rejected (and mistakes do happen!) please <a title="Contact us" href="/contact.php">contact us</a>
referring to <b>image {$image->gridimage_id}</b>
</p>

{/if}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto">{$image->getFull()}</div>
  
  {literal}
  <script language="JavaScript">
  
  function redrawMainImage() {
  	el = document.getElementById('mainphoto');
  	el.style.display = 'none';
  	el.style.display = '';
  }
  window.onload = redrawMainImage;
  
  </script>
  {/literal}
  
  <div class="caption"><b>{$image->title|escape:'html'}</b></div>

  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
  {/if}

 
  
  {if $ismoderator}
	  <form method="post">
	  <script type="text/javascript" src="/admin/moderation.js"></script>
	  <b>Moderation</b>
	  <input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  </form>
  {/if}

</div>




<div style="background:#bbbbbb;color:black;">

<table width="100%"><tr>

<td width="50"><a href="/discuss/index.php?gridref={$image->grid_reference}"><img align="left" src="/templates/basic/img/icon_discuss.gif"/></a></td>
<td valign="middle" style="font-size:0.7em;">
{if $discuss}
	There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a
	<a href="/discuss/index.php?gridref={$image->grid_reference}">discussion<br/>on {$image->grid_reference}</a> (preview on the left)

{else}
	<a href="/discuss/index.php?gridref={$image->grid_reference}#newtopic">Start a discussion on {$image->grid_reference}</a>
{/if}
</td>

<td width="50"><a href="/editimage.php?id={$image->gridimage_id}"><img align="left" src="/templates/basic/img/icon_alert.gif"/></a></td>
<td valign="middle" style="font-size:0.7em;">
  
  {if ($user->user_id eq $image->user_id) or ($ismoderator)}
  	<a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">Edit picture information</a>
  {else}
  	<a href="/editimage.php?id={$image->gridimage_id}">Report a problem with this picture</a>
  {/if}
</td>

<td width="50"><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}"><img align="left" src="/templates/basic/img/icon_email.gif"/></a></td>
<td valign="middle" style="font-size:0.7em;">
  
  <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the photographer</a>
</td>

</tr>
</table>

</div>




<div class="picinfo">

{if $overview}
  <div style="float:left; width:{$overview_width+30}px; position:relative">
  {include file="_overview.tpl"}
  <div style="text-align:center"><br/>
  <a title="Send an Electronic Card" href="/ecard.php?image={$image->gridimage_id}">Send eCard</a>
  
  <a title="Open in Google Earth" href="/kml.php?id={$image->gridimage_id}" class="xml-kml">KML</a></div>
  </div>
{/if}


<table class="infotable">

<!-- Creative Commons Licence -->
<tr><td><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a></td>
<td>&copy; Copyright <a title="View profile" href="/profile.php?u={$image->user_id}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</td>
</tr>
<!-- /Creative Commons Licence -->

<!--

<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<Work rdf:about="">
     <dc:title>{$image->title|escape:'html'}</dc:title>
     <dc:creator><Agent>
        <dc:title>{$image->realname|escape:'html'}</dc:title>
     </Agent></dc:creator>
     <dc:rights><Agent>
        <dc:title>{$image->realname|escape:'html'}</dc:title>
     </Agent></dc:rights>
     <dc:date>{$image->submitted}</dc:date>
     <dc:format>image/jpeg</dc:format>
     <dc:publisher><Agent>
        <dc:title>{$http_host}</dc:title>
     </Agent></dc:publisher>
{if $image->imageclass}
     <dc:subject>{$image->imageclass}</dc:subject>
{/if}
     <license rdf:resource="http://creativecommons.org/licenses/by-sa/2.0/" />
</Work>

<License rdf:about="http://creativecommons.org/licenses/by-sa/2.0/">
   <permits rdf:resource="http://web.resource.org/cc/Reproduction" />
   <permits rdf:resource="http://web.resource.org/cc/Distribution" />
   <requires rdf:resource="http://web.resource.org/cc/Notice" />
   <requires rdf:resource="http://web.resource.org/cc/Attribution" />
   <permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
   <requires rdf:resource="http://web.resource.org/cc/ShareAlike" />
</License>

</rdf:RDF>

-->

<tr><td></td>
<td>(find <a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap">nearby pictures by {$image->realname|escape:'html'}</a>)</td>
</tr>

<tr><td>Image status</td><td>
{if $image->ftf}
	First geograph for {getamap gridref=$image->grid_reference}!
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
	 for {getamap gridref=$image->grid_reference}
{/if}
</td></tr>


{if $image_taken}
<tr><td>Date Taken</td><td colspan="2">{$image_taken}
 (submitted {$image->submitted|date_format:"%A, %e %B, %Y"})</td></tr>
{else}
<tr><td>Submitted</td><td colspan="2">{$image->submitted|date_format:"%A, %e %B, %Y"}</td></tr>
{/if}

<tr><td>Category</td><td>

{if $image->imageclass}
	{$image->imageclass}

	(<a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:'url'}">find more nearby</a>)
{else}
	<i>n/a</i>
{/if}

</td></tr>

<tr><td>Maps</td><td>

<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$image->grid_reference}">Geograph Map</a>,

{getamap gridref=$image->subject_gridref text="OS Get-a-map&trade;"},

{if $image->grid_square->reference_index eq 1}
	{assign var="urltitle" value=$image->title|escape:'url'}
	{external href="http://www.streetmap.co.uk/newmap.srf?x=`$image->grid_square->nateastings`&amp;y=`$image->grid_square->natnorthings`&amp;z=3&amp;sv=`$image->grid_square->nateastings`,`$image->grid_square->natnorthings`&amp;st=OSGrid&amp;lu=N&amp;tl=[$urltitle]+from+geograph.org.uk&amp;ar=y&amp;bi=background=http://$http_host/templates/basic/img/background.gif&amp;mapp=newmap.srf&amp;searchp=newsearch.srf" text="streetmap.co.uk"},
	{external href="http://www.multimap.com/map/browse.cgi?GridE=`$image->grid_square->nateastings`&amp;GridN=`$image->grid_square->natnorthings`&amp;scale=25000&amp;title=[`$urltitle`]+on+geograph.org.uk" text="multimap.com"}
	&amp; 
	{external href="http://local.live.com/default.aspx?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;rtp=~pos.`$lat`_`$long`_`$urltitle`" text="local.live.com" title="detailed aerial photography from getmapping.com"}
{else}
	&amp;
	{external href="http://www.multimap.com/p/browse.cgi?scale=25000&amp;lon=`$long`&amp;lat=`$lat`&amp;GridE=`$long`&amp;GridN=`$lat`" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}
{/if}

</td></tr>

<tr><td>What's nearby?</td><td>
<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->grid_reference}">Geograph Images</a>, 
{if $image->grid_square->reference_index eq 1}
	{external title="Geocaches from geocaching.com, search by geocacheuk.com" href="http://stats.guk2.com/caches/search_parse.php?osgbe=`$image->grid_square->nateastings`&amp;osgbn=`$image->grid_square->natnorthings`" text="Geocaches"},
	{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$image->subject_gridref`" text="Trigpoints"},
	{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"} &amp;
	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`" text="more..."}
{else}
	{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="geocaches" title="Geocaches from geocaching.com"},
	{external href="http://www.trigtools.co.uk/irish.cgi?gr=`$image->subject_gridref`&c=25" text="trigpoints" title="Trigpoints from trigtools.co.uk"},
	{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"} &amp;
	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`+OSI" text="more from nearby.org.uk"}
{/if}

</td></tr>


<tr><td>Subject Location</td>
<td style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {getamap gridref=$image->subject_gridref text=$image->subject_gridref} [Accurate to ~{$image->subject_gridref_accuracy}m]<br/>
WGS84: {$latdm} {$longdm}
[{$lat|string_format:"%.5f"},{$long|string_format:"%.5f"}]</td></tr>

{if $image->photographer_gridref}
<tr><td>Photographer Location</td>

<td style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {getamap gridref=$image->photographer_gridref text=$image->photographer_gridref}</td></tr>
{/if}

{if $view_direction}
<tr><td>View Direction</td>

<td style="font-family:verdana, arial, sans serif; font-size:0.8em">
{$view_direction} (about {$image->view_direction} degrees)</td></tr>
{/if}

</table> 


<br style="clear:both"/>


<p align="center">
<span style="background-color:#e9e9e9; padding:10px;margin:10px">Background for photo viewing:
{if $maincontentclass eq "content_photowhite"}
	<b>white</b>
{else}
	<a href="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex">White</a>
{/if}/
{if $maincontentclass eq "content_photoblack"}
	<b>black</b>
{else}
	<a href="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex">Black</a>
{/if}/
{if $maincontentclass eq "content_photogray"}
	<b>grey</b>
{else}
	<a href="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex">Grey</a>
{/if}
</span>
</p>



</div>



{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
