{assign var="page_title" value="Google Earth or Google Maps :: KML Export"}
{include file="_std_begin.tpl"}
{dynamic}
	<div style="padding:5px;background:#dddddd;position:relative; float:right; font-size:0.8em">
	Quick links:<br/><br/>
	<b>All Geograph Images</b>: <br/>
	&nbsp;&nbsp;&nbsp;<a href="/kml-superlayer.php">Google Earth <b>Version 4</b></a><br/>
	<br/> 
	<b>Recent Images</b>: <br/>
	&nbsp;&nbsp;&nbsp;<a href="/feed/recent.kml">Google Earth</a><br/>
	&nbsp;&nbsp;&nbsp;{external href="http://maps.google.co.uk/maps?q=http://$http_host/feed/recent.kml" text="Google Maps"}<br/>
	{if $user->registered}<br/> 
	<b>Recent Discussions</b>:<br/> 
	&nbsp;&nbsp;&nbsp;<a href="/discuss/feed/forum5.kml">Google Earth</a><br/>
	&nbsp;&nbsp;&nbsp;{external href="http://maps.google.co.uk/maps?q=http://$http_host/discuss/feed/forum5.kml" text="Google Maps"}<br/>
	{/if}
	<br/> 
	<b><a href="/help/squares">Hectad</a> 3D Coverage Graph</b>:<br/> 
	&nbsp;&nbsp;&nbsp;<a href="/kml/hectads-points.kmz">Google Earth</a> (~200kb KMZ)<br/>
	{if $user->registered}
	&nbsp;&nbsp;&nbsp;<a href="http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=2&topic=4415"><i>More <small>including time animation</small></i></a><br/>
	{/if}
	<small><small><i>Last updated: {$coverage_updated}</i></small></small>
	</div>

	{if !$i}<div class="interestBox" style="width:550px;background-color:pink;padding:2px">
	 NEW! <a href="/kml"><b>Geograph Layer Collection</b> for Google Earth!</a><br/>
	- includes access to nearly all the features on this page and more! </div>
	 {/if}
	 
	 <h2>Geograph Images in Google Earth or Google Maps</h2> 
	 <p>&middot;
	 {external href="http://earth.google.com/" text="Google Earth"}
		is a free desktop application
	 allowing you view satellite images and geo-located information for the entire globe.
	 </p>
	
	 <div class="interestBox" style="width:550px;background-color:yellow;padding:2px;">
	 <img src="/kml/images/cam1-small.gif" width="24" height="24"/> <a href="/kml-superlayer.php"><b>Geograph SuperLayer</b></a> (Google Earth Version 4+ REQUIRED)
	{if $i}<br/><i><b>- displays all images - not the selection as per requested search</b></i>{/if}
	<small><br/><br/>This SuperLayer allows full access to the thousends of images contributed to Geograph, the view starts depicting a coarse overview of the current coverage, zooming in reveals more detail until pictures themselves become visible. (Broadband Recommended) 
	 <br/><small><i>Last updated: {$superlayer_updated}</i></small>
	 <a href="/help/superlayer">View Icon Key</a></small>
	 </div>
	
	 
	 
	 <p>&middot;
	 {external href="http://maps.google.co.uk/" text="Google Maps"}
		provides zoomable street-maps and satellite imagery in an online interface.
	 </p>
	 
	<form method="post" action="{$script_name}"> 
	
	{if $i} 
	
	<div class="interestBox">
	
		<input type="hidden" name="i" value="{$i}"/>
		Your <a href="/search.php?i={$i}">search</a> for images<i>{$engine->criteria->searchdesc}</i>, returns 
		<b>{$engine->resultCount}</b> results.	
		{if $engine->criteria->searchclass != 'Special'}[<a href="search.php?i={$i}&amp;form=advanced">refine</a>]{/if}
		{if $engine->resultCount == 0} 
			<p>Please enter <a href="/search.php">another search</a>, or select <a href="/kml.php">common exports</a></p>
		{/if}
	{else} 
	 <p>Anywhere you see a <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a>
	 link on the Geograph website, (for example on a photo page, or on search results) click to download images into Google Earth or Maps.</p>

	<div class="interestBox">
		
		Show <select name="i" id="i">
		{html_options options=$is}
		</select><br/>
		
		Or perform a <a href="/search.php">search</a> and 
		look for the the <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a> button on the results page.
	{/if}
	<br/><br/>
	<input type="submit" name="submit" value="Download KML file ({if $engine->resultCount > $engine->criteria->resultsperpage}{$engine->criteria->resultsperpage}{else}{$engine->resultCount}{/if} images)..."/> <span id="advtoggle"></span>
	
	
	<div id="advanced">
	{if $i && $engine->resultCount || !$i}	
	 	<h3>Download type:</h3> 
		<table border="1" cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><input type="radio" name="type" value="maps" id="type_maps"
				checked="checked"/></td> 
			 <td><b><label for="type_maps"><big><acronym title="Open in Google Maps">Maps</acronym></big></label></b><br/>
			 Open search results in the <b>Google Maps UK</b> website.<br/><br/></td> 
			 <td rowspan="3"><label for="page">Download<br/> Page</label>
				<input type="text" name="page" value="{$currentPage}" size="3" id="page"/> of {if $engine->numberOfPages}{$engine->numberOfPages}{else}results{/if}.</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="static" id="type_static"
				checked="checked"/></td> 
			 <td><b><label for="type_static"><big><acronym title="Static One Off Download">Simple</acronym></big></label></b><br/>
			 View your search results in <b>Google Earth</b> by downloading the KML file.</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="time" id="type_time"/></td> 
			 <td><b><label for="type_time"><big><acronym title="Time-Based Refresh">Automatic Updates</acronym></big></label></b> <br/>
			 Like 'Simple', but <b>Google Earth</b> will refresh the results <label>once every 
				<select name="refresh" size="1"> 
				  <option value="3600">Hour</option> 
				  <option value="21600">6 Hours</option> 
				  <option value="86400" selected="selected">24 Hours</option> 
				  <option value="604800">7 Days</option> 
				</select></label></td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="view" id="type_view"/></td> 
			 <td colspan="2"><b><label for="type_view"><big><acronym title="View-Based Refresh">Wide area</acronym></big></label></b><br>
			 For a large result set covering a wide area, this option allows the <b>Google Earth</b> application
			 to just show the photos within the area of view. As you scroll around, Google Earth will
			 automatically fetch other photos from the result set. Please note that you'll normally only see
			 {if $i && $engine->resultCount}{$engine->criteria->resultsperpage}{else}around 15{/if} photos at a time with this option. (if you have GE4+ it is recommended to use the <a href="/kml.php">superlayer</a>, however you will not get the filtering of the results, and will see all images)
			 </td> 
		  </tr> 
		</table> 
		
		<input type="hidden" name="simple" value="1"/>
		<!--
		<h3>View Type:</h3> 
		<div style="float:left;position:relative;width:50%">
			<div><input type="radio" name="simple" value="1" id="simple_1"
				checked="checked"> <b><label for="simple_1">Image Title pops up when point at image</label></b></div> 
			<div><input type="radio" name="simple" value="0" id="simple_0"> <b><label for="simple_0">Image Title is always visible</label></b></div> 
		</div> 
		-->
		
		
		<br style="clear:both"/>
		
	{/if}
	
	</div>	
	
	</div>
	{if !$adv} 
	<script type="text/javascript">
	{literal}
	var advanced=document.getElementById('advanced');
	advanced.style.display='none';
	
	var advtoggle=document.getElementById('advtoggle');
	advtoggle.innerHTML=' <a href="#" onclick="showAdvanced()">[<b>open advanced options</b> (including opening results in Google Maps)...]</a>';
	
	function showAdvanced()
	{
		var advanced=document.getElementById('advanced');
		advanced.style.display='block';
		var advtoggle=document.getElementById('advtoggle');
		advtoggle.style.display='none';
	}
	{/literal}
	</script>
	{/if}
	</form>
{/dynamic} 


<p style="background-color:lightgreen;padding:10px;">Alternatively you can load the <a href="/gpx.php">GPX</a> files into Google Earth, to produce coverage maps. (rather than loading the individual images).</p>

<div class="copyright">Google Earth and Google Maps are registered trademarks of Google Inc. Geograph is not affiliated with Google.</div>

{include file="_std_end.tpl"}
