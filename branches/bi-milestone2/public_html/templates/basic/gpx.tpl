{assign var="page_title" value="GPX Export"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

	<h2>GPX Export</h2>

	<p>Use this page to download a {external href="http://www.topografix.com/gpx.asp" text="GPX"} file to load into your mapping program and/or GPS receiver. This is ideal for creating a high tech version of the Printable Check sheet for when you go paperless.</p>
{dynamic}
{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
	<form method="get" action="{$script_name}">
		<div style="background-color:#eeeeee;position:relative;padding-left:10px;padding-top:1px;padding-bottom:1px;">
		<p><label for="gridsquare">center grid square</label> 
		<input id="gridref" type="text" name="gridref" value="{$gridref|escape:'html'}" size="8"/>
		(Can use SH34 for SH3545)</p>
		
		<p><input id="limit_distance" type="radio" name="limit" value="distance"{if $limit == 'distance'} checked="checked"{/if}/><label for="distance" id="l_distance">radius </label>
		<select name="distance" id="distance" size="1" style="text-align:right"> 
			{html_options values=$distances output=$distances selected=$distance}
		</select>km <i>or</i><br/>
		<input id="limit_points" type="radio" name="limit" value="points"{if $limit == 'points'} checked="checked"{/if}/><label for="points" id="l_points">number of points </label>
		<select name="points" id="points" size="1" style="text-align:right"> 
			{html_options values=$distances output=$distances selected=$points}
		</select></p>
		
		<p>Download Squares 
			<input id="type_with" type="radio" name="type" value="with"{if $type == 'with'} checked="checked"{/if}/><label for="type_with">with</label> 
			/<input id="type_without" type="radio" name="type" value="without" size="8"{if $type == 'without'} checked="checked"{/if}/><label for="type_without">without</label>
			/<input id="type_few" type="radio" name="type" value="few"{if $type == 'few'} checked="checked"{/if}/><label for="type_few">with few</label> 
			Photographs /<input id="type_nogeos" type="radio" name="type" value="nogeos" size="8"{if $type == 'nogeos'} checked="checked"{/if}/><label for="type_nogeos">no Geographs</label> </p>
		</div>
		
		<p><input type="submit" name="submit" value="Download GPX file..."/></p>
	</form>
{/dynamic}

<p style="background-color:yellow;padding:10px;">If you use Memory Map you may prefer <a href="/memorymap.php">this page</a> instead.</p>

<p style="background-color:lightgreen;padding:10px;">Loading these GPX files into Google Earth will produce coverage maps. You can also load in actual images into Google Earth, using <a href="/kml.php">KML files</a>.</p>

<p style="background-color:lightblue;padding:10px;">You can also download the images from a set of search results and/or the <a title="Latest Images in GPX format" href="/feed/recent.gpx">latest uploads</a> in GPX format. </p>

{include file="_std_end.tpl"}
