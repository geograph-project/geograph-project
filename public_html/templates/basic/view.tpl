{include file="_std_begin.tpl"}

 <h2>{$image->gridref} : {$image->title}</h2>
     
<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  {$image->getFull()}
  <div class="caption">{$image->title|escape:'html'}</div>
</div>
		  
<br style="clear:both;"/>		  

<table>		
<tr><td>Submitted by</td><td>{$image->realname|escape:'html'}</td></tr>
<tr><td>Submission date</td><td>{$image->submitted}</td></tr>
<tr><td>Comments</td><td>{$image->comment}</td></tr>
<tr><td>Copyright</td><td>



<!-- Creative Commons License -->
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img align="right" alt="Creative Commons License" border="0" src="http://creativecommons.org/images/public/somerights20.gif" /></a>
The copyright on this image is owned by {$image->realname|escape:'html'} and is 
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

{include file="_std_end.tpl"}
