{assign var="page_title" value="Memory Map Export"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

	<h2>Memory Map Export</h2>

	<p>Use this page to download a CSV file to import into Memory Map, to show squares currently with Geograph images by downloading a list of 'First Geographs'.</p>
{dynamic}
{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
{/dynamic}
	<form method="post" action="{$script_name}">
		<p><label for="gridsquare">Myriad</label><sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup>
		<select name="gridsquare" id="gridsquare" size="1">
			<option value="">Choose...</option>
			{html_options options=$prefixes selected=$gridsquare}
		</select> <input type="submit" value="Download CSV file..."/></p>
	</form>

	<p>You will also need the Geograph icon: <a href="{$script_name}?getbmp=1">geograph.bmp</a></p>

<p style="background-color:yellow;padding:10px;">Alternatively you can load the <a href="/gpx.php">GPX</a> files, which you can download smaller site centred files.</p>



{include file="_std_end.tpl"}
