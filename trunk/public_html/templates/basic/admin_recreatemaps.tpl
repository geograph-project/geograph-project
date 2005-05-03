{include file="_std_begin.tpl"}
{dynamic}

<h2>Recreate Maps</h2>
<p>This is an advanced administrative tool for recreating the maps 
that have recently expired.</p>

<p>There are currently <b>{$invalid_maps}</b> maps waiting.</p>

{if $invalid_maps > 0}
<form method="post" action="recreatemaps.php">

Number of Maps to Process: <input type="text" name="limit" value="10" size=3/>


<input type="submit" name="go" value="Recreate Maps"/>

</form>{/if}
{/dynamic}    
{include file="_std_end.tpl"}
