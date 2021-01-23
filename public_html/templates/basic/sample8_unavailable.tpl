{include file="_std_begin.tpl"}

<h2>Sorry, this search is temporarily unavailable</h2>

<p>We are performing routine maintainance. Please come back after 6pm.

<h3>Image Search</h3>

<p>However may still be able to use the <a href="/search.php">original search</a> to search <b>images</b>.

{dynamic}
{if $q}

<form method=get action="/search.php"> 
keywords <input type=search name=q value="{$q|escape:'html'}"> 
<input type=submit>
<input type=hidden name=redir value=false>
</form>
{/if}

{/dynamic}

{include file="_std_end.tpl"}
