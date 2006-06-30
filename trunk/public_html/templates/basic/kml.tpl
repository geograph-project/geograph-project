{assign var="page_title" value="KML Export"}
{include file="_std_begin.tpl"}
{dynamic}
	<div style="padding:5px;background:#dddddd;position:relative; float:right; font-size:0.8em">
	Quick links:<br/><br/>
	<b>Recent Images</b>: <br/>
	&nbsp;&nbsp;&nbsp;<a href="/feed/recent/KML">Google Earth</a><br/>
	&nbsp;&nbsp;&nbsp;{external href="http://maps.google.co.uk/maps?q=http://www.geograph.org.uk/feed/recent/KML" text="Google Maps"}<br/>
	{if $user->registered}<br/> 
	<b>Recent Discussions</b>:<br/> 
	&nbsp;&nbsp;&nbsp;<a href="/discuss/feed/forum5/KML">Google Earth</a><br/>
	&nbsp;&nbsp;&nbsp;{external href="http://maps.google.co.uk/maps?q=http://www.geograph.org.uk/discuss/feed/forum5/KML" text="Google Maps"}<br/>
	{/if}
	</div>

	 <h2>Use Geograph with Google Earth or Google Maps</h2> 
	 <p>
	 {external href="http://earth.google.com/" text="Google Earth"}
		is a free desktop application
	 allowing you view satellite images and geo-located information for the entire globe.
	 </p>
	 <p>
	 {external href="http://maps.google.co.uk/" text="Google Maps"}
		provides zoomable street-maps and satellite imagery in an online interface.
	 </p>
	 
	<form method="post" action="{$script_name}"> 
	
	{if $i} 
		<div style="padding:5px;background:#dddddd;position:relative">
		<input type="hidden" name="i" value="{$i}"/>
		Your <a href="/search.php?i={$i}">search</a> for images<i>{$engine->criteria->searchdesc}</i>, returns 
		<b>{$engine->resultCount}</b> results.	
		{if $engine->criteria->searchclass != 'Special'}[<a href="search.php?i={$i}&amp;form=advanced">refine</a>]{/if}
		{if $engine->resultCount == 0} 
			<p>Please enter <a href="/search.php">another search</a>, or select <a href="/kml.php">common exports</a></p>
		{/if}
	{else} 
	 <p>Anywhere you see a <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a>
	 link on the Geograph website, click to download images into Google Earth or Maps.</p>

		<div style="padding:5px;background:#dddddd;position:relative">
		Show <select name="i" id="i">
		{html_options options=$is}
		</select><br/>
		
		Or perform a normal <a href="/search.php">search</a> and 
		look for the the <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a> button.
	{/if}</div>
	
	<p><input type="submit" name="submit" value="Download KML file ({if $engine->resultCount > $engine->criteria->resultsperpage}{$engine->criteria->resultsperpage}{else}{$engine->resultCount}{/if} images)..."/> <span id="advtoggle"></span> </p>
	
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
		  {if $engine->islimited}
		  <tr> 
			 <td><input type="radio" name="type" value="view" id="type_view"/></td> 
			 <td colspan="2"><b><label for="type_view"><big><acronym title="View-Based Refresh">Wide area</acronym></big></label></b><br>
			 For a large result set covering a wide area, this option allows the <b>Google Earth</b> application
			 to just show the photos within the area of view. As you scroll around, Google Earth will
			 automatically fetch other photos from the result set. Please note that you'll normally only see
			 {if $i && $engine->resultCount}{$engine->criteria->resultsperpage}{else}around 15{/if} photos at a time with this option.
			 </td> 
		  </tr> 
		  {/if}
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
	
	<script type="text/javascript">
	{literal}
	var advanced=document.getElementById('advanced');
	advanced.style.display='none';
	
	var advtoggle=document.getElementById('advtoggle');
	advtoggle.innerHTML=' <a href="#" onclick="showAdvanced()">[open advanced options (and Google Maps)...]</a>';
	
	function showAdvanced()
	{
		var advanced=document.getElementById('advanced');
		advanced.style.display='block';
		var advtoggle=document.getElementById('advtoggle');
		advtoggle.style.display='none';
	}
	{/literal}
	</script>
	
	</form>
{/dynamic} 


<p style="background-color:lightgreen;padding:10px;">Alternatively you can load the <a href="/gpx.php">GPX</a> files into Google Earth, to produce coverage maps. (rather than loading the individual images).</p>

<div class="copyright">Google Earth and Google Maps are registered trademarks of Google Inc. Geograph is not affiliated with Google.</div>

{include file="_std_end.tpl"}
