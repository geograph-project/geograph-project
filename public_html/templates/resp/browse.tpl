{dynamic}
{if $imagecount}
	{assign var="page_title" value="$gridref :: Browse $imagecount Images"}
{elseif $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}

{include file="_std_begin.tpl"}


<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
{/literal}
</style>


<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px">
	<table border="0" cellspacing="0" cellpadding="2">
	<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}{$byextra}">NW</a></td>
	<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}{$byextra}">N</a></td>
	<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}{$byextra}">NE</a></td></tr>
	<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}{$byextra}">W</a></td>
	<td><b>Go</b></td>
	<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}{$byextra}">E</a></td></tr>
	<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}{$byextra}">SW</a></td>
	<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}{$byextra}">S</a></td>
	<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}{$byextra}">SE</a></td></tr>
	</table>
</div>


<h2 style="margin-bottom:0;">Grid reference {if $gridref6}{$gridref6}{else}{$gridref}{/if}</h2>
{if $place}<h3>{place place=$place}</h3>{/if}

<br style="clear:both; padding:10px"/>

{if $showresult}
	{* We have a valid GridRef *}
{/if}


{if $errormsg}
	<p>{$errormsg}</p>
{/if}

<div class="twocolsetup">
	<div class="twocolumn">

<h3>Coverage</h3>

{if $square->percent_land < 50 && $square->percent_land != -1}
	<form action="/mapfixer.php" method="get">
		<p align="right"><input type="submit" name="save" value="Bring {$gridref|escape:'html'} to the attention of a moderator"/>
		<input type="hidden" name="gridref" value="{$gridref|escape:'html'}"/>
		</p>
	</form>
{/if}

{if $imagecount}
		{* There are some thumbnails to display *}
    
      <div style="text-align:center;">
      <big>We have
			{if $imagecount eq 1}just one image{else}<b>{$imagecount|thousends}</b> images{/if}
			{if $totalimagecount && $totalimagecount ne $imagecount && !$filtered}(and {$totalimagecount-$imagecount} hidden){/if}
			{if $mode eq 'takenfrom'} of other squares, taken from <b>{$gridref}</b>
			{elseif $mode eq 'mentioning'} mentioning <b>{$gridref}</b> <sup>[Note: currently only matches 4 figure grid references]</sup>
			{else} in grid square <b>{$gridref}</b>
			{/if}
      </big>
			{if false && $imagecount > 15}<p><a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">View all images page by page</a></p>{/if}
			</div>

{else}
		{* There are no images in this square (yet) *}

		<div style="text-align:center"><big>We have no images for <b>{$gridref}</b> yet.</big></div>
    
		{if $nearest_distance}
			<p>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance} km away.</p>
		{else}
			<p>There are no pictures for any grid square within 100km.</p>
		{/if}
    
    <ul class="buttonbar">
<li><select onchange="window.location.href=this.value">
				<option value="">Search for nearby images</option>
				<option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=full&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=thumbs&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=grid&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=slide&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=map&amp;do=1">Map</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=20&amp;displayclass=black&amp;do=1">Georiver</option>
</select></li></ul>
    
{/if} 
  
<h4 style="margin-bottom:0; margin-top:2px;">Contribute</h4>

<ul class="buttonbar">

<li><a href="{if $user->submission_method == 'submit2'}/submit2.php#gridref={$gridrefraw}{else}/submit.php?gridreference={$gridrefraw}{/if}">Submit your own photo</a></li>

{if $enable_forums}
		{if $discuss}
			<li><a href="/discuss/index.php?gridref={$gridref}">View Discussion</a>
		{else}
			<li><a href="/discuss/index.php?gridref={$gridref}">Discuss {$gridref}</a>
		{/if}
{/if}


</ul>

 
{if $imagecount} 

{if $gridref6}
<h4 style="margin-bottom:0; margin-top:2px;">Explore centiquare {$gridref6}</h4>

<ul class="buttonbar">
{if $imagecount}
<li><a href="/gridref/{$gridref}?viewcenti={$gridref6}">View image(s) taken in {$gridref6}</a></li>
<li><a href="/gridref/{$gridref}?centi={$gridref6}">View subjects in {$gridref6}</a> (if any)</li>
{/if}
<li><select onchange="window.location.href=this.value">
				<option value="">Search images by distance</option>
				<option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=full&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=thumbs&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=grid&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=slide&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=map&amp;do=1">Map</option>
        <option value="/search.php?gridref={$gridref6}&amp;distance=2&amp;displayclass=black&amp;do=1">Georiver</option>
</select></li>

</ul>

{/if}




<h4 style="margin-bottom:0; margin-top:2px;">Explore gridsquare {if $gridref6}<a href="/gridref/{$gridref}">{$gridref}</a>{else}{$gridref}{/if}</h4>  

<ul class="buttonbar">

<li>
<select onchange="window.location.href=this.value">
    <option>View all {$imagecount} images in the search</option>
    <optgroup label="Most recent first">
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbsmore&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=bigger&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=grid&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Map</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=black&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="One image per">
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;do=1">Day taken</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;groupby=auser_id&amp;breakby=user_id&amp;do=1">Contributor</option>
        <option value="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;groupby=scenti&amp;do=1">Centisquare</option>
    </optgroup>
</select>
</li>



<li><a href="/browser/#!/grid_reference+%22{$gridref}%22">View this square in the browser</a></li>

<li><select onchange="window.location.href=this.value">
<option value="">View images in Finder grouped by...</option>
		<optgroup label="Subject">
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=context_ids">Geographical context</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=tag_ids">Tags</option>
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=snippet_ids">Shared description</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=group_ids">Clusters</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=subject_ids">Subject</option>
		</optgroup>
    		<optgroup label="Date taken">
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=decade">Decade taken</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=takenyear">Year taken</option>
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=takenmonth">Month taken</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=takenday">Day taken</option>
		</optgroup>
    <optgroup label="Other">
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=format">Image format</option>
        <option value="/finder/groups.php?q=%5E{$gridref}&amp;group=user_id">Contributor</option>
				<option value="/finder/groups.php?q=%5E{$gridref}&amp;group=segment">When submitted</option>
		</optgroup>
</select></li>


{* Collections in the gridsquare*}
{if $square && $square->collections}
    <li><select onchange="window.location.href=this.value">
        <option value="">Collections</option>
        {assign var="lasttype" value="0"}
        {foreach from=$square->collections item=item}
            {if $lasttype != $item.type}
                {if $lasttype}
                    </optgroup>
                {/if}
                <optgroup label="{$item.type|regex_replace:"/y$/":'ie'}s">
                {assign var="lasttype" value=$item.type}
            {/if}
            <option value="{$item.url}">{$item.title|escape:'html'}</option>
        {/foreach}
        </optgroup>
    </select></li>
{/if}

</ul>

{/if}


<h4 style="margin-bottom:0; margin-top:2px;">Surrounding area</h4>

<ul class="buttonbar">
<li><select onchange="window.location.href=this.value">
				<option value="">Geograph coverage maps</option>
				<option value="/mapbrowse.php?new=1&amp;o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1">Interactive coverage map</option>
        <option value="/maplarge.php?t={$hectad_row.largemap_token}">Photo mosaic</option>
        <option value="/mapper/combined.php?mobile=1&#35;15/{$lat}/{$long}">Mobile coverage map</option>
        <option value="/browser/#!/grid_reference+%22{$gridref}%22">Browser map</option>
        <option value="/leaflet/all.php#16/{$lat}/{$long}">All Geograph projects map</option>
				<option value="/mapsheet.php?zoom=15&lat={$lat}&lon={$long}">Printable Checksheet</option>
        {if $user}<option value="/mapsheet.php?zoom=15&lat={$lat}&lon={$long}&mine=1">Printable Checksheet (personalised)</option>{/if}
        <option value="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1">Original coverage maps</option>
</select></li>

<li><select onchange="window.location.href=this.value">
				<option value="">Mapping links</option>
				<option value="https://www.google.co.uk/maps/search/{$lat},{$long}/">Google maps</option>
        <option value="https://www.google.co.uk/maps/dir/?api=1&amp;destination={$lat},{$long}">Google maps - Navigate to {if $gridref6}{$gridref6}{else}{$gridref}{/if}</option>
        <option value="http://www.openstreetmap.org/?mlat={$lat}&amp;mlon={$long}&amp;zoom=14">OpenStreetMaps</option>
        <option value="https://maps.nls.uk/geo/find/marker/#zoom=13&lat={$lat}&lon={$long}&f=1&z=1&marker={$lat},{$long}">National Library of Scotland</option>
        <option value="http://wtp2.appspot.com/wheresthepath.htm?lat={$lat}&amp;lon={$long}">Where's the path</option>
        <option value="https://www.bing.com/maps?v=2&amp;cp={$lat}~{$long}&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1">Bing maps</option>
        <option value="/gridref/{$gridrefraw}/links">Links page with additional map choices</option>
</select></li>



<li><select onchange="window.location.href=this.value">
				<option value="">Explore</option>
				<option value="/gridref/{$hectad}">Hectad {$hectad}</option>
        <option value="/gridref/{$gridsquare}">Myriad {$gridsquare}</option>
        <option value="/content/?q={$gridref}">Nearby collections</option>
        <option value="/finder/bytag.php?q={$gridref}">Tags used nearby</option>
</select></li>
    
</ul>


<br/>
<div style="text-align:center"><big><img src="{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$gridrefraw}/links">More Links for {$gridrefraw}</a></big></div>
<br/>


<br style="clear:both"/>
</div>
 

<div class="twocolumn">
<h3>Maps</h3>
  <div style="width:100%; text-align:center;">
  {if $rastermap->enabled}
	<div style="display: inline-block; vertical-align: middle; text-align:center; width:{$rastermap->width}px; font-size:0.8em; margin:5px;">
	{$rastermap->getImageTag($gridrefraw)}
	<div style="color:gray"><small>{$rastermap->getFootNote()}</small></div>
	{$rastermap->getScriptTag()}	
	</div>
  {/if}


	{if $overview}
	<div style="display: inline-block; vertical-align: middle; text-align:center; width:{$overview_width+30}px; margin:5px;">
	{include file="_overview.tpl"}
  <div style="color:gray"><small>Tip: Click the map to open the coverage map</small></div>
	</div>
  {/if}
</div>

</div>
  


  

</div>


<br style="clear:both"/>
</div>

{if $imagecount}
		{* There are some thumbnails to display *}



{if $showresult}
	{if $totalimagecount && !$square->has_recent && $user->registered}
		<div class="interestBox" style="text-align:center;margin-bottom:20px">
			This square doesn't have any recent images, taken in the last 5 years. Can you <a href="{if $user->submission_method == 'submit2'}/submit2.php#gridref={$gridrefraw}{else}/submit.php?gridreference={$gridrefraw}{/if}">add some</a>? You can get a TPoint!
		</div>
	{/if}

	{* We have a valid GridRef *}

	{if $mode eq 'takenfrom'}
		{assign var="tab" value="8"}
	{elseif $mode eq 'mentioning'}
		{assign var="tab" value="9"}
	{elseif $sample}
		{assign var="tab" value="1"}
	{elseif $breakdown}
		{assign var="tab" value="4"}
	{elseif $breakdowns}
		{assign var="tab" value="3"}
	{elseif $filtered}
		{assign var="tab" value="6"}
	{elseif $totalimagecount > 0}
		{assign var="tab" value="2"}
	{/if}

	<div class="tabHolder" style="margin-left:10px">
		{if $sample}
			<b class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1">Sample images</b>
		{elseif $imagecount >= 15}
			<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" href="/gridref/{$gridref}">Sample images</a>
		{elseif $imagecount && $imagecount < 15}
			<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" href="/gridref/{$gridref}">Images in {$gridref}</a>
		{/if}
		{if $totalimagecount}
			<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" href="/gridref/{$gridref}?by=1">Breakdown list</a>
		{/if}
		{if $bby}
			<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" href="{linktoself name="by" value=$bby delete=$bby}">List of filters</a>
		{elseif $breakdown}
			<b class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4">{$breakdown_title|default:"Filter"} breakdown</b>
		{/if}

		{if $filtered}
			<b class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6">Filtered List</b>
			{if $by ne 'centi' && $by ne 'viewcenti' && $mode eq 'normal'}
				<a class="tab" href="{linktoself name="by" value="centi"}">Centisquare Coverage</a>
			{/if}
		{/if}
		{if $viewpoint_count || $mode eq 'takenfrom'}
			<a class="tab{if $tab == 8}Selected{/if} nowrap" id="tab8" href="/gridref/{$gridref}?takenfrom">images taken <i>from</i> {$gridref}{if $viewpoint_count} [{$viewpoint_count}]{/if}</a>
		{/if}
		{if $mention_count || $mode eq 'mentioning'}
			<a class="tab{if $tab == 9}Selected{/if} nowrap" id="tab9" href="/gridref/{$gridref}?mentioning"><i>mentioning</i> {$gridref}{if $mention_count} [{$mention_count}]{/if}</a>
		{/if}

		{if $square->premill}
			<a class="tab{if $tab == 10}Selected{/if} nowrap" id="tab10" 
			{if $square->premill > 100} 
				href="/browser/#!/taken=,1999-12-31/grid_reference+%22{$gridref}%22">
			{else}
				href="/stuff/list.php?gridref={$gridref}&amp;premill=1">
			{/if}
			taken <i>pre 2000</i> [{$square->premill}]</a></li>
		{/if}

	</div>

	<div class="interestBox" style="position:relative; margin-left:10px">
	{if $sample}<big>
	A <b>sample</b> of {$sample|thousends} photos from <b>{$imagecount|thousends}</b>
	{else}
	We have
	{if $imagecount eq 1}just one image{else}{$imagecount} images{/if}
	{if $totalimagecount && $totalimagecount ne $imagecount && !$filtered}(and {$totalimagecount-$imagecount} hidden){/if}
	{/if}

	{if $mode eq 'takenfrom'}
		taken from <b>{$gridref}</b> of other squares
	{elseif $mode eq 'mentioning'}
		mentioning <b>{$gridref}</b> <sup>[Note: currently only matches 4 figure grid references]</sup>
	{else}
		for <b>{$gridref}</b>
	{/if}
	{if $sample}</big> - <a href="/browser/#!/grid_reference+%22{$gridref}%22">View this square in the Browser &gt;&gt;&gt;</a>{/if}
	</div>

	<div style="position:relative; text-align:right; font-size:0.7em">

	{if $user->registered && $mode eq 'normal'}
		[{if !$nl}
			<a href="{linktoself name="nl" value="1"}">Include {if !$is_mod}my {/if}<b>pending and rejected</b> images</a>
		{else}
			<a href="{linktoself name="nl" value="0"}">Exclude {if !$is_mod}my {/if}<b>pending and rejected</b> images</a>
		{/if}] &nbsp;
	{/if}
	{if $breakdown}
		[{if !$ht}
			<a href="{linktoself name="ht" value="1"}">Hide <b>thumbnail</b> images</a>
		{else}

			<a href="{linktoself name="ht" value="0"}">Show <b>thumbnail</b> images</a>
		{/if}] &nbsp;
	{/if}
	</div>

	{if $breakdown}
		{* We want to display a breakdown list *}
		<blockquote>
		<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select images <b>{if $filtered_title}{$filtered_title},{/if} by {$breakdown_title}</b>:</p>

		{if $by eq 'centi' || $by eq 'viewcenti'}
			{if $lat}
				<p>View on <a href="/mapper/combined.php{if $by eq 'viewcenti'}?views=1{/if}#15/{$lat}/{$long}">Interactive <b>Coverage Map</b></a></p>
			{/if}
			<p><small>The 100 centisquares of {$gridref} are laid out on the grid below, of which {$allcount} have photos, hover over the square to see the 6 figure grid reference.</small></p>

	<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}&amp;by={$by}{$extra}">NW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}&amp;by={$by}{$extra}">N</a></td>
		<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}&amp;by={$by}{$extra}">NE</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}&amp;by={$by}{$extra}">W</a></td>
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
						<th height="30"><a href="javascript:void(selOption('n','{$yy}'))">{$yy}</a></th>
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
						<th width="20"><a href="javascript:void(selOption('e','{$xx}'))">{$xx}</a></th>
					{/foreach}
				</tr>
			</table>



			{if $rastermap->enabled && $rastermap->mapurl}
					</div>
				</div>
			{/if}
	</td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}&amp;by={$by}{$extra}">E</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}&amp;by={$by}{$extra}">SW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}&amp;by={$by}{$extra}">S</a></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}&amp;by={$by}{$extra}">SE</a></td></tr>
	</table>
			{if $breakdown.50.50.link}
				<ul>
				<li><a href="{$breakdown.50.50.link}" title="{$breakdown.50.50.name}">{$breakdown.50.50.name}</a> [{$breakdown.50.50.count}]</li>
				</ul>
			{/if}

			<form method="get" action="/search.php">
				<input type="hidden" name="form" value="browse"/>
				<div class="interestBox" style="width:450px">
				<b>Search local images</b>:<br/>

				<label for="">Centisquare</label>:
					<select id="gridsquare" name="gridsquare">
						{html_options options=$prefixes selected=$gridsquare}
					</select>
					<label for="eastings">E</label>
					<select id="eastings" name="eastings">
						{html_options options=$kmlist selected=$eastings}
					</select>
					<select id="centie" name="centie" style="font-size:1.4em">
						{html_options options=$tenup selected=$e}
					</select>
					<label for="northings">N</label>
					<select id="northings" name="northings">
						{html_options options=$kmlist selected=$northings}
					</select>
					<select id="centin" name="centin" style="font-size:1.4em">
						{html_options options=$tenup selected=$n}
					</select>
				<br/>

				<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
				<input type="submit" value="Search"/><br/>
				<input type="radio" name="distance" value="0.1" checked id="d1"/><label for="d1">in centisquare only</label> /
				<input type="radio" name="distance" value="0.3" id="d3"/><label for="d3">incl. surrounding centisquare</label> /
				<input type="radio" name="distance" value="0.6" id="d6"/><label for="d6">600m</label><br/>
				<input type="hidden" name="do" value="1"/>
				</div>
			</form>
			<script type="text/javascript">{literal}
				function selOption(name,value) {
					var ele = document.getElementById("centi"+name);
					for(q=0;q<ele.options.length;q++) {
						if (ele.options[q].value == value) {
							ele.selectedIndex=q;
						}
					}
				}
			{/literal}</script>
		{else}
			{if !$ht}
				<p align="center" style="color:gray; font-size:0.8em">{if $breakdown_count> 20}Random 20 groupings{else}The groupings will{/if} show an example image [total number in brackets].</p>
			{/if}
			<table>
			{foreach from=$breakdown item=b}

				{if $b.image}
					<tr><td>&middot; <a href="{$b.link}">{$b.name}</a> <b>[{$b.count}]</b>{if $b.centi}<br/><small style="font-size:0.7em;margin-left:30px">[<a href="{$b.centi}">Centisquare Distribution</a>]<small>{/if}</td>


					<td align="middle"><a title="{$b.image->grid_reference} : {$b.image->title|escape:'html'} by {$b.image->realname} {$b.image->dist_string} - click to view full size image" href="/photo/{$b.image->gridimage_id}">{$b.image->getThumbnail($thumbw,$thumbh,false,true)}</a></td>
					<td><div class="caption">{if $mode != 'normal'}<a href="/gridref/{$b.image->grid_reference}">{$b.image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$b.image->gridimage_id}">{$b.image->title|escape:'html'}</a></div>
					<div class="statuscaption">by <a href="{$b.image->profile_link}">{$b.image->realname}</a></div></td>
					</tr>
				{else}
					<tr><td colspan="3">&middot; <a href="{$b.link}">{$b.name}</a> [{$b.count}]</td></tr>
				{/if}
			{/foreach}
			</table>
		{/if}
		<br style="clear:both" />
		<p>{if $imagecount < 15}<a href="/gridref/{$gridref}?by=1{if $extra}{$extra}{/if}">&lt;&lt; Choose a different filter method</a></p>{/if}

		</blockquote>
	{else}
		{if $breakdowns}
			{* We want to choose a breakdown criteria to show *}

			<blockquote><p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select a method to browse the images:</p></blockquote>

			{if $image}
			<div style="float:right;" class="photo33"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true)}</a>
			<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
			<div class="statuscaption">classification:
			  {if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}{if $image->ftf eq 1} (first){/if}</div>
			</div>
			{/if}

			<ul>
				<li style="margin-bottom:10px;"><a href="/browser/#!/grid_reference+%22{$gridref}%22">View and explore other breakdowns in the Browser</a></li>
				<li style="margin-bottom:3px;"><small>by </small><a href="/finder/groups.php?q=%5E{$gridref}&amp;group=context_ids"><b>Geographical Context</b></a></li>

			{foreach from=$breakdowns item=b}
				<li style="margin-bottom:2px"><small>by </small><a href="/gridref/{$gridref}?by={$b.type}{$extra}"><b>{$b.name}</b></a>{if $b.count != 'unknown'} <small>[{$b.count}]</small>{/if}</li>
			{/foreach}

				<li style="margin-top:8px;"><small>by </small><a href="/finder/bytag.php?q=grid_reference:{$gridref}"><b>Tags</b></a><small> (<a href="/finder/bytag.php?q={$gridref}">inc surrounding squares</a>)</small></li>

				<li style="margin-top:10px;">or <b>Clustering Options</b>:<br/>
				&nbsp; &middot; <a href="/search.php?gridref={$gridref}&amp;cluster2=1&amp;orderby=label">Automatic</a>,
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=cluster2&amp;breakby=realname%2B&amp;orderby=imagetaken&amp;reverse_order_ind=1&amp;do=1">Contributor</a>,
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=cluster2&amp;breakby=imagetaken%2B&amp;orderby=imagetaken&amp;reverse_order_ind=1&amp;do=1">Date Taken</a>{if $imagecount < 500} or
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbs&amp;breakby=imagetaken_year&amp;orderby=imagetaken&amp;reverse_order_ind=1&amp;do=1">Year Taken</a>{/if}
				</li>
			</ul>
			<br style="clear:both"/>
		{else}
			{* Display some actual thumbnails *}


			{if $filtered}
				<blockquote><p>{$totalimagecount} Images, {$filtered_title}...</p></blockquote>
			{/if}

			{if $displayclass == 'full'}
				<table cellspacing="0" cellpadding="3" border="0" style="margin-left:20px">
				{foreach from=$images item=image}
					<tr>
						<td valign="top" align="center" class=shadow>
							<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a>
						</td>
						<td valign="top" style="border-bottom:1px solid silver">
							{if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
							<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
							{if $image->comment}
									<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:250:"... (<u>more</u>)"|geographlinks}</div>
							{/if}
						</td>
					</tr>
				{/foreach}
				</table>

			{elseif $displayclass == 'tiles2'}
				<table class=shadow cellspacing="0" cellpadding="3" border="0" style="margin-left:20px">
				{foreach from=$images2 item=images}
					<tr>
					{foreach from=$images item=image name="loop"}
						<td valign="top" align="center" width="{$thumbw+50}" bgcolor="{cycle values="#666666,#6C6C6C"}">
							<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a>
						</td>
					{/foreach}
					</tr>
					<tr>
					{foreach from=$images item=image name="loop"}
						<td valign="top" align="center" bgcolor="{cycle values="#6C6C6C,#666666"}">
							<div class="caption">
							{if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a></div>
							<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
						</td>
					{/foreach}
					</tr>
				{/foreach}
				</table>

			{elseif $displayclass == 'thumbs'}
				<div class="shadow" style="position:relative;margin-top:3px; margin-left:20px">
				{foreach from=$images item=image}
					<div style="float:left;position:relative; width:{$thumbw+10}px; height:{$thumbh+10}px">
					<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
				{/foreach}
				</div>
			{else} 
				{if $displayclass == 'tilesbig'}
				{assign var="thumbw" value="213"}
				{assign var="thumbh" value="160"}
				{/if}

				{foreach from=$images item=image}
					<div class="photo33 shadow" style="border:0;float:left; {if $sample && $displayclass != 'tilesbig'}width:180px{/if}"><div style="height:{$thumbh+8}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
					<div class="caption"><div class="minheightprop" style="height:2.5em"></div>{if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
					<div class="statuscaption">{if $image->year && $image->year > '1'}{$image->year} {/if}by <a href="{$image->profile_link}">{$image->realname}</a></div>
					</div>
				{/foreach}
			{/if}

			</table>
			<br style="clear:left;"/>&nbsp;

			{if $bby == 'centi'}
				<form method="get" action="/search.php">
					<input type="hidden" name="form" value="browse"/>
					<div class="interestBox">
					<b>Search local images</b>:<br/>
					<label for="gridref">Centisquare</label>:
						<input type="text" name="gridref" id="gridref" size="20" value="{$gridrefraw|escape:'html'}"/><input type="submit" value="Search"/>
					<br/>

					<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/> (can be left blank to just list all images)
					<br/>
					<input type="radio" name="distance" value="0.1" checked id="d1"/><label for="d1">In centisquare only</label> /
					<input type="radio" name="distance" value="0.3" id="d3"/><label for="d3">inc surrounding centisquare</label> /
					<input type="radio" name="distance" value="0.6" id="d6"/><label for="d6">600m</label><br/>
					<input type="hidden" name="do" value="1"/>
					</div>
				</form>
			{/if}

			{if $mode eq 'takenfrom'}
				<div class="interestBox">| <a href="/search.php?searchtext={$viewpoint_query}&amp;displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">View these photos on a map</a> | <a href="/search.php?searchtext={$viewpoint_query}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Find all images taken from this square</a> |</div>
			{elseif $mode eq 'mentioning'}
				<div class="interestBox">| <a href="/search.php?searchtext={$gridref}+-gridref:{$gridref}&amp;displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1&amp;resultsperpage=50">View these photos on a map</a> | <a href="/search.php?searchtext={$gridref}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Find all images about this square</a> |</div>
			{/if}
			{if $sample}
				<div class="interestBox"> Explore more images in this square: | <a href="{linktoself name="by" value="1"}">View <b>Filtering options</b></a> | <a href="/search.php?gridref={$gridref}&amp;distance=1&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">View <b>{if $imagecount > 1000}upto 1000 {else}all {$imagecount}{/if} images</b> page by page &gt;&gt;&gt;</a> |</div><br/>
			{/if}



			<div class="interestBox" style="text-align:center" data-nosnippet>
			<form action="/gridref/{$gridref}" method="get" style="display:inline">
			<label for="displayclass">Display Format:</label>
			<select name="displayclass" id="displayclass" size="1" onchange="this.form.submit()">
				{html_options options=$displayclasses selected=$displayclass}
			</select>
			{if $legacy}<input type="hidden" name="legacy" value="1"/>{/if}
			<noscript>
			<input type="submit" value="Update"/>
			</noscript>
			</form> &nbsp;&nbsp; | &nbsp;&nbsp;

			<form method=post style="display:inline-block">{dynamic}
				Background Colour: {if strpos($maincontentclass, "photowhite")}White{else}<button type=submit name=style value=white>White</button>{/if}
				 / {if strpos($maincontentclass, "photoblack")}Black{else}<button type=submit name=style value=black>Black</button>{/if}
				 / {if strpos($maincontentclass, "photogray")}Gray{else}<button type=submit name=style value=gray>Grey</button>{/if}
			{/dynamic}</form>

			</div>

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

<br style="clear:both"/>
{/if}

   	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}

{include file="_std_end.tpl"}
{/dynamic}
