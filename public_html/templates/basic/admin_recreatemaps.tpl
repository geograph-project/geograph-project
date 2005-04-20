{include file="_std_begin.tpl"}
{dynamic}

<h2>Recreate Maps</h2>
<p>This is an advanced administrative tool for recreating the maps 
that have recently expired.</p>

<p>There are currently <b>{$invalid_maps}</b> maps waiting.
{if $invalid_maps > 0}
Only the first 10 maps will be processed on each round.
{/if}
</p>

{if $invalid_maps > 0}
<form method="post" action="recreatemaps.php">



<input type="submit" name="go" value="Recreate Maps">

</form>{/if}
{/dynamic}    
{include file="_std_end.tpl"}
