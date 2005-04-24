{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 
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
  <div class="caption">{$image->title|escape:'html'}</div>
  
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
		  
<br style="clear:both;"/>		  

{if $overview}
<div style="float:right; width{$overview_width+30}px;"> 
<h3>Overview Map</h3>
<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img 
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
{if $marker}
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="/templates/basic/img/crosshairs.gif" alt="+" width="16" height="16"></div>
{/if}
</div>
</div>
</div>
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
<tr><td>Category</td><td>{if $image->imageclass}{$image->imageclass}{else}<i>n/a</i>{/if}</td></tr>

<tr><td>Maps for {$image->grid_reference}</td><td>

<a href="/mapbrowse.php?t={$map_token}">Geograph Map</a>, 

{getamap gridref=$image->grid_reference text="OS Get-a-Map"}

{if $image->grid_square->reference_index eq 1}
	(also 

	{external href="http://www.streetmap.co.uk/streetmap.dll?Grid2Map?X=`$image->grid_square->nateastings`&amp;Y=`$image->grid_square->natnorthings`&amp;title=`$image->title`&amp;back=Return+to+Geograph&amp;url=http://$http_host/photo/`$image->gridimage_id`&amp;nolocal=X&amp;bimage=background%3dhttp://$http_host/templates/basic/img/background.gif" text="streetmap.co.uk"} &amp;
	{external href="http://www.multimap.com/map/browse.cgi?GridE=`$image->grid_square->nateastings`&amp;GridN=`$image->grid_square->natnorthings`&amp;scale=25000" text="multimap.com"} 
	)<br>

{/if}

</td></tr>

<tr><td>What's nearby?</td><td>


{if $image->grid_square->reference_index eq 1}
	<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->grid_reference}">Geograph Images</a>, 
	{external title="Geocaches from stats.guk2.com" href="http://stats.guk2.com/caches/search_parse.php?osgbe=`$image->grid_square->nateastings`&amp;osgbn=`$image->grid_square->natnorthings`" text="Geocaches"}, 
	{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$image->grid_reference`" text="Trigpoints"}, 
	{external title="Many more links from nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`" text="more..."}
{else}
	{external title="Many more links from nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$image->grid_square->nateastings`+`$image->grid_square->natnorthings`+OSI" text="See related information from nearby.org.uk"}

{/if}



</td></tr>



<tr><td>Copyright</td><td>



<!-- Creative Commons License -->
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img align="right" alt="Creative Commons License" src="http://creativecommons.org/images/public/somerights20.gif" /></a>
The copyright on this image is owned by <a title="View profile" href="/profile.php?u={$image->user_id}">{$image->realname|escape:'html'}</a> and is 
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


</td></tr>
</table>




{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a> 
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
