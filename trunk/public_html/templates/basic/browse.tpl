{assign var="page_title" value="Browse"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

    <h2>Browse</h2>

{if !$showresult}     
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

<div style="text-align:center; font-size: 0.8em;">		  
{if $discuss}
	There is {$totalcomments} post{if $totalcomments ne 1}s{/if} in a 
	<a href="/discuss/index.php?gridref={$gridref}">discussion on {$gridref}</a> (preview on the left)
	
{else}
	{if $user->registered} 
		<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a discussion on {$gridref}</a>
	{else}
		<a href="/login.php">login</a> to start a discussion on {$gridref} 
	{/if}
{/if}<br/><br/>
</div>


	{if $imagecount}

		<p><b>We have 
		{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
		for {getamap gridref=$gridref text=$gridref title="OS Get-a-Map for $gridref"}</b> - click for larger version</p>
		
		{foreach from=$images item=image}
		
		  <div style="float:left;" class="photo33"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
		  <div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
		  
		  {if $is_admin}
			  <div class="caption">status: {$image->moderation_status}
			  {if $image->ftf}(FTF){/if}
			  </div>
		  {/if}
		  
		  
		  </div>
		  
		  
		{/foreach}
		
		<br style="clear:left;"/>&nbsp;
		

	{else}

		<p>We have no images for {getamap gridref=$gridref text=$gridref title="OS Get-a-Map for $gridref"}, 
		
		{if $nearest_distance}
			the closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/gridref/{$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away. You can also <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}">search for nearby images</a>.
		{else}
			and have no pictures for any grid square within 100km either! <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}">search for nearby images</a>.
		{/if}
		
		</p>

	{/if}
   
   
{/if}

{/dynamic}

{include file="_std_end.tpl"}
