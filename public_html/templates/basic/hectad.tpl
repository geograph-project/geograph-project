{assign var="page_title" value="`$hectad` :: Hectad"}
{include file="_std_begin.tpl"}

<h2><a href="/gridref/{$myriad}">{$myriad}</a> :: {$hectad} :: Hectad</h2>
 

    <form method="get" action="/hectad.php">
	<div class="interestBox"><label for="hectad">Hectad</label> <input type="text" name="hectad" id="hectad" value="{$hectad|escape:'html'}" size="4" maxlength="5"/>
	<input type="submit" value="Go"> 
	</div>
    </form>
    
    
<h3>Stats</h3>

<ul>
	<li>Number of land squares: <b>{$row.landsquares|thousends}</b> <small>(out of a 100 possible)</small></li>
	<li>First Geographs: <b>{$row.geosquares|thousends}</b> (i.e. number of squares with Geographs){if $row.users}<ul>
		<li>Contributors:  <b>{$row.users|thousends}</b></li>
		<li>First: <b>{$row.first_submitted|date_format:"%A, %e %B, %Y"}</b></li>
		<li>Last: <b>{$row.last_submitted|date_format:"%A, %e %B, %Y"}</b></li>
	</ul>{/if}</li>
	<li>Number of geographs: <b>{$row.geographs|thousends}</b> </li>
	<li>Number of images: <b>{$row.images|thousends}</b> </li>
</ul>

<h3>Links</h3>
<ul class="explore">
	
	<li><a title="View map for {$row.hectad}" href="/mapbrowse.php?t={$row.map_token}">View Geograph <b>Coverage Map</b></a></li>
	
	{if $row.largemap_token}
	<li><a title="View Mosaic for {$obj.hectad}" href="/maplarge.php?t={$row.largemap_token}">Have a look at a <b>Large Mosaic</b>/map</a> (includes First Geograph statistics)</li>
	{/if}
	
	<li><form method="get" action="/search.php">
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

	{if $row.largemap_token}
		<li><a href="/statistics/fully_geographed.php?myriad={$myriad}"><b>Compare</b> to other hectads in {$myriad}</a></li>
	{else}
		<li><a href="/statistics/most_geographed.php?myriad={$myriad}"><b>Compare</b> to other hectads in {$myriad}</a></li>
	{/if}
	
</ul>	
<ul class="explore">

	<li>{external href="http://www.nearby.org.uk/geograph/flamenco-redir.php?grid_reference=`$hectad`" text="Explore a sample of `$hectad` images in the new <b>Multi-Purpose Viewer</b>"} <sup style="color:red">Experimental</sup></li>

	<li><b><a href="/sitemap/clusters/{$myriad}/{$hectad}.html">Common Clusters</a> and <a href="/sitemap/terms/{$myriad}/{$hectad}.html">Common Terms</a></b> used in {$hectad} <sup style="color:red">infrequently updated</sup></li>
	
</ul>





<hr/>

<p><small>Note: this page is very basic - we will probably add more later.</small></p>


{include file="_std_end.tpl"}
