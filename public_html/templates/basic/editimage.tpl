{include file="_std_begin.tpl"}
{dynamic}

{if $image}

 <h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->title}</h2>
 
<p>Edit the image information below - you may find it useful to refer
to the {getamap gridref=$image->grid_reference text="OS Get-a-Map for `$image->grid_reference`"}.</p>


<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow">{$image->getFull()}</div>
  <div class="caption">{$image->title|escape:'html'}</div>
  
  {if $image->comment}
  <div class="caption">{$image->comment|escape:'html'}</div>
  {/if}
</div>


<form method="post" action="/editimage.php">
<input type="hidden" name="id" value="{$image->gridimage_id}"/>



<p><label for="grid_reference">Grid Reference</label><br/>
{if $image->moderation_status eq "pending" or $is_admin}
  <input type="text" id="grid_reference" name="grid_reference" size="6" value="{$image->grid_reference|escape:'html'}"/>
   {if $image->moderation_status eq "pending"}
     (<i>This image hasn't been moderated yet, so you can change the grid square.</i>)
   {else}
     (<i>Although this image has been moderated, your login has sufficient privileges to change the grid square</i>)
   {/if}
  
  {if $error.grid_reference}<br/><span class="formerror">{$error.grid_reference}</span>{/if}
{else}
  {$image->grid_reference|escape:'html'} (<i>You cannot change this grid reference as the image has been moderated - 
  if you believe the grid reference
  is incorrect, please contact us</i>)
{/if}
</p>




<p><label for="title">Title</label><br/>
<input type="text" id="title" name="title" size="50" value="{$image->title|escape:'html'}"/>
<br/><span class="formerror">{$error.title}</span>
</p>


<p>
<label for="comment">Comment</label><br/>
<textarea id="comment" name="comment" rows="3" cols="50">{$image->comment|escape:'html'}</textarea>
<br/><span class="formerror">{$error.comment}</span>
</p>

{literal}
<script type="text/javascript">
<!--
function onChangeImageclass()
{
	var sel=document.getElementById('imageclass');
	var idx=sel.selectedIndex;
	
	var isOther=idx==sel.options.length-1
	
	var otherblock=document.getElementById('otherblock');
	otherblock.style.display=isOther?'inline':'none';
	
}
//-->
</script>
{/literal}

<p><label for="imageclass">Primary geographical category</label><br />	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()">
		<option value="">--please select feature--</option>
		{html_options options=$classes selected=$image->imageclass}
	</select>
	
	
	<span id="otherblock" {if $image->imageclass ne 'Other'}style="display:none;"{else}style="display:inline;"{/if}>
	<label for="imageclassother">Please specify </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32"/></p>
	</span>
	

	{if $error.imageclass}

	<br/><span class="formerror">{$error.imageclass}</span>
	{/if}
	
	{if $error.imageclassother}
	<br/><span class="formerror">{$error.imageclassother}</span>
	{/if}
</p>	
	
<p><label>Date picture taken</label><br/>
	{html_select_date prefix="imagetaken" time=`$image->imagetaken` start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	<br/><small>(please provide as much detail as possible, if you only know the year or month then that's fine)</small></p>


<input type="submit" name="save" value="Save Changes"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/view.php?id={$image->gridimage_id}';"/>







</form>



{else}
	<h2>Sorry, image not available</h2>

	<p>{$error}</p>

	<p>Please <a title="Contact Us" href="/contact.php">contact us</a> 
	if you have queries</p>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
