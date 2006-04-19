{assign var="page_title" value="Explore Geograph Themes"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">

ul.explore li {
	border:1px dotted #dddddd;
	padding:10px;
}

ul.explore {
	list-style-image:url('/templates/basic/img/cameraicon.gif');
}

ul.explore li ul {
	list-style-type:dot;
	list-style-image:none;
}

ul.explore li ul li {
	border:none;
	padding:2px;
}

</style>
{/literal}
<h2>Themed Browsing</h2>

<h3>Go anywhere...</h3>
<ul class="explore">

	<li><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref">Browse</label> by <b>Grid Reference</b>: 
	<input id="gridref" type="text" name="gridref" value="ST8751" size="15" style="color:gray" onfocus="{literal}if (this.value=='ST8751') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo" type="submit" value="Go"/>
	</form></li>

	<li><a href="/mapbrowse.php">Explore a Zoomable <b>Map</b></a>.</li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="searchterm"><b>Search</b> by keyword, place, postcode or contributor</label>: <br/>
	<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
	<input id="searchgo" type="submit" name="go" value="Find"/> [<a href="/help/search">help</a>, <a href="/search.php?form=advanced">advanced search</a>]
	</form></li>

</ul>
<h3>Outstanding archivement...</h3>
<ul class="explore">

	<li><a href="/statistics/fully_geographed.php">List <b>Fully Geographed Hectads</b></a> <small>(10 x 10 Gridsquares)</small> - include Image Mosaics!</li>

</ul>
<h3>Specialist locations...</h3>
<ul class="explore">

	<li><a href="/explore/routes.php">Follow <b>National Routes</b></a>. NEW!</li>

	<li>View Photographs at <b>Centre Points</b>: <ul>
		<li><a href="/explore/counties.php">Ceremonial (Pre 1994) Counties</a></li>
		<li><a href="/explore/counties.php?type=pre74">Pre 1974 Counties</a></li>
		<li><a href="/explore/counties.php?type=capital">(Irish) County Capitals</a></li>
		<li><a href="/explore/cities.php">Cities and Large Towns</a></li>
	</ul></li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="county_id">Search by centre of <b>Ceremonial County</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="county_id" id="county_id" size="1" class="searchinput" onchange="onlyone(this)" onblur="onlyone(this)"/> 
	  <option value=""> </option> 
		{html_options options=$countylist selected=$county_id}
	</select> <input id="searchgo" type="submit" name="go" value="Find"/>
	</form></li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="topic_id">Search by <b>Discussion Topic</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
	  <option value=""> </option> 
		{html_options options=$topiclist selected=$topic_id}
	</select> <input id="searchgo" type="submit" name="go" value="Find"/>
	</form></li>

</ul>
<h3>Breakdowns...</h3>
<ul class="explore">

	<li><a href="/statistics/breakdown.php?by=class">Breakdown by <b>Category</b></a>, (soon to be replaced by tags).</li>

	<li><a href="/explore/wordnet.php">By Popular <b>Title Words</b></a> in the last 7 days and all time.<ul>
		<li><a href="/explore/wordnet.php">Cloud Style</a> or <a href="/explore/wordnet.php?t=1">List Style</a></li>
	</ul></li>

	<li><a href="/explore/calendar.php">Geograph <b>Calendar</b></a>, view images by date taken.</li>

	<li><a href="/help/sitemap#users">Explore by <b>Contributor</b></a>.</li>

</ul>

<p style="background-color:#cccccc;padding:10px;">Explore using <a href="/help/sitemap#software">external <b>Software</b></a>.</p>

{include file="_std_end.tpl"}
