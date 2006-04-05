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
<small>Enter a Placename, Postcode, Grid Reference, or a text search (<a href="/help/search">help</a>)</small></div>
</form>
{/dynamic} 
<ul style="margin-left:0;padding:0 0 0 1em;">

<li>Here are a couple of example searches:
<div style="float:left; width:50%; position:relative">
<ul style="margin-left:0;padding:0 0 0 1em;">
<li><a href="search.php?i=1522" title="Show the most recent submissions">Recent Submissions</a></li>
<li><a href="search.php?displayclass=thumbs&amp;do=1" title="Show a selection of random thumbnails">Random (Thumbnails)</a></li>
{dynamic}
{if $user->registered}
<li><a href="search.php?u={$user->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1" title="show your recent photos">Your Photos (recent first)</a></li>
{else}
<li><a href="search.php?displayclass=text&amp;orderby=submitted&amp;reverse_order_ind=1&amp;resultsperpage=50&amp;do=1" title="recent photo without thumbnails">Recent (Text only)</a></li>
{/if}
{/dynamic}
<li><a href="search.php?taken_endYear=1980&amp;do=1" title="View Historic Pictures">Taken Before 1980</a></li>
<li><a href="search.php?i=342" title="Images in TQ Grid Square">Gridsquare TQ</a></li>
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
	<ul style="margin-left:0;padding:0 0 0 1em;">
	{foreach from=$recentsearchs key=id item=obj}
	<li>{if $obj.favorite == 'Y'}<b>{/if}{if $obj.searchclass == 'Special'}<i>{/if}<a href="search.php?i={$id}" title="Re-Run search for images{$obj.searchdesc}{if $obj.use_timestamp != '0000-00-00 00:00:00'}, last used {$obj.use_timestamp}{/if}">{$obj.searchdesc|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'</a><small>$1</small>'}</a>{if !is_null($obj.count)} [{$obj.count}]{/if}{if $obj.searchclass == 'Special'}</i>{/if}{if $obj.favorite == 'Y'}</b> <a href="/search.php?i={$id}&amp;fav=0" title="cancel favorite"><img src="/templates/basic/img/star-off.png" width="14" height="14" alt="cancel favorite"></a>{else} <a href="/search.php?i={$id}&amp;fav=1" title="make favorite"><img src="/templates/basic/img/star-on.png" width="14" height="14" alt="make favorite"></a>{/if}</li>
	{/foreach}
	{if !$more}
	<li><a href="search.php?more=1" title="View More of your recent searches" rel="nofollow"><i>view more...</i></a></li>
	{/if}
	</ul><br/>
	</li>
	{/if}
{else}
	<li><i><a href="/login.php">Login</a> to see your recent and favorite searches.</i><br/><br/></li>
{/if}
{/dynamic} 
<li>If you are unable to find your location in our search above try {getamap} and return here to enter the <acronym style="border-bottom: red dotted 1pt; text-decoration: none;" title="look for something like 'Grid reference at centre - NO 255 075 GB Grid">grid reference</acronym>.<br/><br/></li> 

</ul>
<div style="padding:5px;background:#dddddd;position:relative">
<ul style="margin-left:0;padding:0 0 0 1em;">

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
