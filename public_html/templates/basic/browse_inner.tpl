{dynamic}
{if $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}

{include file="_basic_begin.tpl"}


{if $errormsg}
<p>{$errormsg}</p>

	{if $square->percent_land < 50 && $square->percent_land != -1}
	<form action="/mapfixer.php" method="get" target="_blank">
		<p align="right"><input type="submit" name="save" value="Bring this square to the attention of a moderator"/>
		<input type="hidden" name="gridref" value="{$square->grid_reference|default:$gridref}"/>
		</p>
	</form>
	{/if}

{/if}
{if $showresult}
	{* We have a valid GridRef *}


	&middot; <a href="/gridref/{$gridref}" target="_blank">View Full Browse Page</a> &middot; 
{/if}


<br style="clear:both"/>
</div>

{if $showresult}
	{* We have a valid GridRef *}
	
	<div class="interestBox" style="position:relative; font-size:0.7em">We have 
	{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
	{if $totalimagecount && $totalimagecount ne $imagecount && !$filtered}(and {$totalimagecount-$imagecount} hidden){/if}
	for <b>{$gridref}</b>
	{if !$breakdown && !$breakdowns && $totalimagecount > 0}<span style="font-size:0.8em;"><br/>- click for larger version</span>{/if}</div>

	{if $breakdown}
		{* We want to display a breakdown list *}

		<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select images, by {$breakdown_title}:</p>

		<ul>
		{foreach from=$breakdown item=b}
			<li><a href="{$b.link}&amp;inner">{$b.name}</a> [{$b.count}]</li>
		{/foreach}
		</ul>	
	{else}
		{if $breakdowns}
			{* We want to choose a breakdown criteria to show *}

			<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select how you would like to view the images</p>
			
			<ul>
			{foreach from=$breakdowns item=b}
				<li><a href="/gridref/{$gridref}?by={$b.type}&amp;inner">{$b.name}</a> [{$b.count}]</li>
			{/foreach}

			<li style="margin-top:10px;">Or view all images in the <a target="_blank" href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" title="View images in {$gridref}">search interface</a> (<a target="_blank" href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;&displayclass=thumbs&amp;do=1">thumbnails only</a>)</li>

			</ul>
			
			<div class="photo33" style="float:left; margin-left:5px; width:150px; height:190px; background-color:white"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" target="_blank" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a>
			<div class="caption"><a target="_blank" title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
			<div class="statuscaption">classification:
			  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}{if $image->ftf} (first){/if}</div>
			</div>

			
			<br style="clear:both"/>
		{else}
			{* Display some actual thumbnails *}
			
			
			{if $filtered}
				<p>{$totalimagecount} Images, {$filtered_title}... (<a href="/gridref/{$gridref}?inner">Remove Filter</a>)</p>
			{/if}

			{foreach from=$images item=image}
				<div class="photo33" style="float:left; margin-left:5px; width:150px; height:170px; background-color:white"><a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(120,120,false,true)}</a>
					<div class="caption"><a targer="_blank" title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></div>
				</div>
			{/foreach}
			<br style="clear:left;"/>&nbsp;
		{/if}
	{/if}

   	{if $square->percent_land < 100 ||  $user->registered}
   		{* We on the coast so offer the option to request removal *}
   		
   		<form action="/mapfixer.php" method="get" target="_blank">
   		<p align="right"><input type="submit" value="Request check of land status" style="font-size:0.7em;"/>
   		<input type="hidden" name="gridref" value="{$gridref}"/>
   		</p>
   		</form>
   	{/if}
   	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{else}
	{* All at Sea Square! *}
{/if}
{/dynamic}
</body>
</html>


