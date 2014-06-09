{assign var="page_title" value="Search Engine to Use"}
{include file="_std_begin.tpl"}

<h2>Search Engine to Use</h2>

<p>Use this page to configure which search engine your query is forwarded to when you enter something in the "search images..." top right of site pages. <br/><br/>

<i>N.B. on Discussion Forum pages, that will always forward direct to the Discussion Search.</i></p>

{dynamic}
{if $optset}
	<div class="interestBox" style="margin:20px"><b>Option Saved</b> - Try it out now, by entering a query top right right now!</div>
{/if}
{/dynamic}


<form method="get">
<table>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/results/1522" align="left"/>
	</td>
	<td>
		<h3>Standard Search</h3>

		The normal site image search engine. 

		<a href="http://www.geograph.org.uk/results/1522">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'default'}
			<b><i>This is your current selection</i></b>
		{else}
			<input type="submit" name="submit[default]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/of/bridge" align="left"/>
	</td>
	<td>
		<h3>"Photos of" Search</h3>

		Simplified Search Interface, great for quick searching.

		<a href="http://www.geograph.org.uk/of/bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'of.php'}
			<b><i>This is your current selection</i></b>
		{else}
			<input type="submit" name="submit[of.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/browser/#!/start" align="left"/>
	</td>
	<td>
		<h3>Browser</h3>

		The new browsing interfece that combines browsing, filtering, searching, and even a map all into one interface.

		<a href="http://www.geograph.org.uk/browser/#!/q=bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'browser'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[browser]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/finder/multi2.php?q=bridge" align="left"/>
	</td>
	<td>
		<h3>Multi-Search</h3>

		Tries your search against a number of different systems, and presents all results together. 
		
		<a href="http://www.geograph.org.uk/finder/multi2.php?q=bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'multi2.php'}
			<b><i>This is your current selection</i></b>
		{else}
        	        <input type="submit" name="submit[multi2.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/finder/multi.php?q=bridge" align="left"/>
	</td>
	<td>
		<h3>Multi-Search (old)</h3>

                Tries your search against a number of different systems, and presents all results together - older version, with multiple frames.

		<a href="http://www.geograph.org.uk/finder/multi.php?q=bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'multi.php'}
			<b><i>This is your current selection</i></b>
		{else}
                	<input type="submit" name="submit[multi.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<!--tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/full-text.php?q=bridge" align="left"/>
	</td>
	<td>
		<h3>Plain Text Search</h3>

		This was an old experimental interface used to test the keywords search engine. Very basic, and not recommened for general use. 

		<a href="http://www.geograph.org.uk/full-text.php?q=bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'full-text.php'}
			<b><i>This is your current selection</i></b>
		{else}
        	        <input type="submit" name="submit[full-text.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr-->
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/finder/bytag.php?q=hectad:NB56" align="left"/>
	</td>
	<td>
		<h3>By Tag Search</h3>

		Searches the images matching the keywords - and collates the top tags. Presenting one example image per tag. 

		<a href="http://www.geograph.org.uk/finder/bytag.php?q=hectad:NB56">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'bytag.php'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[bytag.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.geograph.org.uk/finder/sqim.php?q=bridge" align="left"/>
	</td>
	<td>
		<h3>By GridSquare Search</h3>

		Searches for gridsquares with images matching the keywords. Allows finding squares where different images match the supplied keywords. 

		<a href="http://www.geograph.org.uk/finder/sqim.php?q=bridge">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'sqim.php'}
			<b><i>This is your current selection</i></b>
		{else}
        	        <input type="submit" name="submit[sqim.php]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://images.google.co.uk/images?q=bridge%26as_q%3Dsite%3Ageograph.org.uk%26btnG%3DSearch%0A" align="left"/>
	</td>
	<td>
		<h3>via Google Images</h3>

		Uses Google Images to perform the actual search. Only returns results from <i>geograph.org.uk</i> or <i>geograph.ie</i> sites. Also Google Images has only indexed some (currently about 60% of total)

		<a href="http://images.google.co.uk/images?q=bridge&amp;as_q=site:geograph.org.uk&amp;btnG=Search">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'images.google.co.uk'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[images.google.co.uk]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.google.co.uk/search?q=bridge%20site%3Ageograph.org.uk%26btnG%3DSearch%0A" align="left"/>
	</td>
	<td>
		<h3>via Google Web Search</h3>

		Uses Google Web Search, searches all geograph pages - images, article, profiles etc. Only returns results from <i>geograph.org.uk</i> or <i>geograph.ie</i> sites.

		<a href="http://www.google.co.uk/search?q=bridge&amp;as_q=site:geograph.org.uk&amp;btnG=Search">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'www.google.co.uk'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[www.google.co.uk]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<!--tr>
	<td>
		<img src="http://pagepeeker.com/thumbs.php?size=m&amp;url=http://www.google.co.uk/search?q=bridge%20site%3Ageograph.org.uk&amp;btnG%3DSearch&amp;tbs=img:1" align="left"/>
	</td>
	<td>
		<h3>via Google Web Thumbnails</h3>

		Runs a web-search - using Googles 'Sites with images' option. Only returns results from <i>geograph.org.uk</i> or <i>geograph.ie</i> sites.
		<a href="http://www.google.co.uk/search?q=bridge&amp;as_q=site:geograph.org.uk&amp;btnG=Search&amp;tbs=img:1">View Example page</a><br/><br/>

		{dynamic}
		{if $option == 'www.google.co.uk/tbs'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[www.google.co.uk/tbs]" value="Use this as my default"/>
		{/if}
		{/dynamic}
	</td>
</tr-->
</table>
</form>

{include file="_std_end.tpl"}
