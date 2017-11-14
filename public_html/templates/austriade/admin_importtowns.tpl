{include file="_std_begin.tpl"}
{dynamic}

<h2>Town import</h2>

<form method="post" action="importtowns.php">
<input type="checkbox" name="dryrun" value="1" checked="checked" />
<label for="dryrun">Dry run: Don't touch the database</label><br />
<input type="checkbox" name="checkname" value="1"{if $checkname} checked="checked"{/if} />
<label for="checkname">Skip town if a town with same "short name" exists</label><br />
<label for="mincid">minimal cid:</label> <input size="12" type="text" id="mincid" name="mincid" value="{$mincid}"/><br />
<label for="maxcid">maximal cid:</label> <input size="12" type="text" id="maxcid" name="maxcid" value="{$maxcid}"/><br />
<input type="submit" name="submit" value="Ok">
</form>

{if $submit}
<hr />
{if $dryrun}
<p><b>DRY RUN</b></p>
{/if}

{*
	$smarty->assign_by_ref('towns',          $arr);
	$smarty->assign_by_ref('invalidtowns',   $invalidrows);
	$smarty->assign_by_ref('oldtowns',       $oldrows);
	$smarty->assign_by_ref('duplicatetowns', $duplicaterows);
*}

{if $duplicatetowns}
<h3>Duplicate cids</h3>
<table>
<tr><th>Name</th><th>Comm. Id</th></tr>
{foreach key=key item=row from=$duplicatetowns name=loop}
<tr><td>{$row.name|escape:"htmlall"}</td><td>{$row.cid}</td></tr>
{/foreach}
</table>
{/if}

{if $invalidtowns}
<h3>Invalid town names</h3>
<table>
<tr><th>Name</th><th>Comm. Id</th></tr>
{foreach key=key item=row from=$invalidtowns name=loop}
<tr><td>{$row.name|escape:"htmlall"}</td><td>{$row.cid}</td></tr>
{/foreach}
</table>
{/if}

{if $oldtowns}
<h3>Towns aleady imported (same community id)</h3>
<table>
<tr><th>Name</th><th>Short Name</th><th>Comm. Id</th><th>Name (old)</th><th>Short Name (old)</th></tr>
{foreach key=key item=row from=$oldtowns name=loop}
<tr><td>{$row.name|escape:"htmlall"}</td><td>{$row.shortname|escape:"htmlall"}</td><td>{$row.cid}</td><td>{$row.dbname|escape:"htmlall"}</td><td>{$row.dbsname|escape:"htmlall"}</td></tr>
{/foreach}
</table>
{/if}

{if $oldtownsname}
<h3>Towns aleady imported (same short name)</h3>
<table>
<tr><th>Name</th><th>Short Name</th><th>Comm. Id</th><th>Name (old)</th><th>Short Name (old)</th><th>Comm. Id (old)</th></tr>
{foreach key=key item=row from=$oldtownsname name=loop}
<tr><td>{$row.name|escape:"htmlall"}</td><td>{$row.shortname|escape:"htmlall"}</td><td>{$row.cid}</td><td>{$row.dbname|escape:"htmlall"}</td><td>{$row.dbsname|escape:"htmlall"}</td><td>{$row.dbcid}</td></tr>
{/foreach}
</table>
{/if}

{if $towns}
<h3>Towns</h3>
<table>
<tr><th>Name</th><th>Short Name</th><th>Comm. Id</th><th>SQL</th></tr>
{foreach key=key item=row from=$towns name=loop}
<tr><td>{$row.name|escape:"htmlall"}</td><td>{$row.shortname|escape:"htmlall"}</td><td>{$row.cid}</td><td>{$row.sql|escape:"htmlall"}</td></tr>
{/foreach}
</table>
{/if}

{/if}

{/dynamic}
{include file="_std_end.tpl"}
