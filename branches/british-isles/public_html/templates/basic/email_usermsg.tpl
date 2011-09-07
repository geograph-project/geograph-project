{dynamic}
--This message was sent through the {$http_host} web site--

{$msg}
{if $images}

{foreach from=$images item=image}
Re: image for {$image.grid_reference} ({$image.title})
   http://{$http_host}/photo/{$image.gridimage_id}

{/foreach}
{/if}

------------------------------------------------------------
Forward abuse complaints to: support@geograph.org.uk
{/dynamic}
 	

    
