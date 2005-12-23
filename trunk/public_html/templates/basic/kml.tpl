{assign var="page_title" value="Google Earth Export"}
{include file="_std_begin.tpl"}
{dynamic}

	 <h2>Use Geograph with Google Earth</h2> 
	 <p>
	 {external href="http://earth.google.com/" text="Google Earth"}
		is a free desktop application
	 allowing you view satellite images and geo-located information for the entire globe.
	 </p>
	 <p>Anywhere you see a <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a>
	 link on the Geograph website, you can click it to download images into Google Earth.
	 For example, perform a <a href="/search.php">search</a> and you'll be able to open
	 the <acronym title="Results are downloaded in KML format for direct opening.">results</acronym> in Google Earth.
	 
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
		<div style="padding:5px;background:#dddddd;position:relative">
		Show <select name="i" id="i">
		{html_options options=$is}
		</select><br/>
		
		Or perform a normal <a href="/search.php">search</a> and 
		look for the the <a title="Google Earth Feed" href="/kml.php" class="xml-kml">KML</a> button.
	{/if}</div>
	
	<p><input type="submit" name="submit" value="Download KML file ({$engine->criteria->resultsperpage} images)..."/> <span id="advtoggle"></span> </p>
	
	<div id="advanced">
	{if $i && $engine->resultCount || !$i}	
	 	<h3>Download type:</h3> 
		<table border="1" cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><input type="radio" name="type" value="static" id="type_static"
				checked="checked"/></td> 
			 <td><b><label for="type_static"><acronym title="Static One Off Download">Simple</acronym></label></b><br/>
			 View your search results in Google Earth.</td> 
			 <td rowspan="2"><label for="page">Download Page</label><br/>
				<input type="text" name="page" value="{$currentPage}" size="3" id="page"/> of {if $engine->numberOfPages}{$engine->numberOfPages}{else}results{/if}.</td> 
		  </tr> 
		  <tr> 
			 <td><input type="radio" name="type" value="time" id="type_time"/></td> 
			 <td><b><label for="type_time"><acronym title="Time-Based Refresh">Automatic Updates</acronym></label></b> <br/>
			 Like 'Simple', but Google Earth will refresh the results <label>once every 
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
			 <td colspan="2"><b><label for="type_view"><acronym title="View-Based Refresh">Wide area</acronym></label></b><br>
			 For a large result set covering a wide area, this option allows the Google Earth application
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
	advtoggle.innerHTML=' <a href="#" onclick="showAdvanced()">[advanced options...]</a>';
	
	function showAdvanced()
	{
		var advanced=document.getElementById('advanced');
		advanced.style.display='block';
	
	}
	{/literal}
	</script>
	
	</form>
{/dynamic}    
{include file="_std_end.tpl"}
