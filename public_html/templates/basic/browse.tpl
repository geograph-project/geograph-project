{dynamic}
{if $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}

{include file="_std_begin.tpl"}

 
<div style="position:relative;margin-top:5px; margin-left:10px; width:850px;">
<div style="position:relative;float:left;width:530px">

{if $showresult}
	<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px">
	<table border="0" cellspacing="0" cellpadding="2">
	<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=-1">NW</a></td>
	<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=0">N</a></td>
	<td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=1">NE</a></td></tr>
	<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=-1">W</a></td>
	<td><b>Go</b></td>
	<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=1">E</a></td></tr>
	<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=-1">SW</a></td>
	<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=0">S</a></td>
	<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=1">SE</a></td></tr>
	</table>
	</div>
{else}
	   <h2>Browse</h2>
	<p>You can view a particular grid square below - if the square hasn't been filled yet,
	we'll tell you how far away the nearest one is.</p>
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

	{if $imagecount}
		{* There are some thumbnails to display *}
		<small><small><b>Sample links for this square...</b></small></small>
		<ul style="margin-top:5px; padding-left:24px">
	{else}
		{* There are no images in this square (yet) *}
		
		<p>We have no images for <b>{$gridref}</b> yet,
		
		{if $nearest_distance}
			</p>
			<small><small><b>Sample links...</b></small></small>
			<ul style="margin-top:5px; padding-left:24px">
			<li>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away<br/><br/></li>
		{else}
			and have no pictures for any grid square within 100km either!</p>
			<small><small><b>Sample links...</b></small></small>
			<ul style="margin-top:5px; padding-left:24px">
		{/if}
	{/if}
		<li><a href="/submit.php?gridreference={$gridrefraw}"><b>submit your own picture for {$gridref}</b></a></li>
		{if $enable_forums}
			<li>
			{if $discuss}
				There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
				<a href="/discuss/index.php?gridref={$gridref}"><b>discussion</b> about {$gridref}</a> (preview on the left)
			{else}
				{if $user->registered} 
					<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a <b>discussion</b> about {$gridref}</a>
				{else}
					<a href="/login.php">login</a> to start a <b>discussion</b> about {$gridref}</a>
				{/if}
			{/if}</li>
		{/if}

		<li><a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}">Geograph <b>map</b> for {if $gridref2}{$gridrefraw}{else}{$gridref}{/if}</a>{if $square->reference_index == 1} (<a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}"><b>Draggable</b>)</a>{/if}</li>
		
		{if $gridref6}
			<li style="margin-top:10px"><a href="/gridref/{$gridref}?viewcenti={$gridref6}">image(s) <b>taken in {$gridref6}</b></a> / <span class="nowrap"><a href="/gridref/{$gridref}?centi={$gridref6}">of <b>subjects in {$gridref6}</b></a> (if any)</span></li>
		{/if}
		
		{if $viewpoint_count}
			<li style="margin-top:10px"><a href="/gridref/{$gridref}?takenfrom">view <b>{$viewpoint_count} images taken <i>from</i> {$gridref}</b></a></li>
		{/if}
		{if $mention_count}
			<li><a href="/gridref/{$gridref}?mentioning">view <b>{$mention_count} images <i>mentioning</i> {$gridref}</b></a> <sup style="color:red">new!</sup></li>
		{/if}
		
		</ul>
	<p><big><img src="http://{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" alt="geotagged!"/> <b><a href="/gridref/{$gridrefraw}/links">More Links for {$gridrefraw}</a></b> </big></p>
{/if}

</div>
{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative;font-size:0.8em">
	{$rastermap->getImageTag($gridrefraw)}
	{if $rastermap->getFootNote()}
	<div class="interestBox" style="margin-top:3px;margin-left:2px;padding:1px;"><small>{$rastermap->getFootNote()}</small></div>
	{/if}
	{if count($square->services) > 1}
	<form method="get" action="/gridref/{$gridref}">{*FIXME*}
	<p>Karte:
	<select name="sid">
	{html_options options=$square->services selected=$sid}
	</select>
	<input type="submit" value="Los"/></p></form>
	{/if}
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
	
	{if $mode eq 'takenfrom'}
		taken from <b>{$gridref}</b>
	{elseif $mode eq 'mentioning'}
		mentioning <b>{$gridref}</b> <sup>[Note: Currently only matches 4 Figure Grid References]</sup>
	{else}
		for <b>{$gridref}</b>
	{/if}
	{if !$breakdown && !$breakdowns && $totalimagecount > 0}<span style="font-size:0.8em;">- click for larger version</span>{/if}</div>

	<div style="position:relative;float:right; text-align:right; font-size:0.7em">
	{if !$breakdown && !$breakdowns && $totalimagecount > 0 &&  $totalimagecount > 1}
		<a href="{linktoself name="by" value="1"}">View as <b>breakdown list</b></a>&nbsp;<br/>
	
	{/if}	
	{if $user->registered && $mode eq 'normal'}
		{if !$nl}
			<a href="{linktoself name="nl" value="1"}">Include <b>pending and rejected</b> images</a>&nbsp;<br/>
		{else}
			<a href="{linktoself name="nl" value="0"}">Exclude <b>pending and rejected</b> images</a>&nbsp;<br/>
		{/if}
	{/if}
	{if $breakdown}
		{if !$ht}
			<a href="{linktoself name="ht" value="1"}">Hide <b>thumbnail</b> images</a>&nbsp;<br/>
		{else}
			
			<a href="{linktoself name="ht" value="0"}">Show <b>thumbnail</b> images</a>&nbsp;<br/>
		{/if}
	{/if}
	</div>
	
	{if $breakdown}
		{* We want to display a breakdown list *}
		<blockquote>
		<p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select images <b>{if $filtered_title}{$filtered_title},{/if} by {$breakdown_title}</b>:</p>

		{if $by eq 'centi' || $by eq 'viewcenti' }
			<p><small>The 100 centisquares of {$gridref} are laid out on the grid below, of which {$allcount} have photos, hover over the square to see the 6figure grid reference.</small></p>
	<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=-1&amp;by={$by}">NW</a></td>
		<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=0&amp;by={$by}">N</a></td>
		<td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=1&amp;by={$by}">NE</a></td></tr>
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=-1&amp;by={$by}">W</a></td>
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
		<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=1&amp;by={$by}">E</a></td></tr>
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=-1&amp;by={$by}">SW</a></td>
		<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=0&amp;by={$by}">S</a></td>
		<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=1&amp;by={$by}">SE</a></td></tr>
	</table>
			{if $breakdown.50.50.link}
				<ul>
				<li><a href="{$breakdown.50.50.link}" title="{$breakdown.50.50.name}">{$breakdown.50.50.name}</a> [{$breakdown.50.50.count}]</li>
				</ul>
			{/if}
		{else}
		{if !$ht}
			<span style="color:gray; font-size:0.8em">{if $breakdown_count> 20}Random 20 groupings{else}The groupings will{/if} show an example image [total number in brackets].</span>
		{/if}
			<ul style="margin-top:0">
			{foreach from=$breakdown item=b}
				
				{if $b.image}
					<div class="photo33" style="float:left;padding:2px;margin:2px">
					<div class="interestBox" style="height:2.4em;padding:1px;margin:-2px"><a href="{$b.link}">{$b.name}</a> <b>[{$b.count}]</b></div><br/><br/>
					
					
					<div style="height:{$thumbh}px;vertical-align:middle"><a title="{$b.image->grid_reference} : {$b.image->title|escape:'html'} by {$b.image->realname} {$b.image->dist_string} - click to view full size image" href="/photo/{$b.image->gridimage_id}">{$b.image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
					<div class="caption"><div class="minheightprop" style="height:2.5em"></div>{if $mode != 'normal'}<a title="view full size image" href="/gridref/{$b.image->grid_reference}">{$b.image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$b.image->gridimage_id}">{$b.image->title|escape:'html'}</a><div class="minheightclear"></div></div>
					<div class="statuscaption">by <a href="{$b.image->profile_link}">{$b.image->realname}</a></div>
					</div>
				{else}
					<li style="clear:both"><a href="{$b.link}">{$b.name}</a> [{$b.count}]</li>
				{/if}
			{/foreach}
			</ul>	
		{/if}
		<br style="clear:both" />
		<p>{if $imagecount < 15}<a href="/gridref/{$gridref}?by=1{if $extra}?{$extra}{/if}">&lt;&lt; Choose a different filter method</a></p>{/if}
		
		</blockquote>
	{else}
		{if $breakdowns}
			{* We want to choose a breakdown criteria to show *}

			<blockquote><p>{if $imagecount > 15}Because there are so many images for this square, please{else}Please{/if} select how you would like to view the images</p></blockquote>

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

			<li style="margin-top:10px;">Clustering: <a href="/search.php?gridref={$gridref}&amp;cluster2=1&amp;orderby=label">Automatic</a><sup style="color:red">Experimental</sup>, 
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=cluster2&amp;breakby=imageclass%2B&amp;orderby=imageclass&amp;do=1">Category</a>, 
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=cluster2&amp;breakby=realname%2B&amp;orderby=imagetaken&amp;reverse_order_ind=1&amp;do=1">Contributor</a> or
				<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=cluster2&amp;breakby=imagetaken%2B&amp;orderby=imagetaken&amp;reverse_order_ind=1&amp;do=1">Date Taken</a> <sup style="color:red">New!</sup></li>

			<li style="margin-top:10px;">
			<form method="get" action="/search.php">
				Or <b>search within images in this square</b>:<br/> 
				<div class="interestBox" style="width:400px">
				<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="30"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
				<input type="submit" value="Search"/><br/>
				<input type="hidden" name="location" value="{$gridref}"/>
				<input type="radio" name="distance" value="1" checked id="d1"/><label for="d1">In {$gridref} only</label> /
				<input type="radio" name="distance" value="3" id="d3"/><label for="d1">including surrounding squares</label><br/>
				<input type="checkbox" name="displayclass" value="thumbs" id="dc"/><label for="dc">Show thumbnails only</label> <small>(<a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Direct Link</a>)</small>
				<input type="hidden" name="do" value="1"/>
				</div>
				<small>(leave keywords box blank to simply page though all images)</small><br/>
			</form></li>

			</ul>
			<br style="clear:both"/>
		{else}
			{* Display some actual thumbnails *}
			
			
			{if $filtered}
				<blockquote><p>{$totalimagecount} Images, {$filtered_title}... (<a href="/gridref/{$gridref}{if $extra}?{$extra}{/if}">Remove Filter</a>)</p></blockquote>
			{/if}

			{foreach from=$images item=image}
				<div style="float:left;" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
				<div class="caption"><div class="minheightprop" style="height:2.5em"></div>{if $mode != 'normal'}<a title="view full size image" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
				<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
				</div>
			{/foreach}
			<br style="clear:left;"/>&nbsp;
			
			{if $mode eq 'takenfrom'}
				<div class="interestBox">| <a href="/search.php?searchtext={$viewpoint_query}&amp;displayclass=gmap&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">View these photos on a Map</a> |</div>
			{elseif $mode eq 'mentioning'}
				<div class="interestBox">| <a href="/search.php?searchtext={$gridref}+-gridref:{$gridref}&amp;displayclass=gmap&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1&amp;resultsperpage=50">View these photos on a Map</a> | <a href="/search.php?searchtext={$gridref}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Find all images about this square</a> |</div>
			{/if}
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
