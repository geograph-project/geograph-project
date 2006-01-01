{include file="_std_begin.tpl"}
{dynamic}
<h2>Sorry, search is temporarily unavailable</h2>

<p>During busy periods we limit the availability of search to
ensure the site remains responsive - please try again shortly.</p>

<p>In the meantime, why not try using Google to search this site...</p>

<form method="get" action="http://images.google.co.uk/images">
<div>
<input type="text" name="q" value="{$searchq|escape:'html'}"/>
<input type="hidden" name="as_q" value="site:geograph.org.uk OR site:geograph.co.uk"/>
<input type="submit" name="btnG" value="Search Geograph using Google Image Search"/></div>
</form>

<form method="get" action="http://www.google.co.uk/search">
<div>
<input type="text" name="q" value="{$searchq|escape:'html'}"/>
<input type="hidden" name="as_q" value="site:geograph.org.uk OR site:geograph.co.uk"/>
<input type="submit" name="btnG" value="Search Geograph using Google Web Search"/></div>
</form>

{/dynamic}
{include file="_std_end.tpl"}
