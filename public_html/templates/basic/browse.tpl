{dynamic}
{if $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}

{include file="_std_begin.tpl"}

    <h2>Browse</h2>
<div style="position:relative;margin-left:10px; width:850px;">
<div style="position:relative;float:left;width:530px">

{if $showresult}
	<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px">
	<table border="0" cellspacing="0" cellpadding="2">
	<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}">NW</a></td>
	<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}">N</a></td>
	<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}">NE</a></td></tr>
	<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}">W</a></td>
	<td><b>Go</b></td>
	<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}">E</a></td></tr>
	<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}">SW</a></td>
	<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}">S</a></td>
	<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}">SE</a></td></tr>
	</table>
	</div>
{else}
	<p>You can view a particular grid square below - if the square hasn't been filled yet,
	we'll tell you how far away the nearest one is (Use {getamap gridref='' text='Get-a-map&trade;'} to help locate your grid square)</p>
{/if}



<form action="/browse.php" method="get">
<div>

	<label for="gridref">Enter grid reference (e.g. SY9582)</label>
	<input id="gridref" type="text" name="gridref" value="{$gridrefraw|escape:'html'}" size="8"/>
	<input type="submit" name="setref" value="Show &gt;"/>

	
	<br/>
	<i>or</i><br/>

	<label for="gridsquare">Choose grid reference</label>
	<select id="gridsquare" name="gridsquare">
		{html_options options=$prefixes selected=$gridsquare}
	</select>
	<label for="eastings">E</label>
	<select id="eastings" name="eastings">
		{html_options options=$kmlist selected=$eastings}
	</select>
	<label for="northings">N</label>
	<select id="northings" name="northings">
		{html_options options=$kmlist selected=$northings}
	</select>

	<input type="submit" name="setpos" value="Show &gt;"/>
</div>
</form>

{if $errormsg}
	<p>{$errormsg}</p>

	{if $square->percent_land < 50 && $square->percent_land != -1}
	<form action="/mapfixer.php" method="get">
		<p align="right"><input type="submit" name="save" value="Bring this square to the attention of a moderator"/>
		<input type="hidden" name="gridref" value="{$gridref}"/>
		</p>
	</form>
	{/if}

{/if}
{if $showresult}
	{* We have a valid GridRef *}

	{if $overview}
		<br style="clear:both;"/>
		<div style="float:right; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
		</div>
	{/if}

	{if $totalimagecount}
		{* There are some thumbnails to display *}
		
		<ul>
	{else}
		{* There are no images in this square (yet) *}
		
		<p>We have no images for <b>{$gridref}</b> yet,
		
		{if $nearest_distance}
			</p><ul><li>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away<br/><br/></li>
		{else}
			and have no pictures for any grid square within 100km either!</p>
			<ul>
		{/if}
	{/if}
		<li><a href="/submit.php?gridreference={$gridrefraw}"><b>submit your own picture for {$gridref}</b></a>.</li>
		{if $enable_forums}
			<li>
			{if $discuss}
				There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
				<a href="/discuss/index.php?gridref={$gridref}"><b>discussion</b> about {$gridref}</a> (preview on the left),
			{else}
				{if $user->registered} 
					<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a <b>discussion</b> about {$gridref}</a>,
				{else}
					<a href="/login.php">login</a> to start a <b>discussion</b> about {$gridref}</a>,
				{/if}
			{/if}</li>
		{/if}
		{if !$breakdown && !$breakdowns && !$filtered && $totalimagecount > 1}
			<li><a href="/gridref/{$gridref}?by=1{if $extra}{$extra}{/if}">View <b>breakdowns</b> for this square</a></li>
			
		{/if}

		<li><a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}">Geograph <b>map</b> for {if strlen($gridrefraw) < 5}{$gridrefraw}{else}{$gridref}{/if}</a>,</li>
		<li><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/> <b><a href="/location.php?gridref={$gridrefraw}">More Links for {$gridrefraw}</a></b></li>
		
		</ul>
	
{/if}

</div>
{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative;font-size:0.8em">
	{$rastermap->getImageTag($gridrefraw)}
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	{$rastermap->getScriptTag()}	
	</div>
{/if}

<br style="clear:both"/>
</div>

{if $showresult}
	{* We have a valid GridRef *}
	
	<div class="interestBox" style="position:relative; margin-left:10px">We have 
	{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
	{if $totalimagecount && $totalimagecount ne $imagecount && !$filtered}(and {$totalimagecount-$imagecount} hidden){/if}
	for <b>{$gridref}</b>
	{if !$breakdown && !$breakdowns && $totalimagecount > 0}<span style="font-size:0.8em;">- click for larger version</span>{/if}</div>

	{if $user->registered}
		{if !$extra && $totalimagecount > 1}
			<div style="position:relative;text-align:right; font-size:0.7em"><a href="/gridref/{$gridref}?{if $breakdown || $breakdowns || $filtered}by=1&amp;{/if}nl=1">Include <b>pending and rejected</b> images</a>&nbsp;</div>
		{elseif $extra}
			<div style="position:relative;text-align:right; font-size:0.7em"><a href="/gridref/{$gridref}?{if $breakdown || $breakdowns || $filtered}by=1&amp;{/if}nl=0">Exclude <b>pending and rejected</b> images</a>&nbsp;</div>
		{/if}
	{/if}

	{if $breakdown}
		{* We want to display a breakdown list *}
		<blockquote>
		<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select images, by {$breakdown_title}:</p>

		{if $by eq 'centi'}
			<p><small>The 100 centisquares of {$gridref} are laid out on the grid below, of which {$allcount} have photos, hover over the square to see the 6figure grid reference.</small></p>
	<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}&amp;by=centi">NW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}&amp;by=centi">N</a></td>
		<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}&amp;by=centi">NE</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}&amp;by=centi">W</a></td>
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
								<td align="right" bgcolor="#{$breakdown.$yy.$xx.count|colerize}"><a href="{$breakdown.$yy.$xx.link}" title="{$breakdown.$yy.$xx.name}">{$breakdown.$yy.$xx.count}</a></td>
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
		<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}&amp;by=centi">E</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}&amp;by=centi">SW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}&amp;by=centi">S</a></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}&amp;by=centi">SE</a></td></tr>
	</table>
			{if $breakdown.50.50.link}
				<ul>
				<li><a href="{$breakdown.50.50.link}" title="{$breakdown.50.50.name}">{$breakdown.50.50.name}</a> [{$breakdown.50.50.count}]</li>
				</ul>
			{/if}
		{else}
			<ul>
			{foreach from=$breakdown item=b}
				<li><a href="{$b.link}">{$b.name}</a> [{$b.count}]</li>
			{/foreach}
			</ul>	
		{/if}
		
		<p>{if $imagecount < 15}<a href="/gridref/{$gridref}?by=1{if $extra}?{$extra}{/if}">&lt;&lt; Choose a different filter method</a></p>{/if}
		
		</blockquote>
	{else}
		{if $breakdowns}
			{* We want to choose a breakdown criteria to show *}

			<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select how you would like to view the images</p>

			{if $image}
			<div style="float:right;" class="photo33"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true)}</a>
			<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
			<div class="statuscaption">classification:
			  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}{if $image->ftf} (first){/if}</div>
			</div>
			{/if}
			
			<ul>
			{foreach from=$breakdowns item=b}
				<li><a href="/gridref/{$gridref}?by={$b.type}{$extra}">{$b.name}</a> [{$b.count}]</li>
			{/foreach}

			<li style="margin-top:10px;">Or view all images in the <a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" title="View images in {$gridref}">search interface</a> (<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;&displayclass=thumbs&amp;do=1">thumbnails only</a>)</li>

			</ul>
			<br style="clear:both"/>
		{else}
			{* Display some actual thumbnails *}
			
			
			{if $filtered}
				<p>{$totalimagecount} Images, {$filtered_title|escape:'html'}... (<a href="/gridref/{$gridref}{if $extra}?{$extra}{/if}">Remove Filter</a>)</p>
			{/if}

			{foreach from=$images item=image}
				<div style="float:left;" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
				<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
				<div class="statuscaption">classification:
				  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}{if $image->ftf} (first){/if}</div>
				</div>
			{/foreach}
			<br style="clear:left;"/>&nbsp;
		{/if}
	{/if}

   	{if $square->percent_land < 100 ||  $user->registered}
   		{* We on the coast so offer the option to request removal *}
   		
   		<form action="/mapfixer.php" method="get">
   		<p align="right"><input type="submit" value="Request check of land status for this square" style="font-size:0.7em;"/>
   		<input type="hidden" name="gridref" value="{$gridref}"/>
   		</p>
   		</form>
   	{/if}
   	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{else}
	{* All at Sea Square! *}
	
	<ul>
	{if $nearest_distance}
		<li>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away<br/><br/></li>
	{/if}
		
	{if $map_token}
		<li>You may still be able to view the <a href="/mapbrowse.php?t={$map_token}" title="Geograph map for {$gridref}">Map</a> for this square.</li>
	{/if}
	</ul>
{/if}
{include file="_std_end.tpl"}
{/dynamic}
