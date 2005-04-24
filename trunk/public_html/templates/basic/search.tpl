{include file="_std_begin.tpl"}
{dynamic}

<h2>Search for Photographs</h2>

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
<ul>
<li>
<form method="get" action="/search.php">
<div id="searchfield"><label for="searchterm">Search</label> 
<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
<input id="searchbutton" type="submit" name="go" value="Find"/> [<a href="/search.php?form=advanced">advanced search</a>]

<br/><br/>
<small>Enter a Placename, Postcode, Grid Reference, or a text search</small></div>
</form>
</li>
<li>A <a title="Photograph Listing" href="/list.php">complete listing of all photographs</a> is available.<br/><br/></li> 



<li>Or you can view a <a title="choose a photograph" href="browse.php">particular grid square</a>.<br/><br/></li>

<li>You may also wish to <a href="/submit.php">submit your pictures</a>.   
{if !$user->registered}
	<i>Note that you will be asked to login when you visit the
	submit page - please <a title="Register to create account" href="/register.php">register</a> if you haven't 
	already done so.</i>
{/if}
</li> 
</ul>

{/dynamic}    
{include file="_std_end.tpl"}
