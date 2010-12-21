	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"html"}">Submit yours now</a>]</p>
	{/if}
	{if $engine->criteria->searchclass == 'Text'}
	<form method="get" action="http://images.google.co.uk/images">
	<div style="position:relative;background-color:#dddddd;padding:10px;">
	<div>You might like to try your text search on Google:</div>
	<input type="text" name="q" value="{$searchq|escape:'html'}"/>
	<input type="hidden" name="as_q" value="site:geograph.org.uk OR site:geograph.co.uk"/>
	<input type="submit" name="btnG" value="Search Geograph using Google Image Search"/></div>
	</form>
	{/if}