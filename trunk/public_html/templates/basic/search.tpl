{include file="_std_begin.tpl"}

<h2>Search</h2>

<p>At the moment, you can only search by grid reference, e.g. TL0123</p>

<form method="get" action="/search.php">
<div id="searchfield"><label for="searchterm">Search</label> <input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10"/>
<input id="searchbutton" type="submit" name="go" value="Find"/></div>
</form>


    
{include file="_std_end.tpl"}
