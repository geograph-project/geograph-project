{assign var="page_title" value="Moderation"}
{include file="_std_begin.tpl"}
<script src="/admin/moderation.js"></script>


<h2>Moderation</h2>


{if $unmoderatedcount}

	<p>The following images have been submitted recently. Click an image to 
	view fullsize</p>
	
	{foreach from=$unmoderated item=image}

	  <div style="float:left;" class="photo33"><a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
	  <div class="caption">
	  
	  <a title="view page for {$image->gridref}" href="/browse.php?gridref={$image->gridref}">{$image->gridref}</a> by 
	  
	  <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a> entitled 
	  
	  <a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  
	  {if $image->comments}
	    <i>({$image->comments|escape:'html'})</i>
	  {/if}
	  
	  <br />
	  <input class="accept" type="button" id="accept" value="Accept" onClick="moderateImage({$image->gridimage_id}, true)">
	  <input class="reject" type="button" id="reject" value="Reject" onClick="moderateImage({$image->gridimage_id}, false)">
	  
	  </div>
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  
	  </div>


	{/foreach}

	<br style="clear:left;"/>&nbsp;
		

{else}

	<p>There are no images awaiting moderation!</p>

{/if}
	
    
    
{include file="_std_end.tpl"}
