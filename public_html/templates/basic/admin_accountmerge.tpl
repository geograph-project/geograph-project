{include file="_std_begin.tpl"}
{dynamic}

<h2>Account Merge</h2>
<p>This is an advanced administrative tool for moving the images
in one user account to another account. 
Don't do this unless you know what you are doing!
</p>


<form method="post" action="accountmerge.php">

Old User ID: <input type="text" name="from" value="{$from}"/><br />
New User ID: <input type="text" name="to" value="{$to}"/><br /><br>

&nbsp;&nbsp;&nbsp;Limit: <input type="text" name="limit" value=1000 size=5><br/><br>

or &nbsp;&nbsp;&nbsp;Image ID(s): <input type="text" name="ids" value="" size=50 maxlength=4000/><br /><br>


&nbsp;&nbsp;&nbsp;<input type=checkbox name=real value=1>Execute for real (otherwise just does dry run!) <br><br>

<input type="submit" name="go" value="Move Images from Old to New account">

</form>
{/dynamic}    
{include file="_std_end.tpl"}
