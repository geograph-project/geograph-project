{assign var="page_title" value="Google Earth Export"}
{include file="_std_begin.tpl"}
{dynamic}

	 <H2>Geograph to Google Earth Export</H2> 
	 <P>Use this page to open some pictures from Geograph directly in
		your <A HREF="http://earth.google.com/">Google Earth</A> client.</P> 
	<form method="post"> 
	
	{if $i} 
	
		<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
		<b>{$engine->resultCount}</b> results.	

		{if $engine->resultCount == 0} 
			<p>Please enter <a href="/search.php">another search</a>, or select <a href="/kml.php">common exports</a></p>
		{/if}
	{else} 
		<h3>Select a predefined Search</h3>
		<p><select name="i" id="i">
		{html_options options=$is}
		</select><br/>
		
		Or perform a normal <a href="/search.php">search</a> and 
		look for the the <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a> button.</p>
	{/if}
	
	
	
	{if $i && $engine->resultCount || !$i}	
	 	<h3>View Type:</h3> 
		<table border="1" cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><input type="radio" name="simple" value="1"
				checked="checked"></td> 
			 <td>Image Title pops up when point at image</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="simple" value="0"></td> 
			 <td>Image Title is always visible</td> 
		  </tr> 
		</table> 
		<h3>Download type:</h3> 
		<table border="1" cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><input type="radio" name="type" value="static"
				checked="checked"></td> 
			 <td><b>Static File</b> - this will open the current results in Google
				Earth, they will not update over time. </td> 
			 <td rowspan="2">Download Page<br>
				<input type="text" name="page" value="{$currentPage}" size="3"> of results.</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="time"></td> 
			 <td><b>Time-Based Network Link</b> - Update your results in the
				client, once every 
				<select name="refresh" size="1"> 
				  <option value="3600">Hour</option> 
				  <option value="21600">6 Hours</option> 
				  <option value="86400" selected="selected">24 Hours</option> 
				  <option value="604800">7 Days</option> 
				</select></td> 
		  </tr> 
		  {if $engine->islimited}
		  <tr> 
			 <td><input type="radio" name="type" value="view"></td> 
			 <td colspan="2"><b>View-Based Network Link</b> - Works well to open
				very broad results by downloading images that is visible in the current view.
				By the nature of this it will alway's be using the latest images, results are
				not pageated!</td> 
		  </tr> 
		  {/if}
		</table> 
		<p><input type="submit" name="submit" value="Download KML file..."></p>
	{/if}
	</form>
{/dynamic}    
{include file="_std_end.tpl"}
