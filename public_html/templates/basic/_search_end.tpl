
{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
{if $engine->resultCount}

	{if $engine->fullText && $engine->nextLink}
	<div class="interestBox" style="border:1px solid pink;">
		You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However as your search is in a predictable sort order, you can <b><a href="{$engine->nextLink|escape:'html'}">Generate a new Search</a></b> that continues from this approximatly this page forward.
	</div>
	
	{elseif $engine->fullText && $engine->numberOfPages eq $engine->currentPage && $engine->criteria->sphinx.compatible && $engine->criteria->sphinx.compatible_order && $engine->resultCount > $engine->maxResults}
		<div class="interestBox" style="border:1px solid pink;">
			You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However your search seems to be compatible with the lagacy engine. You can <a href="/search.php?i={$i}&amp;legacy=true&amp;page={$engine->currentPage+1}">view the next page in Legacy Mode</a> to continue. <b>Note, searches will be slower.</b>
		</div>
		
	{elseif $engine->criteria->displayclass=='full' || $engine->criteria->displayclass=='thumbs'}	
		<div id="hidefeed2" style="text-align:center;width:400px;border:2px solid yellow; margin-left:auto;margin-right:auto">Didn't find what you looking for? <a href="javascript:void(show_tree('feed2'));"><b>Tell Us!</b></a></div>
		<div id="showfeed2" class="interestBox" style="display:none"><form method="post" action="/stuff/feedback.php">
		<label for="feedback_comments"><b>Help us improve!</b> Please explain what you are looking for...</label><br/>
		<input type="text" name="comments" size="80" id="feedback_comments"/><input type="submit" name="submit" value="send"/>
		{dynamic}{if $user->registered}<br/>
		<small>(<input type="checkbox" name="nonanon"/> <i>Tick here to include your name with this comment, so we can then reply</i>)</small>
		{else}<br/>
		<i><small>If you want a reply please use the <a href="/contact.php">Contact Us</a> page. We are <b>unable</b> to reply to comments left here.</small></i>
		{/if}{/dynamic}
		<input type="hidden" name="template" value="{$smarty_template}"/>
		<input type="hidden" name="referring_page" value="{$smarty.server.HTTP_REFERER}"/>
		    <div style="display:none">
		    <br /><br />
		    <label for="name">Leave Blank!</label><br/>   
			<input size="40" id="name" name="name" value=""/>
		    </div>
		</form></div>
		<br/>
	{/if}


{if strpos($engine->criteria->searchdesc,'with incomplete data') === FALSE}
	{assign var="sidebarclass" value="search"}
{else}
	{assign var="sidebarclass" value="searchtext"}
{/if}


<div align="right" style="clear:both">Sidebar for:
<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" target="_search" rel="nofollow">IE &amp; Firefox</a>, <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" rel="sidebar" rel="nofollow" title="Results">Opera</a>.


View/Download: {if $engine->islimited && (!$engine->fullText || $engine->criteria->sphinx.compatible)}<a title="Breakdown for images{$engine->criteria->searchdesc|escape:"html"}" href="/statistics/breakdown.php?i={$i}">Statistics</a> {/if}<a title="Google Earth Or Google Maps Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}">as KML</a> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-geo">geo RSS</a> <a title="GPX file for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.gpx" class="xml-gpx">GPX</a></div>
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
</form> &nbsp;&nbsp; | &nbsp;&nbsp;

Background Color: [
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=white" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photowhite"} hidelink{/if}{/dynamic}">White</a>
/
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=black" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photoblack"} hidelink{/if}{/dynamic}">Black</a>
/
	<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;style=gray" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photogray"} hidelink{/if}{/dynamic}">Grey</a>
 ]
</div>

{include file="_std_end.tpl"}