{include file="_std_begin.tpl"}
{dynamic}

<h2>Hash Changer</h2>
<p>This is an advanced administrative tool for changing the 
photo_hashing_secret on a live database - all image files 
will be renamed - don't do this unless you know what you are
doing.
</p>


<form method="post" action="hashchanger.php">

Old photo_hashing_secret: <input type="text" name="from" value="{$from}"/><br />
New photo_hashing_secret: <input type="text" name="to" value="{$to}"/><br />

<input type="submit" name="go" value="Rename Images">

</form>
{/dynamic}    
{include file="_std_end.tpl"}
