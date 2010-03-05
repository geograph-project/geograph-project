{assign var="page_title" value="`$hectad` :: Hectad"}
{include file="_std_begin.tpl"}

<h2><a href="/gridref/{$myriad}">{$myriad}</a> :: {$hectad} :: Hectad</h2>
 

    <form method="get" action="/hectad.php">
	<div class="interestBox"><label for="hectad">Hectad</label> <input type="text" name="hectad" id="hectad" value="{$hectad|escape:'html'}" size="4" maxlength="5"/>
	<input type="submit" value="Go"> 
	</div>
    </form>
    
 	{if $overview}
		<div style="float:right; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
		</div>
	{/if}
	
	{if $overview2}
		{assign var=overview value=$overview2}
		{assign var=overview_token value=$overview_token2}
		{assign var=marker value=$marker2}
		
		<div style="float:right; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
		</div>
	{/if}
	
<h3>Stats</h3>

<ul>
	<li>Number of land squares: <b>{$landsquares|thousends}</b> <small>(out of a 100 possible)</small></li>
	<li>First Geographs: <b>{$geosquares|thousends}</b> (i.e. number of squares with Geographs){if $ftfusers}<ul>
		<li>Contributors:  <b>{$ftfusers|thousends}</b></li>
		<li>First: <b>{$first_submitted|date_format:"%A, %e %B, %Y"}</b></li>
		<li>Last: <b>{$last_submitted|date_format:"%A, %e %B, %Y"}</b></li>
	</ul>{/if}</li>
	<li>Number of geographs: <b>{$geographs|thousends}</b> </li>
	<li>Number of images: <b>{$images|thousends}</b> {if $users}<ul>
                <li>Contributors:  <b>{$users|thousends}</b></li>
        </ul>{/if}</li>
</ul>
<div style="font-size:0.8em; color:gray;">Last updated: {$updated|date_format:"%e %B, %H:%M"}</div>

<h3>Links</h3>

{if $hectads}
Nearby hectads: 
{foreach from=$hectads key=id item=obj}
{if $obj.hectad == $hectad} 
<b>{$hectad}</b>,
{else}
<a title="View Page for {$obj.hectad}, completed {$obj.last_submitted}" href="/gridref/{$obj.hectad}">{$obj.hectad}</a>,
{/if}
{/foreach} <a href="/statistics/fully_geographed.php?myriad={$myriad}">More</a>
{/if}

<ul class="explore">
	
	<li style="list-style-image: url('http://{$static_host}/img/links/20/map.png');"><a title="View map for {$hectad}" href="/mapbrowse.php?t={$map_token}">View Geograph <b>Coverage Map</b></a> or <img src="http://{$static_host}/img/links/20/checksheet.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}">View a <b>printable check sheet</b></a></li>
	
	{if $reference_index == 1}<li style="list-style-image: url('http://{$static_host}/img/links/20/mapper.png');"><a href="/mapper/?t={$map_token}">Open <b>OS Grid Squares Map</b></a></b> {/if}
	
	{if $largemap_token}
	<li style="list-style-image: url('http://{$static_host}/img/links/20/mosaic.png');"><a title="View Mosaic for {$obj.hectad}" href="/maplarge.php?t={$largemap_token}">Have a look at a <b>Large Mosaic</b>/map</a> (includes First Geograph statistics)</li>
	{/if}
	
	<li style="list-style-image: url('http://{$static_host}/img/links/20/search.png');"><form method="get" action="/search.php">
		<b>Search within images in this square</b>:<br/> 
		<div class="interestBox" style="width:400px">
		<label for="fq">Keywords</label>: <input type="text" name="searchtext[]" id="fq" size="30"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/><input type="hidden" name="searchtext[]" value="{$hectad}"/>
		<input type="submit" value="Search"/><br/>
		<input type="hidden" name="location" value="{$gridref}"/>
		<input type="checkbox" name="searchtext[]" value="ftf:1" id="ftf"/><label for="ftf">First Geographs only</label><br/>
		<input type="checkbox" name="displayclass" value="thumbs" id="dc"/><label for="dc">Show thumbnails only</label>
		<input type="hidden" name="do" value="1"/>
		</div>
	</form></li>
			
	<li><a href="/search.php?text=hectad:{$hectad}">View <b>images in {$hectad}</b></a> (<a href="/search.php?text=hectad:{$hectad}+ftf:1">First Geographs only</a>)</li>
	
	<li style="list-style-image: url('http://{$static_host}/img/geotag_16.png');"><a href="/gridref/{$hectad}/links"><b>Location Links for {$hectad}</b></a></a>

	<li style="list-style-image: url('http://{$static_host}/img/links/20/place.png');"><a href="/finder/places.php?q={$myriad}+{$hectad}">Find <b>Places in {$hectad}</b></a> (can then find images of that place)</li>

	{if $largemap_token}
		<li style="list-style-image: url('http://{$static_host}/img/links/20/checksheet.png');"><a href="/statistics/fully_geographed.php?myriad={$myriad}"><b>Compare</b> to other hectads in {$myriad}</a></li>
	{else}
		<li style="list-style-image: url('http://{$static_host}/img/links/20/checksheet.png');"><a href="/statistics/most_geographed.php?myriad={$myriad}"><b>Compare</b> to other hectads in {$myriad}</a></li>
	{/if}
	
</ul>	
<ul class="explore">

	<li>{external href="http://www.nearby.org.uk/geograph/flamenco-redir.php?grid_reference=`$hectad`" text="Explore a sample of `$hectad` images in the new <b>Multi-Purpose Viewer</b>"} <sup style="color:red">Experimental</sup></li>

	<li style="list-style-image: url('http://{$static_host}/img/links/20/words.png');"><b><a href="/sitemap/clusters/{$myriad}/{$hectad}.html">Common Clusters</a> and <a href="/sitemap/terms/{$myriad}/{$hectad}.html">Common Terms</a></b> used in {$hectad} <sup style="color:red">infrequently updated</sup></li>
	
</ul>





<hr/>

<p><small>Note: this page is very basic - we will probably add more later.</small></p>


{include file="_std_end.tpl"}
