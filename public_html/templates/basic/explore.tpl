{assign var="page_title" value="Explore Geograph Themes"}
{assign var="meta_description" value="We have many interesting ways to explore geograph images here..."}
{include file="_std_begin.tpl"}

	<div style="padding:5px;background:#dddddd;position:relative; float:right;"><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref1">Jump</label> to <b>Grid Reference</b>: <br/>
	<input id="gridref1" type="text" name="gridref" value="ST8751" size="15" style="color:gray" onfocus="{literal}if (this.value=='ST8751') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo1" type="submit" value="Go"/>
	</form></div>

<h2>Exploring the photographs</h2>


<h3>Outstanding achievement...</h3>
<ul class="explore">

	<li><a href="/statistics/fully_geographed.php">List <b>Fully Geographed Hectads</b></a> <small>(10 x 10 Gridsquares)</small> - include Image Mosaics!</li>

</ul>
<h3>Selections...</h3>
<ul class="explore">

	<li><a href="/explore/searches.php"><b>Featured Searches</b></a> - hand picked example searches</li>

	<li><a name="counties"></a>View Photographs at <b>Centre Points</b>: <ul>
		<li><a href="/explore/counties.php?type=modern">Modern Administrative Counties</a>,
		<a href="/explore/counties.php">Ceremonial (Pre 1994) Counties</a>,
		<a href="/explore/counties.php?type=pre74">Historic (Pre 1974) Counties</a></li>
		<li><a href="/explore/counties.php?type=capital">(Irish) County Capitals</a>, <a href="/explore/cities.php">Cities and Large Towns</a></li>
	</ul></li>

	<li><a href="/gallery/"><b>Galleries</b></a> - hand picked images on varies themes</li>

	<li><a href="/explore/places/" title="Explore by Place">Explore the <b>placename gazetteer</b></a> <sup style="color:red">New!</sup></li>

</ul>
<h3>Breakdowns...</h3>
<ul class="explore">

	<li><a href="/explore/calendar.php">Geograph <b>Calendar</b></a>, view images by date taken.</li>

	<li><a href="/statistics/breakdown.php?by=class">Breakdown by <b>Category</b></a>, (soon to be replaced by tags).</li>

	<li><a href="/help/sitemap#users">Explore by <b>Contributor</b></a>.</li>

</ul>
<h3>Go anywhere...</h3>
<ul class="explore">

	<li><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref">Browse</label> by <b>Grid Reference</b>: 
	<input id="gridref" type="text" name="gridref" value="ST8751" size="15" style="color:gray" onfocus="{literal}if (this.value=='ST8751') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo" type="submit" value="Go"/>
	</form></li>

	<li><a href="/stuff/browse-random.php">Jump to a <b>Random</b> Gridsquare</a>.</li>

	<li><a href="/mapbrowse.php">Explore a Zoomable <b>Map</b></a>.</li>

	<li><a href="/mapper/">View a <b>Draggable Map</b> checksheet</a>. (use the Grid Reference box to jump to your area of interest)</li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="searchterm"><b>Search</b> by keyword, place, postcode or contributor</label>: <br/>
	<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
	<input id="searchgo" type="submit" name="go" value="Find"/> [<a href="/help/search">help</a>, <a href="/search.php?form=advanced">advanced search</a>]
	</form></li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="county_id">Search by centre of <b>Ceremonial County</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="county_id" id="county_id" size="1" class="searchinput" onchange="onlyone(this)" onblur="onlyone(this)"/> 
	  <option value=""> </option> 
		{html_options options=$countylist selected=$county_id}
	</select> <input id="searchgo" type="submit" name="go" value="Find"/>
	</form></li>

{if $enable_forums}
	<li><form method="get" action="/search.php" style="display:inline">
	<label for="topic_id">Search by <b>Discussion Topic</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
	  <option value=""> </option> 
		{html_options options=$topiclist selected=$topic_id}
	</select> <input id="searchgo" type="submit" name="go" value="Find"/>
	</form></li>
{/if}

</ul>

<p style="background-color:#cccccc;padding:10px;">Explore using <a href="/help/sitemap#software">external <b>Software</b></a>.</p>

{include file="_std_end.tpl"}
