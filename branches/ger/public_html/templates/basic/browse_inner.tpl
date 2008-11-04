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

		{if $by eq 'centi' || $by eq 'viewcenti' }
			<p><small>The 100 centisquares of {$gridref} are laid out on the grid below, of which {$allcount} have photos, hover over the square to see the 6figure grid reference.</small></p>
	<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}&amp;by={$by}">NW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}&amp;by={$by}">N</a></td>
		<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}&amp;by={$by}">NE</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}&amp;by={$by}">W</a></td>
		<td>	
			{if $rastermap->enabled && $rastermap->mapurl}
				<div style="position:relative; width:330px; height:330px">
					<div style="position:absolute; top:-150px; left:-120px; overflow:hidden; clip: rect(150px 450px 450px 150px); width:600px; height:600px;">
						<img id="background" src="{$rastermap->mapurl}" alt="Background-image" height="600" width="600" style="filter:alpha(opacity=80);-moz-opacity:.80;opacity:.80;"/>
					</div>
					<div style="position:absolute; width:330px; height:330px">
			<table cellspacing="0" cellpadding="4" border="1"  style="filter:alpha(opacity=80);-moz-opacity:.80;opacity:.80;">
			{else}
			<table cellspacing="0" cellpadding="4" border="1">
			{/if}
				{foreach from=$tendown item=yy}
					<tr>
						<th height="30">{$yy}</th>
						{foreach from=$tenup item=xx}
							{if $breakdown.$yy.$xx.link}
								<td align="right" bgcolor="#{$breakdown.$yy.$xx.count|colerize}"><a href="{$breakdown.$yy.$xx.link}&amp;inner" title="{$breakdown.$yy.$xx.name}">{$breakdown.$yy.$xx.count}</a></td>
							{else}
								<td>&nbsp;</td>
							{/if}
						{/foreach}
					</tr>
				{/foreach}
				<tr>
					<td width="20">&nbsp;</td>
					{foreach from=$tenup item=xx}
						<th width="20">{$xx}</th>
					{/foreach}
				</tr>
			</table>
			{if $rastermap->enabled && $rastermap->mapurl}
					</div>
				</div>
			{/if}
	</td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}&amp;by={$by}">E</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}&amp;by={$by}">SW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}&amp;by={$by}">S</a></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}&amp;by={$by}">SE</a></td></tr>
	</table>
			{if $breakdown.50.50.link}
				<ul>
				<li><a href="{$breakdown.50.50.link}" title="{$breakdown.50.50.name}">{$breakdown.50.50.name}</a> [{$breakdown.50.50.count}]</li>
				</ul>
			{/if}
		{else}
			<ul>
			{foreach from=$breakdown item=b}
				<li><a href="{$b.link}&amp;inner">{$b.name}</a> [{$b.count}]</li>
			{/foreach}
			</ul>	
		{/if}
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
					<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></div>
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


