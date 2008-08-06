<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:georss="http://www.georss.org/georss/">
<Work rdf:about="http://{$http_host}/photo/{$image->gridimage_id}">
     <dc:title>{$image->grid_reference} : {$image->title|escape:'html'}</dc:title>
     <dc:identifier>http://{$http_host}/photo/{$image->gridimage_id}</dc:identifier>
{if $image->credit_realname}
     <dc:creator><Agent>
        <dc:title>{$image->realname|escape:'html'}</dc:title>
     </Agent></dc:creator>
     <dc:rights><Agent>
        <dc:title>{$image->user_realname|escape:'html'}</dc:title>
     </Agent></dc:rights>
{else}
     <dc:creator><Agent>
        <dc:title>{$image->realname|escape:'html'}</dc:title>
     </Agent></dc:creator>
     <dc:rights><Agent>
        <dc:title>{$image->realname|escape:'html'}</dc:title>
     </Agent></dc:rights>
{/if}     
     <dc:dateSubmitted>{$image->submitted}</dc:dateSubmitted>
     <dc:format>image/jpeg</dc:format>
     <dc:type>http://purl.org/dc/dcmitype/StillImage</dc:type>
     <dc:publisher><Agent>
        <dc:title>{$http_host}</dc:title>
     </Agent></dc:publisher>
{if $image->imageclass}
     <dc:subject>{$image->imageclass}</dc:subject>
{/if}
{if !strpos($image->imagetaken,'-00')}
     <dc:coverage>{$image->imagetaken}</dc:coverage>
{/if}
     <georss:point>{$lat|string_format:"%.6f"} {$long|string_format:"%.6f"}</georss:point>
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