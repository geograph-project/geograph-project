{assign var="page_title" value="`$hectad` :: Hectad"}
{include file="_std_begin.tpl"}

<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
{/literal}
</style>


<h2>Hectad {$hectad}</h2>
<p>Hectads are 10 &times; 10 km blocks consisting of 100 gridquares. More information on the terms used to describe the Ordnance Survey grid system can be found on the <a href="/help/squares">about squares page</a>.





<div class="twocolsetup">
 
<div class="twocolumn">
<h3>Coverage</h3>


<h4 style="margin-bottom:0; margin-top:2px;">Explore hectad {$hectad}</h4>

<ul class="buttonbar">


<li><details>
    <summary>Search images in {$hectad}</summary>
    <div>
    <b>One image per</b><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=agridsquare&amp;orderby=grid_reference&amp;do=1">Grid square</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=auser_id&amp;do=1">Contributor</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=takendays&amp;orderby=imagetaken&amp;do=1">Day taken</a><br>

    <b>First Geographs</b><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=full&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Full details</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=thumbs&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=thumbsmore&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails + links</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=bigger&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails - bigger</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=grid&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails grid</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=slide&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Slideshow</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=map&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Map</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=black&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Georiver</a><br>

    <b>Most recent first</b><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Full details</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbs&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbsmore&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails + links</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=bigger&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=grid&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails grid</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=slide&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Slideshow</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=map&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Map</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=black&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Georiver</a><br>

    <b>Oldest first</b><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;orderby=submitted&amp;do=1">Full details</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=map&amp;orderby=submitted&amp;do=1">Map</a><br>
        <a href="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</a><br>
    </div>
</details>
</li>



<li><details>
     <summary>Geograph coverage maps</summary>
     <div>
				<a href="/mapper/combined.php?t={$map_token}">Interactive coverage map</a><br>
        {if $largemap_token}<a href="/maplarge.php?t={$largemap_token}">Photo mosaic</a><br>{/if}
        <a href="/browser/#!/hectad+%22{$hectad}%22">Browser map</a><br>
        <a href="https://www.geograph.org/leaflet/all.php#12/{$lat}/{$long}">All Geograph projects map</a><br>
				<a href="/mapsheet.php?t={$map_token}"">Printable Checksheet</a><br>
        {if $user}<a href="/mapsheet.php?t={$map_token}&mine=1">Printable Checksheet (personalised)</a><br>{/if}
        <a href="/mapbrowse.php?t={$map_token}">Original coverage maps</a><br>
     </div>
</details></li>



<li><details>
	<summary>Mapping links</summary>
	<div>
    <b>Google Maps</b><br>
	<a href="https://www.google.co.uk/maps/search/{$lat},{$long}/">Google maps</a><br>
        <a href="https://www.google.co.uk/maps/dir/?api=1&amp;destination={$lat},{$long}">Google maps - Navigate to Area</a><br>

    <b>OpenStreetMap</b><br>
        <a href="http://www.openstreetmap.org/?mlat={$lat}&amp;mlon={$long}&amp;zoom=14">OpenStreetMaps</a><br>
        <a href="https://www.opencyclemap.org/?zoom=14&amp;lat={$lat}&amp;lon={$long}">OpenCycleMap</a><br>
        <a href="https://opentopomap.org/#map=14/{$lat}/{$long}">OpenTopoMap</a><br>
        <a href="https://map.openseamap.org/?zoom=14&amp;lat={$lat}&amp;lon={$long}">OpenSeaMap</a><br>    

    <b>Other</b><br>
        <a href="https://maps.nls.uk/geo/find/marker/#zoom=13&lat={$lat}&lon={$long}&f=1&z=1&marker={$lat},{$long}">National Library of Scotland</a><br>
        <a href="http://wtp2.appspot.com/wheresthepath.htm?lat={$lat}&amp;lon={$long}">Where's the path</a><br>
        <a href="https://www.bing.com/maps?v=2&amp;cp={$lat}~{$long}&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1">Bing maps</a><br>
        <a href="https://explore.osmaps.com/?lat={$lat}&amp;lon={$long}&amp;zoom=14">Ordnance Survey</a><br>    
        </div>
</details></li>

<li><a title="open geograph browser - search/browse and map in one" href="/browser/#!/hectad+%22{$hectad}%22">Open {$hectad} in Geograph Browser</a></li>


<li><a href="/finder/places.php?q={$myriad}+{$hectad}">Find places in {$hectad}</a></li>





</ul>



<h4 style="margin-bottom:0; margin-top:2px;">Breakdowns</h4>

<ul class="buttonbar">
<li><a href="/statistics/groupby.php?groupby=auser_id&amp;filter%5Bahectad%5D={$hectad}&amp;distinct=agridsquare">List of contributors</a></li>
<li><a href="/statistics/groupby.php?groupby=takendays_year&amp;filter%5Bahectad%5D={$hectad}&amp;distinct=agridsquare">Year Breakdown</a></li>		
<li><a href="/finder/groups.php?q=hectad:{$hectad}&group=group_ids">Common Clusters</a></li>
<li><a href="/finder/groups.php?q=hectad:{$hectad}&group=term_ids">Common Terms</a></li>
<li><a href="/finder/bytag.php?q=hectad:{$hectad}">Most Used Tags</a></li>
</ul>



{if $hectads}
<h4 style="margin-bottom:0; margin-top:2px;">Nearby hectads</h4>

<ul class="buttonbar">
{foreach from=$hectads key=id item=obj}
{if $obj.hectad == $hectad} 
<li>{$hectad}</li>
{else}
<li><a title="View Page for {$obj.hectad}{if $obj.completed}, completed {$obj.last_submitted}{/if}" href="/gridref/{$obj.hectad}">{$obj.hectad}</a></li>
{/if}
{/foreach}<li><a href="/statistics/fully_geographed.php?myriad={$myriad}">More</a></li>
</ul>
{/if}

<h4 style="margin-bottom:0; margin-top:2px;">Myriad {$myriad}</h4>
<ul class="buttonbar">

<li><a href="/gridref/{$myriad}">View myriad page for {$myriad}</a></li>

{if $largemap_token}
		<li><a href="/statistics/fully_geographed.php?myriad={$myriad}">Compare with other hectads in {$myriad}</a></li>
{else}
		<li><a href="/statistics/most_geographed.php?myriad={$myriad}">Compare with other hectads in {$myriad}</a></li>
{/if}


</ul>



	



</div>
  
<div class="twocolumn">
<h3>Maps</h3>

<div style="width:100%; text-align:center;">
	{if $overview}
		<div style="display: inline-block; vertical-align: middle; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
		</div>
	{/if}
	
	{if $overview2}
		{assign var=overview value=$overview2}
		{assign var=overview_token value=$overview_token2}
		{assign var=marker value=$marker2}
		
		<div style="display: inline-block; vertical-align: middle; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
		</div>
	{/if}

</div>
</div>


</div>
<br style="clear:both"/>




<div class="twocolsetup">
<div class="twocolumn">
<h3>Statistics</h3>

<ul>
	<li>Number of land squares: <b>{$landsquares|thousends}</b> <small>(out of a possible 100)</small></li>
	<li>First Geographs: <b>{$geosquares|thousends}</b> (i.e. number of squares with Geographs){if $ftfusers}<ul>
		<li>Contributors:  <b>{$ftfusers|thousends}</b></li>
		<li>First: <b>{$first_submitted|date_format:"%A, %e %B, %Y"}</b></li>
		<li>Last: <b>{$last_submitted|date_format:"%A, %e %B, %Y"}</b></li>
	</ul>{/if}</li>
	<li>Number of Geographs: <b>{$geographs|thousends}</b> </li>
	<li>Number of images: <b>{$images|thousends}</b> {if $users}<ul>
                <li>Contributors:  <b>{$users|thousends}</b></li>
        </ul>{/if}</li>
</ul>




</div>



<div class="twocolumn">
<h3>Most Used Tags</h3>
{if $tags}

	<ul class="buttonbar">
	{foreach from=$tags item=item}
		<li><a title="{$item->count} images" href="/search.php?searchtext=[{if $item->prefix}{$item->prefix|escape:'url'}:{/if}{$item->tag|escape:'url'}]+hectad:{$hectad}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" class="taglink">{$item->tag|escape:'html'}</a></li>
	{/foreach}
	</ul>
{/if}
</div>


</div>



<br style="clear:both"/>
<br/>

{include file="_std_end.tpl"}
