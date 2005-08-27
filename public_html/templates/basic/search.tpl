{assign var="page_title" value="Search"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Search for Photographs</h2>

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}

<form method="get" action="/search.php">
<div style="padding:5px;background:#dddddd;position:relative"><label for="searchterm">Search</label> 
<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
<input id="searchgo" type="submit" name="go" value="Find"/> [<a href="/search.php?form=advanced">advanced search</a>]

<br/><br/>
<small>Enter a Placename, Postcode, Grid Reference, or a text search</small></div>
</form>
{/dynamic} 
<ul>

<li>Here are a couple of example searches:
<div style="float:left; width:50%; position:relative">
<ul>
<li><a href="search.php?orderby=submitted&amp;reverse_order_ind=1&amp;do=1" title="Show the most recent submissions">Recent Submissions</a></li>
<li><a href="search.php?displayclass=thumbs&amp;do=1" title="Show a selection of random thumbnails">Random (Thumbnails)</a></li>
{dynamic}
{if $user->registered}
<li><a href="search.php?u={$user->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1" title="show your recent photos">Your Photos (recent first)</a></li>
{else}
<li><a href="search.php?displayclass=text&amp;orderby=submitted&amp;reverse_order_ind=1&amp;resultsperpage=50&amp;do=1" title="recent photo without thumbnails">Recent (Text only)</a></li>
{/if}
{/dynamic}
<li><a href="search.php?taken_endYear=1980&amp;do=1" title="View Historic Pictures">Taken Before 1980</a></li>
<li><a href="search.php?gridsquare=TQ" title="Images in TQ Grid Square">Gridsquare TQ</a></li>
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
{if $user->registered && $recentsearchs}
<li>And a list of your recent searches:
<ul>
{foreach from=$recentsearchs key=id item=obj}
<li><a href="search.php?i={$id}" title="Re-Run search for images{$obj}">{$obj|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'<small>$1</small>'}</a></li>
{/foreach}
{if !$more}
<li><a href="search.php?more=1" title="View More of your recent searches"><i>view more...</i></a></li>
{/if}
</ul><br/>
</li>
{/if}
{/dynamic} 
<li>If you are unable to find your location in our search above try {getamap} and return here to enter the <acronym style="border-bottom: red dotted 1pt; text-decoration: none;" title="look for something like 'Grid reference at centre - NO 255 075 GB Grid">grid reference</acronym>.<br/><br/></li> 

</ul>
<div style="padding:5px;background:#dddddd;position:relative">
<ul>

<li><b>If you have a WGS84 latitude &amp; longitude coordinate</b>
		(e.g. from a GPS receiver, or from multimap site), then see our 
		<a href="/latlong.php">Lat/Long to Grid Reference Convertor</a><br/><br/></li>
		

<li>A <a title="Photograph Listing" href="/list.php">complete listing of all photographs</a> is available.<br/><br/></li> 

<li>You may prefer to browse images on a <a title="Geograph Map Browser" href="/mapbrowse.php">Map of the British Isles</a>.<br/><br/></li> 


<li>Or you can browse a <a title="choose a photograph" href="browse.php">particular grid square</a>.<br/><br/></li>


<li>Registered users can also <a href="/discuss/index.php?action=search">search the forum</a>.</li>

</ul>
</div>
   
{include file="_std_end.tpl"}
