{assign var="page_title" value="Moderation"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="/admin/moderation.js"></script>


<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Moderation</h2>

{dynamic}

{if $unmoderatedcount}

	<p>The following images have been submitted recently. Click an image to 
	view fullsize</p>
	
	{foreach from=$unmoderated item=image}

	  <div style="float:left;" class="photo33"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
	  <div class="caption">
	  
	  <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by 
	  
	  <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a> entitled 
	  
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  
	  {if $image->comments}
	    <i>({$image->comments|escape:'html'})</i>
	  {/if}
	  
	  <br />
	  <input class="accept" type="button" id="geograph" value="Geograph!" onclick="moderateImage({$image->gridimage_id}, 'geograph')" {if $image->user_status} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="accept" type="button" id="accept" value="Accept" onclick="moderateImage({$image->gridimage_id}, 'accepted')" {if $image->user_status == 'rejected'} style="background-color:white;color:lightgrey"{/if}/>
	  <input class="reject" type="button" id="reject" value="Reject" onClick="moderateImage({$image->gridimage_id}, 'rejected')"/>
	  {if $image->user_status && $image->moderation_status != 'pending'}
	  	<br/>Current Status: {$image->moderation_status}
	  {/if}
	  </div>
	  <div class="caption" id="modinfo{$image->gridimage_id}">&nbsp;</div>
	  
	  </div>


	{/foreach}

	<br style="clear:left;"/>&nbsp;
		

{else}

	<p>There are no images awaiting moderation!</p>

{/if}
	
    
{/dynamic}    
{include file="_std_end.tpl"}
