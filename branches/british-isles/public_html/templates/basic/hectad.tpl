{assign var="page_title" value="`$hectad` :: Hectad"}
{include file="_std_begin.tpl"}

<h2>Hectad :: {$hectad}</h2>
 

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
	<li><a href="/search.php?text={$hectad}">Search <b>images in {$hectad}</b></a> (<a href="/search.php?text={$hectad}+ftf:1">First Geographs only</a>)</li>
	
	<li style="list-style-image: url('http://{$static_host}/img/geotag_16.png');"><a href="/gridref/{$hectad}/links"><b>Location Links</b> for {$hectad}</a></a>
	
	<li>{external href="http://www.nearby.org.uk/geograph/flamenco-redir.php?grid_reference=`$hectad`" text="View sample images in Multi-Purpose Viewer"} <sup style="color:red">Experimental</sup></li>
	
</ul>





<hr/>

<p><small>Note: this page is very basic - we will probably add more later.</small></p>


{include file="_std_end.tpl"}
