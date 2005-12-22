{dynamic}
{if $showresult}
	{assign var="page_title" value="$gridref :: Browse"}
{else}
	{assign var="page_title" value="Browse"}
{/if}
{/dynamic}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin_dynamic.tpl"}
{dynamic}

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
we'll tell you how far away the nearest one is (Use {getamap gridref='' text='Ordnance Survey Get-a-Map'} to help locate your grid square)</p>
{/if}

<form action="/browse.php" method="get">
<div>

	<label for="gridref">Enter grid reference (e.g. SY9582)</label>
	<input id="gridref" type="text" name="gridref" value="{$gridref|escape:'html'}" size="8"/>
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
{/if}
{if $showresult}




	{if $totalimagecount}
		<ul>
		<li>		  
		{if $discuss}
			There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
			<a href="/discuss/index.php?gridref={$gridref}">discussion about {$gridref}</a> (preview on the left)

		{else}
			{if $user->registered} 
				<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a discussion about {$gridref}</a>
			{else}
				<a href="/login.php">login</a> to start a discussion about {$gridref} 
			{/if}
		{/if}</li>
		<li><a href="/submit.php?gridreference={$gridrefraw}"><b>Submit</b> your own picture of {$gridref}</a>.</li>
		<li><a href="/search.php?q={$gridref}"><b>Search</b> for other nearby images</a>.</li>
		<li><a href="/mapbrowse.php?t={$map_token}">Geograph <b>map</b> for {$gridref}</a> (<a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}">print check sheet</a>).</li>
		
		
		<li><b>We have 
		{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
		{if $totalimagecount && $totalimagecount ne $imagecount}
			({$totalimagecount} including hidden)
		{/if}
		for {getamap gridref=$gridref text=$gridref title="OS Get-a-Map for $gridref"}</b> - click for larger version.</li>
		</ul>
		
		{foreach from=$images item=image}
		
		  <div style="float:left;" class="photo33"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a>
		  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>

		  <div class="statuscaption">status:
			{if $image->ftf}first{/if}
			{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>

		  </div>

		{/foreach}
		
		<br style="clear:left;"/>&nbsp;
		

	{else}

		<p>We have no images for {getamap gridref=$gridref text=$gridref title="OS Get-a-Map for $gridref"} yet,
		
		{if $nearest_distance}
			</p><ul><li>The closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away.<br/><br/></li>
		{else}
			and have no pictures for any grid square within 100km either!</p>
			<ul>
		{/if}
		<li>You can also <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}"><b>search</b> for nearby images</a>.</li>
		<li>		  
		{if $discuss}
			There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
			<a href="/discuss/index.php?gridref={$gridref}"><b>discussion</b> about {$gridref}</a>. (preview on the left)
			
		{else}
			{if $user->registered} 
				<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a <b>discussion</b> about {$gridref}</a>.
			{else}
				<a href="/login.php">login</a> to start a <b>discussion</b> about {$gridref}.
			{/if}
		{/if}</li>
		<li><a href="/mapbrowse.php?t={$map_token}">Geograph <b>map</b> for {$gridref}</a>. (<a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}">print check sheet</a>)</li>
		<li>Or <a href="/submit.php?gridreference={$gridrefraw}"><b>submit</b> your own picture of {$gridref}</a>.</li>
		
		</ul>

	{/if}
   
   
{/if}

{/dynamic}

{include file="_std_end_dynamic.tpl"}
