{assign var="page_title" value="Google Earth :: KML Export"}
{include file="_std_begin.tpl"}
{dynamic}
	<div style="padding:5px;background:#dddddd;position:relative; float:right; font-size:0.8em; z-index:100; border:1px solid blue">
	Quick links: <a href="javascript:void(show_tree(1));" id="hide1">Expand...</a>
	<span style="display:none" id="show1"><a href="javascript:void(hide_tree(1));">Hide</a><br/><br/>
	<b>Recent Images</b>: <br/>
	&nbsp;&nbsp;&nbsp;<a href="/feed/recent.kml" title="recent Geograph Submissions" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a>
	&nbsp;&nbsp;&nbsp;{external href="http://www.bing.com/maps/default.aspx?v=2&mapurl=http://$http_host/feed/recent.kml" text="Bing Maps"}<br/>
	<br/>
	<b>Recent Articles</b>: <br/>
	&nbsp;&nbsp;&nbsp;<a href="/article/feed/recent.kml" title="recent Geograph Articles" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a>
	&nbsp;&nbsp;&nbsp;{external href="http://www.bing.com/maps/default.aspx?v=2&mapurl=http://$http_host/article/feed/recent.kml" text="Bing Maps"}<br/>
	{if $user->registered && $enable_forums}<br/>
	<b>Recent Discussions</b>:<br/>
	&nbsp;&nbsp;&nbsp;<a href="/discuss/feed/forum5.kml" title="recent Geograph Discussions" Google Earth class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a>
	&nbsp;&nbsp;&nbsp;{external href="http://www.bing.com/maps/default.aspx?v=2&mapurl=http://$http_host/discuss/feed/forum5.kml" text="Bing Maps"}<br/>
	{/if}
	<br/>
	{if $coverage_updated}
	<b>Hectad<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> 3D Coverage Graph</b>:<br/>
	&nbsp;&nbsp;&nbsp;<a href="/kml/hectads-points.kmz" title="Geograph 3D Coverage Graph" class="xml-kml" type="application/vnd.google-earth.kmz+xml">KMZ</a> (about 200 kilobytes)<br/>
	{if $user->registered}
	&nbsp;&nbsp;&nbsp;<a href="/discuss/index.php?&action=vthread&forum=2&topic=4415"><i>More <small>including time animation</small></i></a><br/>
	{/if}
	<small><small><i>Last updated: {$coverage_updated}</i></small></small><br/>
	<br/>
	{/if}
	<b>Geograph Layer Collection</b>:<br/>
	&nbsp;&nbsp;&nbsp;<a href="/kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> <small><small>- includes access to<br/> nearly all the features on this page and more!</small></small></span>
	</div>

	 <h2>View Geograph Images using KML</h2>
	 <p>&middot;
	 {external href="http://earth.google.com/" text="Google Earth"}
		is a free desktop application
	 allowing you view satellite images and geo-located information for the entire globe.
	 </p>

	{if !$i}

	 <p style="margin-left: 15px"><small>Anywhere you see a <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a>
		 icon on the Geograph website, (for example on a photo page) click to download into Google Earth.</small></p>

	{/if}


	<form method="post" action="{$script_name}" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

	{if $i}

	<div class="interestBox" style="background-color:#eeeeee">

		<input type="hidden" name="i" value="{$i}"/>
		Your <a href="/search.php?i={$i}"><b>search for images<i>{$engine->criteria->searchdesc|escape:"html"}</i></b></a>, returns
		<b>{$engine->resultCount}</b> results.
		{if $engine->criteria->searchclass != 'Special'}[<a href="search.php?i={$i}&amp;form=advanced">refine</a>]{/if}
		{if $engine->resultCount == 0}
			<p>Please enter <a href="/search.php">another search</a>, or select <a href="/kml.php">common exports</a></p>
		{/if}

		{if $engine->fullText && !$engine->criteria->sphinx.compatible}
			<br/><br/>
			<div style="padding:2px;border:1px solid gray; font-size:0.7em;text-align:center">
			This search is powered by the new <a href="/help/search_new">experimental Full-Text search index</a>, which in some ways is less precise than the legacy search, but often results in quicker and more relevant results. However at this time it's not fully compatible with all KML exports, such options are currently hidden.</div>
			<br/><br/>
		{/if}
	{else}

	<div class="interestBox">

		Predefined Searches:  <select name="i" id="i">
		{html_options options=$is}
		</select><br/>

		Or perform a <a href="/search.php">search</a> and
		look for the KML link at the foot of the results page.
		<br/><br/>
	{/if}



	<div id="advanced">
	{if $i && $engine->resultCount || !$i}
	 	<h3>Download type:</h3>
	 	<label for="page">Download Page</label>
				<input type="text" name="page" value="{$currentPage}" size="3" id="page"/> of {if $engine->numberOfPages}{$engine->numberOfPages}{else}results{/if} in {if $engine->fullText && $engine->resultCount > $engine->maxResults}(only {$engine->maxResults} results available){/if} ...
		<table border="1" cellpadding="3" cellspacing="0">
		  <tr>
			 <td><input type="radio" name="type" value="live" id="type_live"/></td>
			 <td><b><label for="type_live"><big><acronym title="Open on maps.live.com">maps.live.com</acronym></big></label></b><br/>
			 Open search results on the <b>maps.live.com</b> website (will automatically update).</td>
		  </tr>
		  <tr style="display:none">
			 <td><input type="radio" name="type" value="maps" id="type_maps"/></td>
			 <td><b><label for="type_maps"><big><acronym title="Open in Google Maps">Google Maps</acronym></big></label></b><br/>
			 Open search results in the <b>Google Maps UK</b> website (will automatically update).</td>
		  </tr>
		  <tr>
			 <td><input type="radio" name="type" value="static" id="type_static"
				checked="checked"/></td>
			 <td><b><label for="type_static"><big><acronym title="Static One Off Download">Google Earth</acronym></big></label></b><br/>
			 View your search results in <b>Google Earth</b> by downloading the KML file. Downloads the raw data which will not update.</td>
		  </tr>
		  <tr>
			 <td><input type="radio" name="type" value="time" id="type_time"/></td>
			 <td><b><label for="type_time"><big><acronym title="Time-Based Refresh Network Link">Automatic Updates for Google Earth</acronym></big></label></b> <br/>
			 Like 'Simple', but <b>Google Earth</b> will refresh the results <label>once every
				<select name="refresh" size="1">
				  <option value="3600">hour</option>
				  <option value="21600">6 hours</option>
				  <option value="86400" selected="selected">24 hours</option>
				  <option value="604800">7 days</option>
				</select></label></td>
		  </tr>
		</table>

		<br/> -or - <br/><br/>
		 For a large result set covering a wide area, this option allows the application
		 to just show the photos within the area of view. As you scroll around, it will
		 automatically fetch other photos from the result set. Please note that you'll normally only see
		{if $i && $engine->resultCount}{$engine->criteria->resultsperpage}{else}around 15{/if} photos at a time with this option.

		<table border="1" cellpadding="3" cellspacing="0">
		  <tr>
			 <td><input type="radio" name="type" value="view" id="type_view"/></td>
			 <td><b><label for="type_view"><big><acronym title="View-Based Refresh Network Link">Wide area</acronym></big></label></b><br/>Browse these results in <b>Google Earth</b></td>

		  </tr>
		  <tr style="display:none">

			 <td><input type="radio" name="type" value="mapsview" id="type_mapsview"/></td>
			 <td colspan="2"><b><label for="type_mapsview"><big><acronym title="View-Based Refresh Network Link on Maps">Wide area Maps</acronym></big></label></b><br/>Browse these results on <b>Google Maps</b></b>
		  </tr>
		</table>

		<input type="hidden" name="simple" value="1"/>


		<br style="clear:both"/>

	{/if}

	</div>

	<div style="border:1px solid red; padding: 10px;">
	<input type="submit" name="submit" style="font-size:1.1em" value="Download KML file ({if $engine->resultCount > $engine->criteria->resultsperpage}{$engine->criteria->resultsperpage}{else}{$engine->resultCount}{/if} images)..."/> <div id="advtoggle"></div>
	</div>

	</div>

	<script type="text/javascript">
	{literal}
	function hideAdvanced() {
		var advanced=document.getElementById('advanced');
		advanced.style.display='none';

		var advtoggle=document.getElementById('advtoggle');
		advtoggle.innerHTML='<br/>&middot; Should open in Google Earth, alternativly <a href="#" onclick="return showAdvanced()"><b>open advanced panel</b></a> for more options';
	}
	function showQuickLinks() {
		show_tree(1);
	}
	{/literal}
	{if !$adv}
	AttachEvent(window,'load',hideAdvanced,false);
	{/if}
	{if !$i}
	AttachEvent(window,'load',showQuickLinks,false);
	{/if}
	{literal}
	function showAdvanced()	{
		var advanced=document.getElementById('advanced');
		advanced.style.display='block';
		var advtoggle=document.getElementById('advtoggle');
		advtoggle.style.display='none';
		return false;
	}
	{/literal}
	</script>

	</form>
{/dynamic}

<br style="clear:both"/>

{if !$i}
<p style="background-color:lightgreen;padding:10px;">Alternatively you can load the <a href="/gpx.php">GPX</a> files into Google Earth, to produce coverage maps (rather than loading the individual images).</p>
{/if}

<div class="copyright">{external href="http://www.opengeospatial.org/standards/kml/" text="KML"} is now owned and controlled by Open Geospatial Consortium Inc.<br/>Google Earth and Google Maps are registered trademarks of Google Inc. Geograph is not affiliated with Google.</div>

{include file="_std_end.tpl"}
