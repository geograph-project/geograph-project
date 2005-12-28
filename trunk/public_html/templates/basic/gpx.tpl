{assign var="page_title" value="GPX Export"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

	<h2>GPX Export</h2>

	<p>Use this page to download a {external href="http://en.wikipedia.org/wiki/GPX" text="GPX"} file to load into your mapping program and/or GPS receiver. This is ideal for creating a high tech version of the Printable Check sheet for when you go paperless. If you use Memory Map you may prefer <a href="/memorymap.php">this page</a> instead. </p>
{dynamic}
{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
	<form method="post" action="{$script_name}">
		<p><label for="gridsquare">center grid square</label> 
		<input id="gridref" type="text" name="gridref" value="{$gridref|escape:'html'}" size="8"/>
		(Can use SH34 for SH3545)</p>
		
		<p><label for="distance" id="l_distance">radius </label>
		<select name="distance" id="distance" size="1" style="text-align:right"> 
			{html_options values=$distances output=$distances selected=$distance}
		</select>km</p>
		
		<p>Download Squares 
			<input id="type_with" type="radio" name="type" value="with" size="8"{if $type == 'with'} checked="checked"{/if}/><label for="type_with">with</label> 
			/<input id="type_without" type="radio" name="type" value="without" size="8"{if $type == 'without'} checked="checked"{/if}/><label for="type_without">without</label>
			Photographs</p>
		
		<p><input type="submit" name="submit" value="Download GPX file..."/></p>
	</form>
{/dynamic}

{include file="_std_end.tpl"}
