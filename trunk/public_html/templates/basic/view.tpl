{include file="_std_begin.tpl"}

{if $image}

 <h2 style="margin-bottom:0px;"><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 {if $place.distance}
 <div style="color:silver">&nbsp;{if $place.distance > 3}{$place.distance} km from{else}near to{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && $place.adm1_name != $place.reference_name}, {$place.adm1_name}{/if}, {$place.reference_name}</i></small></div>{/if}

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
image has been incorrectly rejected (and mistakes do happen!) please <a title="Contact us" href="contact.php">contact us</a>
referring to <b>image {$image->gridimage_id}</b>
</p>

{/if}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow">{$image->getFull()}</div>
  <div class="caption"><b>{$image->title|escape:'html'}</b></div>

  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'}</div>
  {/if}

  {if $user->user_id eq $image->user_id}
  <div class="caption"><a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">Edit Photo Information</a></div>
  {/if}

  {if $ismoderator}
	  <form method="post" action="/usermsg.php">
	  <input type="hidden" name="to" value="{$image->user_id}"/>
	  <input type="hidden" name="init" value="Re: image for {$image->grid_reference} ({$image->title})&#13;&#10;http://{$http_host}/photo/{$image->gridimage_id}&#13;&#10;"/>
	  <script type="text/javascript" src="/admin/moderation.js"></script>
	  <b>Moderation</b>
	  <input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')"/>
	  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')"/>
	  <input class="reject" type="button" id="reject" value="Reject" onclick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  <input class="reject" type="button" name="edit" value="Edit" title="Edit Photo Information" onclick="document.location='/editimage.php?id={$image->gridimage_id}';"/>
	  <input class="reject" type="submit" name="query" value="?" title="Send email to user"/>
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  </form>
  {/if}

</div>

{dynamic}
<div style="text-align:center; font-size: 0.8em;">
{if $discuss}
	There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a
	<a href="/discuss/index.php?gridref={$image->grid_reference}">discussion on {$image->grid_reference}</a> (preview on the left)

{else}
	{if $user->registered}
		<a href="/discuss/index.php?gridref={$image->grid_reference}#newtopic">Start a discussion on {$image->grid_reference}</a>
	{else}
		<a href="/login.php">login</a> to start a discussion on {$image->grid_reference}
	{/if}
{/if}<br/><br/>
</div>
{/dynamic}

{if $overview}
	{include file="_overview.tpl"}
{/if}


<table class="formtable">
<tr><td>Submitted by</td><td><a title="View profile" href="/profile.php?u={$image->user_id}">{$image->realname|escape:'html'}</a></td></tr>

<tr><td>Image status</td><td>
{if $image->ftf}
	First geograph for this square!
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
{/if}
</td></tr>



<tr><td>Submitted</td><td>{$image->submitted|date_format:"%A, %e %B, %Y"}</td></tr>

{if $image_taken}
<tr><td>Taken</td><td>{$image_taken} </td></tr>
{/if}
<tr><td>Category</td><td>{$image->imageclass|default:"<i>n/a</i>"}</td></tr>

<tr><td>Maps for {$image->grid_reference}</td><td>

<a href="/mapbrowse.php?t={$map_token}">Geograph Map</a>,

{getamap gridref=$image->grid_reference text="OS Get-a-Map"},
<br/>
{if $image->grid_square->reference_index eq 1}
	{external href="http://www.streetmap.co.uk/streetmap.dll?Grid2Map?X=`$image->grid_square->nateastings`&amp;Y=`$image->grid_square->natnorthings`&amp;title=[`$image->title`]+from+geograph.co.uk&amp;back=Return+to+Geograph&amp;url=http://$http_host/photo/`$image->gridimage_id`&amp;nolocal=X&amp;bimage=background%3dhttp://$http_host/templates/basic/img/background.gif" text="streetmap.co.uk"}
	&amp; 
	{external href="http://www.multimap.com/map/browse.cgi?GridE=`$image->grid_square->nateastings`&amp;GridN=`$image->grid_square->natnorthings`&amp;scale=25000&amp;title=[`$image->title`]+on+geograph.co.uk" text="multimap.com"}
{else}
	&amp;
	{external href="http://www.multimap.com/p/browse.cgi?scale=25000&amp;lon=`$long`&amp;lat=`$lat`&amp;GridE=`$long`&amp;GridN=`$lat`" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}
{/if}

</td></tr>

<tr><td>What's nearby?</td><td>
<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->grid_reference}">Geograph Images</a>

<small>(<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}">by this contributor</a> or <a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:html|default:"-"}" href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:url|default:"-"}">of this category</a>)</small>,<br/>

{if $image->grid_square->reference_index eq 1}
	{external title="Geocaches from geocaching.com, search by geocacheuk.com" href="http://stats.guk2.com/caches/search_parse.php?osgbe=`$image->grid_square->nateastings`&amp;osgbn=`$image->grid_square->natnorthings`" text="Geocaches"},
	{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$image->grid_reference`" text="Trigpoints"},
	{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"} &amp;
 	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`" text="more..."}
{else}
	{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="geocaches" title="Geocaches from geocaching.com"},
 	{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}  &amp;
 	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`+OSI" text="more from nearby.org.uk"}
{/if}

</td></tr>
<tr><td>Subject Location</td>

<td style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$smallgr} <span style="font-size:0.8em"> [Accurate to ~{$accucacy}m]</span><br/>
WGS84: {$latdm} {$longdm}
<span style="font-size:0.8em">[{$lat|string_format:"%.5f"},{$long|string_format:"%.5f"}]</span> </td></tr>

{if $posgr}
<tr><td>Photographer Loc.</td>

<td style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$posgr}</td></tr>
{/if}

</table>

<br/>
<div style="text-align:center">
<!-- Creative Commons License -->
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" src="http://creativecommons.org/images/public/somerights20.gif" style="float:left; position:relative"/></a>
The copyright on this image is owned by <a title="View profile" href="/profile.php?u={$image->user_id}">{$image->realname|escape:'html'}</a> and is <br/>
licenced under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>.
<!-- /Creative Commons License -->


<!--

<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<Work rdf:about="">
     <dc:date>{$image->submitted}</dc:date>
     <dc:format>image/jpeg</dc:format>
     <dc:title>{$image->title|escape:'html'}</dc:title>
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

</div>





{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
