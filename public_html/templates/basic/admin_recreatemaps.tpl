{include file="_std_begin.tpl"}
{dynamic}

<h2>Recreate Maps</h2>
<p>This is an advanced administrative tool for recreating the maps 
that have recently expired.</p>

<h3>Current Map Queue</h3>
{if $invalid_maps > 0}
<p>There are currently <b>{$invalid_maps}</b> maps waiting.</p>

<form method="post" action="recreatemaps.php">

Number of Maps to Process: <input type="text" name="limit" value="10" size="12"/>


<input type="submit" name="go" value="Recreate Maps"/>

</form>
{else}
<p>There are currently <b>0</b> maps waiting.</p>
{/if}

<h3>Add to Map Queue</h3>

<form method="post" action="recreatemaps.php">

<p>You can use the following form to immediately invalidate the maps for a particular gridsquare and optionally a user.</p>

<p>{$errormsg}</p>

Invalidate Grid Square(s): <input type="text" name="gridref" value="" size="30"/><br/>
Geograph User ID: <input type="text" name="user_id" value="" size="5"/>
Inc Basemap: <input type="checkbox" name="base" value="on"/>

<input type="submit" name="inv" value="Invalidate"/>

</form>

{/dynamic}    

{if $is_admin}
<h3>Clear all Maps (seldom used)</h3>

<p>Delete &amp; Invalidate All Maps <tt>?deleteInvalidateAll=1</tt><br/>
Invalidate All Maps <tt>?invalidateAll=1</tt><br/>
Clear Cache <tt>?expireAll=0</tt><br/>
(clear basemaps too) <tt>?expireAll=1</tt></p>

<p>Non clickable to help prevent accidental clicking.</p>
{/if}

{include file="_std_end.tpl"}
