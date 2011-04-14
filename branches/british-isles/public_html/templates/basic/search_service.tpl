{assign var="page_title" value="Search Results"}
{include file="_basic_begin.tpl"}
{if $inner || $feedback}
	<br/>
{else}
<div style="text-align:right">
	<a href="/search.php?q={$searchq|escape:"url"}" target="_parent">Run this query in the Full Search</a>
</div>
{/if}

<div id="maincontent">
{if $suggestions}
	<b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href='search-service.php?q={$row.gr}+{$row.query}'>{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul>
{/if}

{if $images}
	<div>
	{foreach from=$images item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{/foreach}
	<br style="clear:both"/>
	</div>

{else}
	{include file="_search_noresults.tpl"}
{/if}

	{if $query_info}
	<p style="clear:both">{$query_info}</p>
	{/if}
{if $feedback}
	<hr/>
	<b>Rate this set of results!</b><br/>
	<form method="get" action="/finder/modes.php?sdf=sdf" target="resultsframe" onsubmit="{literal}that=this;setTimeout(function() {that.innerHTML='Thanks!'},1000);return true;{/literal}">
		<label for="rating">Rating</label><br/>
		<select name="rating">
			<option value="10">10 - Excellent</option>
			<option value="9">9</option>
			<option value="8">8 - Reasonable</option>
			<option value="7">7</option>
			<option value="6">6</option>
			<option value="5" selected>5 - So So</option>
			<option value="4">2</option>
			<option value="3">3 - Below average</option>
			<option value="2">2</option>
			<option value="1">1 - Hmm</option>
		</select><br/><br/>
		<input type="hidden" name="mode" value="{$mode|escape:'html'}"/>
		<input type="hidden" name="q" value="{$q|escape:'html'}"/>
		<label for="comment"><i>Optional</i> comment</label><br/>
		<input type="text" name="comment" value="" id="comment" maxlength="160"/><br/><br/>

		<input type="submit" name="test" value="submit feedback"/>
	</form>

	<iframe src="about:blank" width="1" height="1" name="resultsframe"></iframe>
	<span style="color:white">{$mode|escape:'html'}</span>
{elseif $inner}
	<a href="/search.php?q={$searchq|escape:"url"}" target="_parent">Try this query in the Full Search</a> - Might be non functional
{/if}
</div>
</body>
</html>
