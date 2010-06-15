{assign var="page_title" value="Featured Stuff"}
{include file="_std_begin.tpl"}


<h2>Featured Stuff on Geograph</h2>

<div style="width:360px;float:left;position:relative">
	<a href="/photo/{$pictureoftheday.gridimage_id}" 
	title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(360,263)}</a>
</div>

<div class="interestBox" style="width:360px;float:left">
	<h3 style="margin-bottom:2px;margin-top:2px;">Photograph of the day <small>[<a href="/results/2087426">more...</a>]</small></h3>
	
	
	<a href="/photo/{$pictureoftheday.gridimage_id}"><b>{$pictureoftheday.image->title|escape:'html'}</b></a>

	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
	&nbsp;&nbsp;
	by <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname|escape:'html'}</a> for <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></div>
	
	<div style="color:gray">Image taken: {$pictureoftheday.image->imagetaken|date_format:"%e %b, %Y"}</div>

	</div>
</div>

<br style="clear:both"/><br/>

{foreach from=$latest item=item key=feature_id}

	<div class="interestBox" style="background-color:{cycle values="#eeeeee,#d0d0d0" name="color"}">
		
		{if $item.images}
			<div style="float:{cycle values="left,right" name="align"};position:relative; width:260px">
				{foreach from=$item.images item=image}
				  <div style="float:left;position:relative; width:130px; height:130px">
				  <div align="center">
				  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
				  </div>
				{/foreach}
			</div>
		{/if}
		
		<h3 style="margin-top:1px">{$item.feature|escape:'html'}</h3>
		
		&middot; <b><a href="{$item.url|escape:'html'}">{$item.title|escape:'html'}</a></b>
		
	
		{if $enable_forums && $item.thread_id}
			<div  style="font-size:small;margin-top:10px"><a href="/discuss/?&action=vthread&topic={$item.thread_id}">Discuss this item</a></div>
		{/if}
		
		<!--div style="font-size:small;text-align:right">
			<a href="{$script_name}?history={$feature_id}">View Past Items</a>
		</div-->
		<br style="clear:both"/>
	</div>

{foreachelse}
	<i>There is no content to display at this time.</i>
{/foreach}



{include file="_std_end.tpl"}
