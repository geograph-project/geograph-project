{assign var="page_title" value="Google Earth Export"}
{include file="_std_begin.tpl"}
{dynamic}

	 <H2>Geograph to Google Earth Export</H2> 
	 <P>Use this page to open some pictures from Geograph directly in
		your <A HREF="http://earth.google.com/">Google Earth</A> client.</P> 
	<form method="post"> 
	
	{if $i} 
		<div style="padding:5px;background:#dddddd;position:relative">
		Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
		<b>{$engine->resultCount}</b> results.	

		{if $engine->resultCount == 0} 
			<p>Please enter <a href="/search.php">another search</a>, or select <a href="/kml.php">common exports</a></p>
		{/if}
	{else} 
		<h3>Select a predefined Search</h3>
		<div style="padding:5px;background:#dddddd;position:relative">
		<select name="i" id="i">
		{html_options options=$is}
		</select><br/>
		
		Or perform a normal <a href="/search.php">search</a> and 
		look for the the <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a> button.
	{/if}</div>
	
	
	
	{if $i && $engine->resultCount || !$i}	
	 	<h3>Download type:</h3> 
		<table border="1" cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><input type="radio" name="type" value="static" id="type_static"
				checked="checked"></td> 
			 <td><b><label for="type_static">Static File</label></b> - this will open the current results in Google
				Earth.</td> 
			 <td rowspan="2"><label for="page">Download Page</label><br/>
				<input type="text" name="page" value="{$currentPage}" size="3" id="page"> of {if $engine->numberOfPages}{$engine->numberOfPages}{else}results{/if}.</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="time" id="type_time"></td> 
			 <td><b><label for="type_time">Time-Based Network Link</label></b> <br/>- Update your results in the
				client, <label>once every 
				<select name="refresh" size="1"> 
				  <option value="3600">Hour</option> 
				  <option value="21600">6 Hours</option> 
				  <option value="86400" selected="selected">24 Hours</option> 
				  <option value="604800">7 Days</option> 
				</select></label></td> 
		  </tr> 
		  {if $engine->islimited}
		  <tr> 
			 <td><input type="radio" name="type" value="view" id="type_view"></td> 
			 <td colspan="2"><b><label for="type_view">View-Based Network Link</label></b> - Works well to open
				very broad results by downloading images that is visible in the current view.
				By the nature of this it will alway's be using the latest images, results are
				not pageated!</td> 
		  </tr> 
		  {/if}
		</table> 
		<h3>View Type:</h3> 
		<div style="float:left;position:relative;width:50%">
			<div><input type="radio" name="simple" value="1" id="simple_1"
				checked="checked"> <b><label for="simple_1">Image Title pops up when point at image</label></b></div> 
			<div><input type="radio" name="simple" value="0" id="simple_0"> <b><label for="simple_0">Image Title is always visible</label></b></div> 
		</div> 
		<div style="float:left;position:relative;width:50%;text-align:center">
		<p><input type="submit" name="submit" value="Download KML file..."></p>
		</div>
		<br style="clear:both"/>
		<p><a href="search.php">Start a new search</a> or choose <a href="/kml.php">predefined exports</a>
	{/if}
	</form>
{/dynamic}    
{include file="_std_end.tpl"}
