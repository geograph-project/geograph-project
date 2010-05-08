{assign var="page_title" value="Human powered search"}
{include file="_basic_begin.tpl"}
<style type="text/css">{literal}
	body,html {
		margin:0;
		padding:0;
	}
{/literal}</style>
{dynamic}
<div class="interestBox" style="padding:4px;height:92px">
	<div style="float:right;position:relative;text-align:center">
		[<a href="{$script_name}" target="_top">Back to Search List</a>]
		{if $created}<br/><small class="nowrap">[<a href="{$script_name}?id={$search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" target="_top" style="color:red">Report as inappropriate</a>]</small>{/if}
	</div>
{if $created}
	<a href="/profile/{$user_id}" target="mainframe">{$realname|escape:'html'}</a>, said: I am looking 
	
	<span style="border:1px solid silver;padding:2px"><i>for</i> <b>{$q|escape:'html'}</b>{if $location} <i>near</i> <b>{$location|escape:'html'}</b>{/if}</span>, 
	can you help?<br/>
	
	{if $comment}
		<small>Comments: {$comment|escape:'html'}</small>
	{/if}
	<br/>
	<form method="get" action="/search.php" target="mainframe" style="display:inline">
	
		Your search: 
			<label for="searchq" style="line-height:1.8em"><b>For</b>:</label>
			<input id="searchq" type="text" name="q" value="{$searchtext|escape:"html"}" size="15"/> 

			<label for="searchlocation" style="line-height:1.8em">near</b>:</label> 
			<input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"}" size="10"/>
				<input id="searchgo" type="submit" value="Search"/>
		
		| <a href="/search.php?form=text" target="mainframe">Advanced Search Form</a>	
		| <a href="/search.php" target="mainframe">Simple Search Form</a><br/>
		<small><small>Click the "Suggest this image" against images that may interest the searcher.</small></small>
		
	</form>
	
{else}
	<p>Unable to load this search. a href="{$script_name}" target="_top">Back to Search List</a></p>
{/if}
</div>
{/dynamic}
</body>
</html>


