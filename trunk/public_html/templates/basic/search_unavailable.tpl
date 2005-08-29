{include file="_std_begin.tpl"}

<h2>Sorry, search is temporarily unavailable</h2>

<p>During busy periods we limit the availability of search to
ensure the site remains responsive - please try again shortly.</p>

<p>In the meantime, why not try using Google to search this site...</p>

<form method="GET" action="http://www.google.co.uk/search">
<input type="hidden" name="as_sitesearch" value="www.geograph.co.uk"/>
<input type="text" name="as_q" value="{$searchq|escape:'html'}"/>
<input type="submit" name="btnG" value="Search Geograph using Google"/>
</form>

{include file="_std_end.tpl"}
