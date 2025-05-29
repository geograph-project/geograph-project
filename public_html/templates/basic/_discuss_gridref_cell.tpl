{dynamic}


<div class="forum-gsd-grid">

<div class="forum-gsd-gr"><h3>{if count($images) > 0}Photos in{else}Gridsquare{/if} <a href="/gridref/{$gridref}">{$gridref}</a></h3></div>
<div class="forum-gsd-location">{if $place.distance}{place place=$place}{/if}</div>
<div class="forum-gsd-thumbnails">
{if count($images) > 0}
        <div id="photo_block" class="forum-gsd-thumbs-flex-container" onscroll="return showThumbnails(this);" id="scrollDiv">
                {foreach from=$images item=image name=i}
                <div class="forum-gsd-thumbs-flex-photo">
                        <div class="shadow" style="height:126px; width:100%;">
                                <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true,'loading="lazy" src')}</a>
                        </div>
                        <div class="forum-gsd-thumbs-attribution">
                        <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
                        <span>by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
                        <div><a href="#" onclick="return paste_strinL('[[[{$image->gridimage_id}]]]',0)"><img src="img/compose_image_small.jpg" class="forum-icons" title="Insert thumbnail into reply" alt="Thumbnail"> <a href="#" onclick="return paste_strinL('[[{$image->gridimage_id}]]',0)"><img src="img/compose_twobracket.jpg" class="forum-icons" title="Insert text link into reply" alt="Text link"></a> <a href="#" onclick="return paste_strinL('[image id={$image->gridimage_id}]',0)"><img src="img/compose_image_large.jpg" class="forum-icons" title="Insert large thumbnail into reply" alt="Large thumbnail"></a></div>
                        </div>
                </div>

                {/foreach}                
        </div>
{else}
<div class="forum-gsd-thumbnails-none">There are no images for this gridsquare</div>
{/if}
</div>
<div class="forum-gsd-count">{if count($images) > 0}Click the icons below the images to add to the compose window.{/if}{if count($images) > 500}<br/><b>Note: Only the first 500 images are shown above.</b>{/if}</div>
<div class="forum-gsd-search">{if count($images) > 0}<a href="/gridref/{$gridref}">View browse page for {$gridref}</a>{/if}</div>



</div>


{/dynamic}
