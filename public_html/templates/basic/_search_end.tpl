
{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
{if $engine->resultCount}

<div class="interestBox" style="text-align:center">
<a href="/explore/searches.php?i={$i}">Feature this search!</a> <sup style="color:red">new!</sup>
&nbsp;&nbsp; | &nbsp;&nbsp;
<span id="votediv"><b>Rate this Search Result</b>: 
<a href="javascript:void(record_vote('q',{$i},1));" title="Completely Hopeless"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="1" onmouseover="star_hover(1,5)" onmouseout="star_out(5)" name="star1"/></a><a href="javascript:void(record_vote('q',{$i},2));" title="Below average"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="2" onmouseover="star_hover(2,5)" onmouseout="star_out(5)" name="star2"/></a><a href="javascript:void(record_vote('q',{$i},3));" title="So So"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="3" onmouseover="star_hover(3,5)" onmouseout="star_out(5)" name="star3"/></a><a href="javascript:void(record_vote('q',{$i},4));" title="Reasonable"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="4" onmouseover="star_hover(4,5)" onmouseout="star_out(5)" name="star4"/></a><a href="javascript:void(record_vote('q',{$i},5));" title="Excellent"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="5" onmouseover="star_hover(5,5)" onmouseout="star_out(5)" name="star5"/></a> (click one)
</span>

</div>

<p align="right" style="clear:both">View/Download: {if $engine->islimited && (!$engine->fullText || $engine->criteria->sphinx.compatible)}<a title="Breakdown for images{$engine->criteria->searchdesc|escape:"html"}" href="/statistics/breakdown.php?i={$i}">Statistics</a> {/if}<a title="Google Earth Or Google Maps Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}">as KML</a> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-geo">geo RSS</a> <a title="GPX file for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.gpx" class="xml-gpx">GPX</a></p>
{else}
<p align="right" style="clear:both"><small>Subscribe to find images submitted in future:</small> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-geo">geo RSS</a></p>
{/if}

</div>

<br style="clear:both"/>

{if $statistics} 
	<a href="javascript:void(show_tree(2));" id="hide2">Expand Word Statistics</a>
	<div style="font-size:0.8em; display:none; margin-left:20px" id="show2"><b>Word Match statistics</b>
	<ul>
	{foreach from=$statistics key=word item=row}
		<li><b>{$word}</b> <small>{$row.docs} images, {$row.hits} hits</small></li>
	{/foreach}
	</ul>
	
	<p>Note, these are the raw words sent to the query engine, which are used to form the base query. There is post-filtering to make the results match your query as closely as possible which is why these terms can seem very broad.</p>
	<a href="javascript:void(hide_tree(2));">close</a></div>

{/if}

<div class="interestBox" style="text-align:center">

<form action="/search.php" method="get" style="display:inline">
<input type="hidden" name="i" value="{$i}"/>
{if $engine->currentPage > 1}<input type="hidden" name="page" value="{$engine->currentPage}"/>{/if}
<label for="displayclass">Display Format:</label>
<select name="displayclass" id="displayclass" size="1" onchange="this.form.submit()"> 
	{html_options options=$displayclasses selected=$engine->criteria->displayclass}
</select>
{if $legacy}<input type="hidden" name="legacy" value="1"/>{/if}
<noscript>
<input type="submit" value="Update"/>
</noscript>
</form> &nbsp;&nbsp;

Background Color:
{if $maincontentclass eq "content_photowhite"}
	<b>white</b>
{else}
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=white" rel="nofollow" class="robots-nofollow robots-noindex">White</a>
{/if}/
{if $maincontentclass eq "content_photoblack"}
	<b>black</b>
{else}
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=black" rel="nofollow" class="robots-nofollow robots-noindex">Black</a>
{/if}/
{if $maincontentclass eq "content_photogray"}
	<b>grey</b>
{else}
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=gray" rel="nofollow" class="robots-nofollow robots-noindex">Grey</a>
{/if}
</div>

{include file="_std_end.tpl"}