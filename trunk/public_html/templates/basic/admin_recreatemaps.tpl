{include file="_std_begin.tpl"}
{dynamic}

<h2>Recreate Maps</h2>
<p>This is an advanced administrative tool for recreating the maps 
that have recently expired.</p>


{if $invalid_maps > 0}
<p>There are currently <b>{$invalid_maps}</b> maps waiting.</p>

<form method="post" action="recreatemaps.php">

Number of Maps to Process: <input type="text" name="limit" value="10" size="3"/>


<input type="submit" name="go" value="Recreate Maps"/>

</form>
{else}
<p>There are currently <b>0</b> maps waiting.</p>
{/if}

or 

<form method="post" action="recreatemaps.php">

<p>You can use the following form to immediately invalidate the maps for a particular gridsquare.</p>

<p>{$errormsg}</p>

Invalidate Grid Square: <input type="text" name="gridref" value="" size="10"/>

<input type="submit" name="inv" value="Invalidate"/>

</form>

{/dynamic}    

<p><a href="recreatemaps.php?deleteInvalidateAll=1">Delete & Invalidate All Maps</a>
<a href="recreatemaps.php?invalidateAll=1">Invalidate All Maps</a>
<a href="recreatemaps.php?expireAll=0">Clear Cache</a>
<a href="recreatemaps.php?expireAll=1">(clear basemaps too)</a></p>


{include file="_std_end.tpl"}
