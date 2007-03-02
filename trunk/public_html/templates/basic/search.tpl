{assign var="page_title" value="Search"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Search for Photographs</h2>

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}

<form method="get" action="/search.php">
<div class="tabHolder">
	<span class="tabSelected">Simple Search</span>
	<a href="/search.php?form=advanced" class="tab">advanced search</a>
	<a href="/search.php?form=first" class="tab">first geographs</a>
	{if $user->registered}
		<a href="/search.php?form=check" class="tab">check submissions</a>
	{/if}	
</div>
<div style="position:relative;" class="interestBox">
	<div style="position:relative;float:left;width:240px;">
		<label for="searchq" style="line-height:1.8em"><b>Search For</b>:</label> <small>(<a href="/help/search">help &amp; tips</a>)</small><br/>
		&nbsp;&nbsp;&nbsp;<input id="searchq" type="text" name="q" value="{$searchtext|default:"(anything)"}" size="30" onfocus="if (this.value=='(anything)') this.value=''" onblur="if (this.value=='') this.value='(anything)'"/>
	</div>
	<div style="position:relative;float:left;width:430px;">
		<label for="searchlocation" style="line-height:1.8em">and/or a <b>Placename, Postcode, Grid Reference</b>:</label><br/>
		&nbsp;&nbsp;&nbsp;<i>near</i> <input id="searchlocation" type="text" name="location" value="{$searchlocation|default:"(anywhere)"}" size="30" onfocus="if (this.value=='(anywhere)') this.value=''" onblur="if (this.value=='') this.value='(anywhere)'"/>
		<input id="searchgo" type="submit" name="go" value="Search..."/>
	</div><span style="line-height:0.1em; font-size:0.1em"><br style="clear:both"/>&nbsp;</span></div>
</form>
{/dynamic} 
<ul style="margin-left:0;padding:0 0 0 1em;">

<li>Here are a couple of example searches:
<div style="float:left; width:50%; position:relative">
<ul style="margin-left:0;padding:0 0 0 1em;">
<li><a href="search.php?i=1522" title="Show the most recent submissions">Recent Submissions</a></li>
<li><a href="search.php?displayclass=thumbs&amp;do=1" title="Show a selection of random thumbnails">Random : Thumbnails</a></li>
{dynamic}
{if $user->registered}
<li><a href="search.php?u={$user->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1" title="show your recent photos">Your Photos, recent first</a></li>
{else}
<li><a href="search.php?displayclass=text&amp;orderby=submitted&amp;reverse_order_ind=1&amp;resultsperpage=50&amp;do=1" title="recent photo without thumbnails">Recent : Text Listing</a></li>
{/if}
{/dynamic}
<li><a href="search.php?taken_endYear=1980&amp;do=1" title="View Historic Pictures">Taken Before 1980</a></li>
<li><a href="search.php?i=199" title="Recent Images in Community Showcase">Community Showcase</a> (<a href="search.php?i=200" title="Images recently added to Community Showcase">more</a>)</li>
<li><a href="search.php?reference_index=2&amp;do=1" title="Irish Pictures">Pictures of Ireland</a></li>
</ul>
</div>
<div style="float:left; width:50%; position:relative">
<ul style="font-size:0.8em">
{foreach from=$imageclasslist key=id item=name}
<li><a href="search.php?imageclass={$id|escape:url}" title="Show images classed as {$id|escape:html}">{$name|escape:html}</a></li>
{/foreach}
<li><a href="/statistics/breakdown.php?by=class" title="Show Image Categories"><i>more categories...</i></a></li>

</ul>
</div><br style="clear:both;"/><br/><span style="font-size:0.8em">Tip: all these searches and more 
are available in the <a href="/search.php?form=advanced" 
title="customisable search options">advanced search</a></span><br/><br/>
</li>

{dynamic} 
{if $user->registered}
	{if $recentsearchs}
	<li>And a list of your recent searches:
	<ul style="margin-left:0;
	padding:0 0 0 0em; list-style-type:none">
	{foreach from=$recentsearchs key=id item=obj}
	<li>{if $obj.favorite == 'Y'}<a href="/search.php?i={$id}&amp;fav=0" title="remove favorite flag"><img src="/templates/basic/img/star-on.png" width="14" height="14" alt="remove favorite flag" onmouseover="this.src='/templates/basic/img/star-light.png'" onmouseout="this.src='/templates/basic/img/star-on.png'"></a> <b>{else}<a href="/search.php?i={$id}&amp;fav=1" title="make favorite"><img src="/templates/basic/img/star-light.png" width="14" height="14" alt="make favorite" onmouseover="this.src='/templates/basic/img/star-on.png'" onmouseout="this.src='/templates/basic/img/star-light.png'"></a> {/if}{if $obj.searchclass == 'Special'}<i>{/if}<a href="search.php?i={$id}" title="Re-Run search for images{$obj.searchdesc}{if $obj.use_timestamp != '0000-00-00 00:00:00'}, last used {$obj.use_timestamp}{/if} (Display: {$obj.displayclass})">{$obj.searchdesc|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'</a><small>$1</small>'}</a>{if !is_null($obj.count)} [{$obj.count}]{/if}{if $obj.searchclass == 'Special'}</i>{/if}{if $obj.favorite == 'Y'}</b>{/if}</li>
	{/foreach}
	{if !$more && !$all}
	<li><a href="search.php?more=1" title="View More of your recent searches" rel="nofollow"><i>view more...</i></a></li>
	{/if}
	</ul><br/>
	</li>
	{/if}
	<div style="position:relative; padding:10px; background-color:#eeeeee;">
	<small>Marked Images: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	</small><small style="font-size:0.6em; line-height:0.2em">TIP: Add images to your list by using the [Mark] buttons on the "full + links" and "thumbnails + links"<br/> search results display formats, and the full image page.<br/></small></div>
	<br/><br/>
{else}
	<li><i><a href="/login.php">Login</a> to see your recent and favorite searches.</i><br/><br/></li>
{/if}
{/dynamic} 
<li>If you are unable to find your location in our search above try {getamap} and return here to enter the <acronym style="border-bottom: red dotted 1pt; text-decoration: none;" title="look for something like 'Grid reference at centre - NO 255 075 GB Grid">grid reference</acronym>.<br/><br/></li> 

</ul>
<div class="interestBox">
<ul class="lessIndent">

<li>An (experimental) <a title="Special Partial GR Search Form" href="/search.php?form=first">First Geographs with Partial Grid References</a> form is available.<br/><br/></li> 

<li><b>If you have a WGS84 latitude &amp; longitude coordinate</b>
		(e.g. from a GPS receiver, or from multimap site), then see our 
		<a href="/latlong.php">Lat/Long to Grid Reference Convertor</a><br/><br/></li>
		

<li>A <a title="Photograph Listing" href="/list.php">complete listing of all photographs</a> is available.<br/><br/></li> 

<li>You may prefer to browse images on a <a title="Geograph Map Browser" href="/mapbrowse.php">Map of the British Isles</a>.<br/><br/></li> 


<li>Or you can browse a <a title="choose a photograph" href="browse.php">particular grid square</a>.<br/><br/></li>


<li>Registered users can also <a href="/discuss/index.php?action=search">search the forum</a>.</li>

</ul>
</div>
   
   <br/><br/>
<div class="copyright">Natural Language Query Parsing by {external href="http://developers.metacarta.com/" text="MetaCarta Web Services"}, Copyright MetaCarta 2006</div>
   
{include file="_std_end.tpl"}
