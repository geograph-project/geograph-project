{include file="_std_begin.tpl"}
{dynamic}

<h2>Account Merge</h2>
<p>This is an advanced administrative tool for moving the images
in one user account to another account. 
Don't do this unless you know what you are doing!
</p>


<form method="post" action="accountmerge.php">

Old User ID: <input type="text" name="from" value="{$from}"/><br />
&nbsp;&nbsp;&nbsp;Specific gridimage_id's: <input type="text" name="ids"><br/>
New User ID: <input type="text" name="to" value="{$to}"/><br />

<input type="submit" name="go" value="Move Images from Old to New account">

</form>
{/dynamic}    
{include file="_std_end.tpl"}
