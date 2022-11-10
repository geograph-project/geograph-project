{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>

{if $engine->resultCount}

	{if $engine->fullText && $engine->nextLink}
	<div class="interestBox" style="border:1px solid pink;">
		You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However, as your search is in a predictable sort order, you can <b><a href="{$engine->nextLink|escape:'html'}">Generate a new Search</a></b> that continues from approximately this page.
	</div>

	{elseif $engine->fullText && $engine->numberOfPages eq $engine->currentPage && $engine->criteria->sphinx.compatible && $engine->criteria->sphinx.compatible_order && $engine->resultCount > $engine->maxResults}
		<div class="interestBox" style="border:1px solid pink;">
			You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However your search seems to be compatible with the legacy engine. You can <a href="/search.php?i={$i}&amp;legacy=true&amp;page={$engine->currentPage+1}">view the next page in Legacy Mode</a> to continue. <b>Note, searches will be slower.</b>
		</div>

	{/if}


	View/Download: {if $engine->islimited && (!$engine->fullText || $engine->criteria->sphinx.compatible)}<a href="/stuff/searchmap.php?i={$i}">Coverage Map</a> <a title="Breakdown for images{$engine->criteria->searchdesc|escape:"html"}" href="/statistics/breakdown.php?i={$i}">Statistics</a> {/if}<a title="Google Earth Or Google Maps Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}"  class="xml-kml">KML</a> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-geo">geo RSS</a> <a title="GPX file for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.gpx" class="xml-gpx">GPX</a>

	<a href="/browser/search-redirect.php?i={$i}"><i>Try</i> opening in Browser function</a><small> (Experimental, may not work!)</small>


{elseif !$engine->error}
<p align="right" style="clear:both"><small>Subscribe to find images submitted in future:</small> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-geo">geo RSS</a></p>
{/if}

		{if $engine->fullText && $engine->criteria->searchclass != 'Special' && ($engine->criteria->displayclass=='full' || $engine->criteria->displayclass=='thumbs' || $engine->criteria->displayclass=='text' || $engine->criteria->displayclass=='excerpt')}
			<div class="interestBox">
				<form action="{$script_name}" method="get">
					<b>Quick refine this search:</b> <small style="color:gray">(images{$engine->criteria->searchdesc|escape:'html'})</small>
					<div><label for="fq">New Keywords</label>: <input type="text" name="text" id="fq" size="30"{if $engine->criteria->searchtext} value="{$engine->criteria->searchtext|escape:'html'}"{/if}/>
					<input type="submit" value="Search"/>
					<input type="hidden" name="i" value="{$i}"/>
					<input type="hidden" name="redo" value="1"/>
					({newwin href="/article/Word-Searching-on-Geograph" text="Tips"}) - all other fields unchanged<br/>
					<input type="checkbox" name="strip" id="strip" {if $engine->error}checked{/if}/> <label for="strip">Remove special characters (otherwise will be used as search syntax)</label>


					</div>
				</form>
			</div>
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

<hr>
<!--p align="center" style='background-color:purple;color:white;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:yellow' href='#' onclick="jQl.loadjQ('/js/search-feedback.js');return false">Please take this short survey</a>.</p-->

<p align="center">Found these results useful? <a href="/help/donate">Please Donate</a></p>


{if $engine->resultCount}
<div class="interestBox" style="text-align:center" data-nosnippet>
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
</form> &nbsp;&nbsp; | &nbsp;&nbsp;

{dynamic}
<form method=post style="display:inline-block">
	Background Colour: {if strpos($maincontentclass, "photowhite")}White{else}<button type=submit name=style value=white>White</button>{/if}
				 / {if strpos($maincontentclass, "photoblack")}Black{else}<button type=submit name=style value=black>Black</button>{/if}
				 / {if strpos($maincontentclass, "photogray")}Gray{else}<button type=submit name=style value=gray>Grey</button>{/if}
</div></form>
{/dynamic}

</div>
{/if}

{include file="_std_end.tpl"}
