{assign var="page_title" value="Search"}
{assign var="right_block" value="_block_recent.tpl"}
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

<li>Or you can browse a <a title="choose a photograph" href="browse.php">particular grid square</a>.<br/><br/></li>

<li>Registered users can also <a href="/discuss/index.php?action=search">search the forum</a>.</p>

</ul>

{/dynamic}    
{include file="_std_end.tpl"}
