{assign var="page_title" value="Explore Geograph"}
{include file="_std_begin.tpl"}

<h2>Explore Images by various themes</h2>

<ul>
	<li><form method="get" action="/search.php">
	<label for="searchterm">Search</label> by keyword, place, postcode or grid reference: <br/>
	<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
	<input id="searchgo" type="submit" name="go" value="Find"/> [<a href="/help/search">help</a>, <a href="/search.php?form=advanced">advanced search</a>]
	</form></li>

	<li><form method="get" action="/browse.php">
	<label for="gridref">Browse</label> by Grid Reference: <br/>
	<input id="gridref" type="text" name="gridref" value="ST8751" size="15" style="color:gray" onfocus="{literal}if (this.value=='ST8751') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo" type="submit" value="Go"/>
	</form></li>



	<li>Explore <a href="/mapbrowse.php">a zoomeable <b>map</b></a>.<br/><br/></li>

	<li><b><a href="/statistics/fully_geographed.php">Fully Geographed Hectads</a></b> <small>(10 x 10 Gridsquares)</small> - include Image Mosaics!<br/><br/></li>



	<li><a href="/statistics/breakdown.php?by=class">Breakdown by <b>Category</b></a>, (soon to be replaced by tags).<br/><br/></li>

	<li>By <a href="/explore/wordnet.php">Popular <b>Title Words</b></a> in the last 7 days and all time.<ul>
		<li><a href="/explore/wordnet.php">Cloud Style</a></li>
		<li><a href="/explore/wordnet.php?t=1">List Style</a></li>
	</ul><br/></li>

	<li><a href="/explore/calendar.php">Geograph <b>Calendar</b></a>, view images by date taken.<br/><br/></li>

	<li>Explore  <a href="/help/sitemap#users">by contributor</a>.<br/><br/></li>

	<li><b>Centre Points</b>: (really just arbituary lists of Grid References!)<ul>
		<li><a href="/explore/counties.php">Ceremonial (Pre 1994) Counties</a></li>
		<li><a href="/explore/counties.php?type=pre74">Pre 1974 Counties</a></li>
		<li><a href="/explore/counties.php?type=capital">(Irish) County Capitals</a></li>
		<li><a href="/explore/cities.php">Cities and Large Towns</a></li>
	</ul><br/></li>

	<li>Explore using <a href="/help/sitemap#software">external <b>Software</b></a>.</li>
</ul>

{include file="_std_end.tpl"}
