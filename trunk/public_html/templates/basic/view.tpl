{include file="_std_begin.tpl"}

{if $image}

 <h2><a title="Grid Reference {$image->gridref}" href="/browse.php?gridref={$image->gridref}">{$image->gridref}</a> : {$image->title}</h2>
 
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
  {$image->getFull()}
  <div class="caption">{$image->title|escape:'html'}</div>
</div>
		  
<br style="clear:both;"/>		  

<table>		
<tr><td>Submitted by</td><td><a title="View profile" href="/profile.php?u={$image->user_id}">{$image->realname|escape:'html'}</a></td></tr>
<tr><td>Submission date</td><td>{$image->submitted}</td></tr>
<tr><td>Comments</td><td>{$image->comment}</td></tr>
<tr><td>Copyright</td><td>



<!-- Creative Commons License -->
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img align="right" alt="Creative Commons License" border="0" src="http://creativecommons.org/images/public/somerights20.gif" /></a>
The copyright on this image is owned by <a title="View profile" href="/profile.php?id={$image->user_id}">{$image->realname|escape:'html'}</a> and is 
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
