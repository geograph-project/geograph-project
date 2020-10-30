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

	<li><a href="/statistics/fully_geographed.php">List <b>Fully-Geographed hectads</b></a> <small>(10 x 10 km squares)</small> - links to large image Mosaics</li>

	<li>{external href="http://www.geograph.org/mosaic/" text="Mosaics of <b>completed myriads</b>"} <small>(100 x 100 km squares)</small> - using a Flash-based viewer</li>

        <li>{external href="http://www.geograph.org/coverage/" text="<b>Historic coverage maps</b>"} - includes build-up animations!</li>

</ul>
<h3>Selections...</h3>
<ul class="explore">

	<li><b>{external href="http://www.geograph.org/gallery.php" text="Showcase Gallery"}</b> - Hand selected selection of Geograph Images</li>

	<li><form action="/tags/" method="get" style="display:inline"> <a href="/tags/"><b>Tagged Images</b></a> 
                <input type="text" name="tag" size="30" maxlength="60" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" autocomplete="off"/ placeholder="Tag Search">
                <input type="submit" value="View"/> - Explore Geograph images by tag/label!<br/>
                <div style="position:relative;">
                        <div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:86px;padding-right:20px" id="tagParent">
                                <ul id="taglist">
                                </ul>
                        </div>
                </div>
	</form></li>

        <li><a href="/tags/primary.php"><b>Geographical Context</b></a> - high level categorization of Geograph Images<br>
		Can also view the <a href="/tags/prefix.php?prefix=subject&output=alpha">Subjects</a> by <a href="/tags/prefix.php?prefix=subject&output=context">Context</a></li>

	<li><a href="/stuff/thisday.php"><b>This day in past years</b></a> - selections of images taken on this day in previous years</li>

	<li><a href="/thumbed-weekly.php"><b>This week's popular images</b></a> - view images being <a href="/faq.php#thumbsup">thumbed</a></li>

	<li><a href="/explore/searches.php"><b>Featured searches</b></a> - hand-picked example searches</li>

	<!--li>{external href="http://ww2.scenic-tours.co.uk/serve.php?t=WolNuJvoMhXMJL5405olNblMbhZjaMtNXNh#station=achnasheen%3ANH1658" text="Geograph Stations Viewer"} - experimental interative station viewer</li-->

	<li><a href="/explore/rivers.php">Prime <b>Rivers</b> of Great Britain</a> - sample of images for all major rivers</li>

	<li><a href="/explore/routes.php">Follow <b>national routes</b></a> - national trails etc.</li>

	<li><a name="counties"></a>View photographs at <b>centre points</b>: <ul>
		<li><a href="/explore/counties.php?type=modern">Modern administrative counties</a>,
		<a href="/explore/counties.php">Ceremonial (pre-1994) counties</a>,
		<a href="/explore/counties.php?type=pre74">Historic (pre-1974) counties</a></li>
		<li><a href="/explore/counties.php?type=capital">(Irish) County capitals</a>, <a href="/explore/cities.php">cities and large towns</a></li>
	</ul></li>

	<li><a href="/gallery/"><b>Galleries</b></a> - hand-picked images on various themes</li>

	<li><a href="/explore/places/" title="Explore by Place">Explore the <b>placename gazetteer</b></a> (or try a <a href="/finder/places.php">search</a>)</li>

	<!--li>{external href="http://www.geographs.org/portals/portals.php" text="Geograph Portals"} <small>Experimental collections of Geograph images</small></li-->

</ul>
<h3>Breakdowns...</h3>
<ul class="explore">

	<li><a href="/explore/calendar.php">Geograph <b>Calendar</b></a>, view images by date taken.</li>

	<li><a href="/statistics/breakdown.php?by=class">Breakdown by <b>Category</b></a>, (soon to be replaced by tags).</li>

	<li><a href="/credits/">Explore by <b>contributor</b></a>.</li>

</ul>
<h3>Go anywhere...</h3>
<ul class="explore">

	<li><a href="/browser/#!start">Geograph Browser</a> - Interactive browser to quickly search and browse images</li>

	<li>SuperLayer: for <a href="/kml.php">Google Earth</a> - dragable/zoomable interactive maps - shows all images</li>

	<li><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref">Browse</label> by <b>grid reference</b>: 
	<input id="gridref" type="text" name="gridref" value="ST8751" size="15" style="color:gray" onfocus="{literal}if (this.value=='ST8751') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo" type="submit" value="Go"/>
	</form></li>

	<li><b><a href="/explore/random.php">Image Randomizer</a></b><sup style="color:red">new!</sup> or <a href="/stuff/browse-random.php">Jump to a <b>random</b> grid square</a>.</li>

	<li><a href="/mapper/combined.php">View a <b>Draggable map</b></a> shows coverage and individial photos. (use the search button on the map to jump to your area of interest)</li>

	<li><a href="/hectadmap.php">View a <b>Hectad coverage</b> map</a></li>

	<li><form method="get" action="/search.php" style="display:inline">
	<input type="hidden" name="form" value="explore"/>
	<label for="searchterm"><b>Search</b> by keyword, place, postcode or contributor</label>: <br/>
	<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
	<input id="searchgo" type="submit" name="go" value="Find"/> [<a href="/help/search">help</a>, <a href="/search.php?form=advanced">advanced search</a>]
	</form></li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="county_id">Search by centre of <b>ceremonial county</b></label>: 
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
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script src="/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
  <link rel="stylesheet" href="/js/chosen/chosen.css" />
<script type="text/javascript">{literal}
$(function() {
	$("#topic_id, #county_id").chosen();
});


        function loadTagSuggestions(that,event) {

                var unicode=event.keyCode? event.keyCode : event.charCode;
                if (unicode == 13) {
                        //useTags(that);
                        return;
                }

                param = 'q='+encodeURIComponent(that.value);

                $.getJSON("/tags/tags.json.php?"+param+"&callback=?",

                // on search completion, process the results
                function (data) {
                        var div = $('#taglist').empty();
                        $('#tagParent').show();

                        if (data && data.length > 0) {

                                for(var tag_id in data) {
                                        var text = data[tag_id].tag;
                                        if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='category' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
                                                text = data[tag_id].prefix+':'+text;
                                        }
                                        text = text.replace(/<[^>]*>/ig, "");
                                        text = text.replace(/['"]+/ig, " ");

                                        div.append("<li><a href=\"/tagged/"+text+"\">"+text+"</a></li>");
                                }

                        } else {
                                div.append("<li><a href=\"/tagged/"+that.value+"\">"+that.value+"</a></li>");
                        }
                });
        }

{/literal}</script>

{include file="_std_end.tpl"}
