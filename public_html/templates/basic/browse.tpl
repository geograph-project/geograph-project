{dynamic}
{if $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

    <h2>Browse</h2>

{if $showresult}
	<div style="float: right; position:relative;">
	<table border="1" cellspacing="0">
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

	{if $totalimagecount}
		{* There are some thumbnails to display *}
		
		<ul>
		<li>{$gridref} : 
		{if !$breakdown && !$breakdowns && !$filtered && $totalimagecount > 1}
			[<a href="/gridref/{$gridref}?by=1">breakdown</a>]
		{/if}
		[<a href="/submit.php?gridreference={$gridrefraw}" title="Submit image for $gridref">submit</a>]
		[<a href="/search.php?q={$gridref}" title="Search for other nearby images">search</a>]
		[<a href="/discuss/index.php?gridref={$gridref}" title="discussion about {$gridref}">discuss</a>] 
		{if $totalimagecount > 5}
			[<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1" title="View images in a Slide Show">slide Show</a>]
		{/if}
		[<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}" title="Geograph map for {$gridref}">map</a>]
		[<a href="/gpx.php?gridref={$gridref}" title="Download GPX coverage around {$gridref}">gpx</a>]		
		[<a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">check sheet</a>]
		{if strlen($gridrefraw) < 5}
			[<a title="First Geographs within hectad {$gridrefraw}" href="/search.php?first={$gridrefraw}">hectad</a>]
		{/if}
		<br/><br/></li>



		<li><b>We have 
			{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
			{if $totalimagecount && $totalimagecount ne $imagecount && !$filtered}(and {$totalimagecount-$imagecount} hidden){/if}
			for {getamap gridref=$gridref text=$gridref title="Get-a-map&trade; for $gridref"}</b>
			{if !$breakdown && !$breakdowns}<span style="font-size:0.8em;">- click for larger version</span>{/if}
		</li>
		</ul>

		{if $breakdown}
			{* We want to display a breakdown list *}
			
			<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select images, by {$breakdown_title}:</p>

			<ul>
			{foreach from=$breakdown item=b}
				<li><a href="{$b.link}">{$b.name}</a> [{$b.count}]</li>
			{/foreach}
			</ul>	
		{else}
			{if $breakdowns}
				{* We want to choose a breakdown criteria to show *}
				
				<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select how you would like to view the images</p>

				<div style="float:right;" class="photo33"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true)}</a>
				<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
				<div class="statuscaption">status:
				  {if $image->ftf}first{/if}
				  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
				</div>

				<ul>
				{foreach from=$breakdowns item=b}
					<li><a href="/gridref/{$gridref}?by={$b.type}">{$b.name}</a> [{$b.count}]</li>
				{/foreach}

				<li style="margin-top:10px;">Or view all images in the <a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1%amp;do=1" title="View images in {$gridref}">search interface</a> (<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;&displayclass=thumbs&amp;do=1">thumbnails only</a>)</li>

				</ul>
				<br style="clear:both"/>
			{else}
				{* Display some actual thumbnails *}
				
				{if $filtered}
					<p>{$totalimagecount} Images, {$filtered_title}... (<a href="/gridref/{$gridref}">Remove Filter</a>)</p>
				{/if}

				{foreach from=$images item=image}
					<div style="float:left;" class="photo33"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a>
					<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
					<div class="statuscaption">status:
					  {if $image->ftf}first{/if}
					  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
					</div>
				{/foreach}
				<br style="clear:left;"/>&nbsp;
			{/if}
		{/if}
	{else}
		{* There are no images in this square (yet) *}
		
		<p>We have no images for {getamap gridref=$gridref text=$gridref title="Get-a-map&trade; for $gridref"} yet,
		
		{if $nearest_distance}
			</p><ul><li>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away<br/><br/></li>
		{else}
			and have no pictures for any grid square within 100km either!</p>
			<ul>
		{/if}
		<li>You can also <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}"><b>search</b> for nearby images</a>,</li>
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
		<li><a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}">Geograph <b>map</b> for {if strlen($gridrefraw) < 5}{$gridrefraw}{else}{$gridref}{/if}</a>,</li>
		<li><a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">View a <b>printable check sheet</b> for {if strlen($gridrefraw) < 5}{$gridrefraw}{else}{$gridref}{/if}</a>,</li>
		<li><a title="Download GPX" href="/gpx.php?gridref={$gridref}">Download a <b>GPX coverage</b> file around {$gridref}</a>,</li>
		{if strlen($gridrefraw) < 5}
			<li><a title="First Geographs within {$gridrefraw}" href="/search.php?first={$gridrefraw}">Find <b>first geographs for hectad</b> {$gridrefraw}</a>,</li>
		{/if}
		<li>Or <a href="/submit.php?gridreference={$gridrefraw}"><b>submit</b> your own picture for {$gridref}</a>.<br/><br/></li>
		
		<li><b>Maps</b>:
		
		{getamap gridref=$gridrefraw text="Get-a-Map&trade;"},
		
		{if $square->reference_index eq 1}
			{assign var="urltitle" value=$image->title|escape:'url'}
			{external href="http://www.streetmap.co.uk/newmap.srf?x=`$square->nateastings`&amp;y=`$square->natnorthings`&amp;z=3&amp;sv=`$square->nateastings`,`$square->natnorthings`&amp;st=OSGrid&amp;lu=N&amp;tl=[$gridref]+from+geograph.org.uk&amp;ar=y&amp;bi=background=http://$http_host/templates/basic/img/background.gif&amp;mapp=newmap.srf&amp;searchp=newsearch.srf" text="streetmap.co.uk"}
			&amp; 
			{external href="http://www.multimap.com/map/browse.cgi?GridE=`$square->nateastings`&amp;GridN=`$square->natnorthings`&amp;scale=25000&amp;title=[`$gridref`]+from+geograph.org.uk" text="multimap.com"}
		{else}
			&amp;
			{external href="http://www.multimap.com/p/browse.cgi?scale=25000&amp;lon=`$long`&amp;lat=`$lat`&amp;GridE=`$long`&amp;GridN=`$lat`" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}
		{/if}</li>
		
		<li><b>What's nearby?</b> 
		{if $square->reference_index eq 1}
			{external title="Geocaches from geocaching.com, search by geocacheuk.com" href="http://stats.guk2.com/caches/search_parse.php?osgbe=`$square->nateastings`&amp;osgbn=`$square->natnorthings`" text="Geocaches"},
			{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$gridrefraw`" text="Trigpoints"},
			{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"} &amp;
		 	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`" text="more..."}
		{else}
			{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="geocaches" title="Geocaches from geocaching.com"},
			{external href="http://www.trigtools.co.uk/irish.cgi?gr=`$gridrefraw`&c=25" text="trigpoints" title="Trigpoints from trigtools.co.uk"},
		 	{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}  &amp;
		 	{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`+OSI" text="more from nearby.org.uk"}
		{/if}</li>
		</ul>
	{/if}
   
   	{if $square->percent_land < 80}
   		{* We on the coast so offer the option to request removal *}
   		
   		<form action="/mapfixer.php" method="get">
   		<p align="right"><input type="submit" name="save" value="Request marking of this square as All at Sea"/>
   		<input type="hidden" name="gridref" value="{$gridref}"/>
   		</p>
   		</form>
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
