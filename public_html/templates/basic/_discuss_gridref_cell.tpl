{dynamic}



{*
{if count($images) > 0}
<div {if $maincontentclass}class="{$maincontentclass}"{/if} style="{if count($images) > 3}height:520px; overflow:auto; {/if}background-color: #E9EFF4; max-width: 90%; position:relative;margin:0" onscroll="return showThumbnails(this);" id="scrollDiv">
<div style="position:relative;height:250px;">

{foreach from=$images item=image name=i}
  <div class="photo33" style="float:left;width:200px;height:220px;padding:3px">
  <a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true,'class="ithumb" data-src')}</a>
  
  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
  {if $is_admin}
    <div class="statuscaption">classification: {$image->moderation_status} {if $image->ftf eq 1}(first){/if}</div>
	{/if}

	<div style="font-size:0.7em"><br/>Insert: <a href="#" onclick="return paste_strinL('[[[{$image->gridimage_id}]]]',0)">Thumbnail</a> or <a href="#" onclick="return paste_strinL('[[{$image->gridimage_id}]]',0)">Text Link</a></div>
  </div>

{if $smarty.foreach.i.iteration%3 == 0 && $smarty.foreach.i.iteration < count($images)}
  <br style="clear:both"/>
  </div>
  <div style="position:relative;height:250px;">
{/if}
{/foreach}

<br style="clear:both"/>

{if count($images) == 500}
  Note: Only the first 500 images in the square are shown above.
{/if}

</div>
</div>
{/if}
<br style="clear:both"/>

<hr/>

*}


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
<div class="forum-gsd-count">{if count($images) > 5}Note: Only the first 500 images are shown above.{/if}</div>
<div class="forum-gsd-search">{if count($images) > 0}<a href="">View all images in {$gridref}</a>{/if}</div>



</div>


{/dynamic}

{literal}
<script type="text/javascript">
function showThumbnails(that) {
	t = that.scrollTop;
	i = document.images;
	c = 0; r = 0; p = 0; show = (Math.abs(t-p) < 520);
	for(q=0;q<i.length;q++) {
		if (i[q].hasAttribute('data-src') && i[q].className == 'ithumb') {
			if (show && (i[q].src == ''))
				i[q].src = i[q].getAttribute('data-src');
			c=c+1;
			if (c%3==0) {
				r=r+1;
				p = r * 260; show = (Math.abs(t-p) < 520);
			}
		}
	}
	return true;
}
function pageLoad() {
	showThumbnails(document.getElementById('scrollDiv'));
}
AttachEvent(window,'load',pageLoad,false);
</script>
{/literal}

