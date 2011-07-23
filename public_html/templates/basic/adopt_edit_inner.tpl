{include file="_basic_begin.tpl"}
{literal}
<script type="text/javascript">
	function useimage(id) {
		window.parent.window.useimage(id);
		return false;
	}
	function formsubmit(form) {
		useimage(form.elements['iid'].value);
		return false;
	}
</script>{/literal}
{dynamic}


<div class="interestBox"><form style="display:inline" onsubmit="return formsubmit(this)">
Select an image from the {$square->imagecount} Images or enter Image ID: <input type="text" name="iid" value="" size="6"/>
<input type="submit" name="submit" value="Set"/>
</form><small><br/>Click thumbnail to select image, or click title to open full photo page<br/></small>
{if $square->imagecount > 100}
	Only 100 shown, {newwin href="/search.php?gridref=`$square->grid_reference`&amp;distance=1&amp;displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" text="open more in search"}
	or {newwin href="/gridref/`$square->grid_reference`" text="browse page for `$square->grid_reference`"}
{else}
	{newwin href="/gridref/`$square->grid_reference`" text="browse page for `$square->grid_reference`"}
{/if}

</div>

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative;font-size:0.8em; float:left; z-index:10;padding:0">
	{$rastermap->getImageTag($gridrefraw)}
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	{$rastermap->getScriptTag()}
	</div>
{/if}

{foreach from=$images item=image}
	<div class="photo33" style="float:left; margin-left:5px; width:150px; border:0; padding:0; padding-left:5px; background-color:white"><a title="{$image->title|escape:'html'} - click to use this image" href="#" onclick="return useimage({$image->gridimage_id})" target="_blank">{$image->getThumbnail(120,120,false,true)}</a>
		<div class="caption"><a title="{$image->title|escape:'html'} - view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'|truncate:'60'}</a> by <a title="view user profile" href="{$image->profile_link}" target="_blank">{$image->realname}</a></div>
	</div>
{/foreach}

<br style="clear:left;"/>&nbsp;
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{/dynamic}
</body>
</html>


