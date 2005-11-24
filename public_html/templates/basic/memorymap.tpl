{assign var="page_title" value="Memory Map Export"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

	<h2>Memory Map Export</h2>

	<p>Use this page to download a CSV file to import into Memory Map, to show squares currently with Geograph Images.</p>
{dynamic}
{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
{/dynamic}
	<form method="post" action="{$script_name}">
		<p><label for="gridsquare">grid square</label> 
		<select name="gridsquare" id="gridsquare" size="1"> 
			<option value="">Choose...</option>
			{html_options options=$prefixes selected=$gridsquare}
		</select> <input type="submit" value="Download CSV file..."/></p>
	</form>

	<p>You will also need the Geograph Icon: <a href="{$script_name}?getbmp=1">geograph.bmp</a></p>

{include file="_std_end.tpl"}
