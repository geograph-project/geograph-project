{assign var="page_title" value="Update Image Details"}
{dynamic}
{include file="_std_begin.tpl"}

{if $image}


<div style="float:right; width:250px" class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a></div>
  <div class="caption"><b>{$image->title|escape:'html'}</b><br/> by {$image->realname}</div>
  
</div>

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title|escape:'html'}</h2>

{if $isadmin && $locked_by_moderator}
	<p style="position:relative;padding:10px;border:1px solid pink; color:white; background-color:red">
	<b>This image is currently open by {$locked_by_moderator}</b>, please come back later.
	</p>
{/if}

{if $error}
<h2><span class="formerror">Changes not submitted - check and correct errors below...</span></h2>
{/if}



   <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="id" value="{$image->gridimage_id}"/>

{include file="_submit_agreetext.tpl"}

{assign var="credit" value=$image->credit_realname}	
{assign var="credit_default" value=0}	
{include file="_submit_licence.tpl"}
<br/><br/>
<input type="submit" name="save" value="Submit Changes" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>

</form>

{else}
	<h2>Sorry, image not available</h2>

	<p>{$error}</p>

	<p>Please <a title="Contact Us" href="/contact.php">contact us</a> 
	if you have queries</p>
{/if}

{include file="_std_end.tpl"}
{/dynamic}
