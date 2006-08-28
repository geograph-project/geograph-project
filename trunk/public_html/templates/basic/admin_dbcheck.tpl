{assign var="page_title" value="database check"}
{include file="_std_begin.tpl"}
{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :Database Check</h2>
<p>This tool will analyse the database tables for corruption at the
db server level, and also perform some application level sanity checks</p>

<form method="post" action="dbcheck.php">
<input type="checkbox" checked="checked" name="dbtables" value="1" id="dbtables"/>
	<label for="dbtables">Database tables</label><br/>
<input type="checkbox" checked="checked" name="gridsquares" value="1" id="gridsquares"/>
	<label for="gridsquares">Gridsquare Integrity Check</label><br/>
	&nbsp;<input type="checkbox" checked="checked" name="fix" value="1" id="fix"/>
	<label for="fix">Fix problems if possible</label><br/>
<input type="checkbox" checked="checked" name="geographs" value="1" id="geographs"/>
	<label for="geographs">check 1 geograph per square</label><br/>

<input type="submit" name="check" value="Perform Database Check"/>
</form>

{/dynamic}    
{include file="_std_end.tpl"}
