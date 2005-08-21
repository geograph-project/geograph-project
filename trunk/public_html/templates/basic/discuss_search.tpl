{assign var="page_title" value="Grid Square Discussions Search"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Search Grid Square Discussions</h2>

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}
<ul>
<li>
<form method="get" action="/discuss/search.php">
<div id="searchfield"><label for="searchterm">Search</label> 
<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
<input id="searchbutton" type="submit" name="go" value="Find"/> 
<br/><br/>
<small>Enter a Placename, Postcode, Grid Reference</small></div>
</form>
</li>

<li>For text based search see <a href="/discuss/index.php?action=search">Forum Search</a>.</p>

<li>See also <a href="/search.php">image search</a></p>

</ul>

{/dynamic}    
{include file="_std_end.tpl"}
