{assign var="page_title" value="Search Results`$engine->criteria->searchdesc`"|escape:"html"}
{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">
{if $engine->resultCount}

{if strpos($engine->criteria->searchdesc,'with incomplete data') === FALSE}
	{assign var="sidebarclass" value="search"}
{else}
	{assign var="sidebarclass" value="searchtext"}
{/if}

<div style="float:right;position:relative; font-size:0.9em">Sidebar for:
<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" target="_search" rel="nofollow">IE &amp; Firefox</a>, <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" rel="sidebar" rel="nofollow" title="Results">Opera</a>.
Display: 
<form action="/search.php" method="get" style="display:inline">
<input type="hidden" name="i" value="{$i}"/>
{if $engine->currentPage > 1}<input type="hidden" name="page" value="{$engine->currentPage}"/>{/if}
<select name="displayclass" size="1" onchange="this.form.submit()" style="font-size:0.9em"> 
	{html_options options=$displayclasses selected=$engine->criteria->displayclass}
</select>
<noscript>
<input type="submit" value="Update"/>
</noscript>
</form>

</div>
{/if}

{if $suggestions} 
	<div><b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href="/search.php?q={$row.query|escape:'url'}+near+{$row.gr}">{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul></div>
	<hr/>
{/if}

<h2>Search Results</h2>


<p>Your search for images<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}:

{if $engine->fullText && !strpos($engine->criteria->searchtext,':')}
	<div class="interestBox" style="border:1px solid pink;display:none; " id="show1">
		<h4>Not seeing the results you expect?</h4>
		This search is powered by the new <a href="/help/search_new">experimental Full-Text search index</a>, which in some ways is less precise than the legacy search, but often results in quicker and more relevent results. 
		{if !$engine->criteria->sphinx.no_legacy}
			You can access the <a href="/search.php?i={$i}&amp;legacy=true">old search here</a>.
		{/if}
		<br/><br/>

		{if $engine->criteria->isallsearch}
			Note: The new query engine searches the whole entry (not just the title like before), so + has no effect, to search just in the title prefix the word with "title:"<br/><br/>
		{/if}
		{if $engine->criteria->changeindefault}
			Note: The new query engine searches the whole entry (not just the title like before), to search just in the title prefix a keyword with "title:", example "title:bridge"<br/><br/>
		{/if}
		<a href="javascript:void(hide_tree(1));">close</a>
	</div>

	<div class="interestBox" style="border:1px solid pink; float:right; width:200px; position:relative; " id="hide1"><b>Not seeing the results you expect?</b>	<a href="javascript:void(show_tree(1));">expand...</a>
		
	</div>
{elseif strlen($engine->criteria->searchtext) && $engine->criteria->sphinx.impossible}
	<div style="padding:2px;border:1px solid gray; font-size:0.7em;text-align:center">You have dropped back into <a href="/help/search_new">legacy search mode</a>, the search options you have selected are not supported in the new search,<br/> you can try simplifing the choosen options to change mode.
	
	{if $engine->criteria->sphinx.no_legacy}
	<br/><br/>
		<b>However legacy is not able to support this query</b> - please <a href="/contact.php">let is know</a>.
	{elseif strpos($engine->criteria->searchtext,' ')}
	<br/><br/>
		Note: <b>The <a href="/help/search_new">text matching method</a> is different</b>. So the results might not be what you expect.
	{/if}
	</div>
{/if}

