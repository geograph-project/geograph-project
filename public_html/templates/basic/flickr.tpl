{assign var="page_title" value="Flickr Search"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Search Geograph images on Flickr</h2>

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
<ul>
<li>
<form method="get" action="/flickr.php">
<div id="searchfield"><label for="searchterm">Search</label> 
<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
<input id="searchbutton" type="submit" name="go" value="Find"/> 
<br/><br/>
<small>Enter a Placename, Postcode, Grid Reference, or a text search</small></div>
</form>
</li>

<li>This page searches images on {external href="http://www.flickr.com/" text="flickr.com"}, 
which have been tagged with a grid reference, see the info at the {external href="http://www.flickr.com/groups/geograph/" text="Flickr Geograph Group"}.<br/><br/></li> 

<li>Hopefully this page will soon index images {external href="http://www.flickr.com/groups/mappingflickr/" text="geotagged"} with lat/long, and possibly even placenames!<br/><br/></li>

<li><i>The images found by this page are not endorsed nor controlled by Geograph.org.uk, please ensure you check the licence of the images before copying.</i><br/><br/></li>

<li>Also <a href="/search.php">search</a> images uploaded to Geograph.org.uk</p>

</ul>

{/dynamic}    
{include file="_std_end.tpl"}
