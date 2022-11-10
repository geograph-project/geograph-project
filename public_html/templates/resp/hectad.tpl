{assign var="page_title" value="`$hectad` :: Hectad"}
{include file="_std_begin.tpl"}

<style>
{literal}
*{
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


<li>
<select onchange="window.location.href=this.value" style="width:300px">
    <option>Search images in {$hectad}</option>
    <optgroup label="One image per">
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=agridsquare&amp;orderby=grid_reference&amp;do=1">Grid square</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=auser_id&amp;do=1">Contributor</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;groupby=takendays&amp;orderby=imagetaken&amp;do=1">Day taken</option>
    </optgroup>
        <optgroup label="First Geographs">
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=full&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=thumbs&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=thumbsmore&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=bigger&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=grid&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=slide&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=map&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Map</option>
        <option value="/search.php?searchtext=hectad:{$hectad}+ftf:1&amp;displayclass=black&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Most recent first">
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbs&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbsmore&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=bigger&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=grid&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=slide&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=map&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=black&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?searchtext=hectad:{$hectad}&amp;displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
</select>
</li>



<li><select onchange="window.location.href=this.value" style="width:300px">
				<option value="">Geograph coverage maps</option>
				<option value="/mapper/combined.php?t={$map_token}">Interactive coverage map</option>
        {if $largemap_token}<option value="/maplarge.php?t={$largemap_token}">Photo mosaic</option>{/if}
        <option value="/mapper/combined.php?mobile=1&#35;12/{$lat}/{$long}">Mobile coverage map</option>
        <option value="/browser/#!/hectad+%22{$hectad}%22">Browser map</option>
        <option value="/leaflet/all.php#12/{$lat}/{$long}">All Geograph projects map</option>
				<option value="/mapsheet.php?t={$map_token}"">Printable Checksheet</option>
        {if $user}<option value="/mapsheet.php?t={$map_token}&mine=1">Printable Checksheet (personalised)</option>{/if}
        <option value="/mapbrowse.php?t={$map_token}">Original coverage maps</option>
</select></li>



<li><select onchange="window.location.href=this.value" style="width:300px">
				<option value="">Mapping links</option>
				<option value="https://www.google.com/maps/@?api=1&map_action=map&center={$lat}%2C{$long}&zoom=12">Google maps</option>
        <option value="http://www.openstreetmap.org/?mlat={$lat}&amp;mlon={$long}&amp;zoom=12">OpenStreetMaps</option>
        <option value="https://maps.nls.uk/geo/find/marker/#zoom=12&lat={$lat}&lon={$long}&f=1&z=1&marker={$lat},{$long}">National Library of Scotland</option>
        <option value="http://wtp2.appspot.com/wheresthepath.htm?lat={$lat}&amp;lon={$long}">Where's the path</option>
        <option value="https://www.bing.com/maps?v=2&amp;cp={$lat}~{$long}&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1">Bing maps</option>
</select></li>

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
