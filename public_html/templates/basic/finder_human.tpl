{assign var="page_title" value="Cooperative Searching"}
{include file="_std_begin.tpl"}


	<h2><a href="/finder/">Finder</a> :: Cooperative Searching</h2>

{dynamic}
	{if $message}
		<p style="color:red">{$message|escape:"html"}</p>
	{/if}
{/dynamic}

	<div class="interestBox">
		Use this section to either:
		<ul>
			<li>Run 'searches' for images, and get 'find's by other site users that match your query</li>
			<li>'Find' matching images for searches other people are requesting</li>
			<li>... or simply just browse past searches.</li>
		</ul>
		This is a new way to locate images you aren't able to find via the <a href="/search.php">site search</a> yourself, possibly because they haven't been submitted yet! 
		
		<p>Can also use this section to record Searchs that you <i>know currently return no images</i>. Then later local photographers can use this list for inspiration when out photographing - and your search can be forfilled with a fresh image!</p>
	</div>

{dynamic}
<div style="position:relative;float:left;width:47%;margin:5px;padding:5px; border-right:2px solid gray;">
	<h3>Answered Searches</h3>
	<p>Click a title to view search results...</p>
	
	<ul>
	{foreach from=$answered item=item}
		<li style="border-top:1px solid silver;padding-top:2px;margin-top:2px">
		<a href="{$script_name}?id={$item.search_id}&amp;mode=results" title="for {$item.q|escape:'html'}{if $item.location} near '{$item.location|escape:'html'}'{/if}">
		{if $item.title}
			<b>{$item.title|escape:'html'}</b>
		{else}
			{if $item.location}<i>for</i>{/if} <b>{$item.q|escape:'html'}</b> {if $item.location}<i>near</i> <b>{$item.location|escape:'html'}</b>{/if}
		{/if}
		</a><br/>
		<small><small>
		{if $item.images}
			<span style="color:gray">{$item.images} images found</span>{/if}
		[<a href="{$script_name}?id={$item.search_id}&amp;mode=answer">Add more images</a>]
		[<a href="{$script_name}?id={$item.search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" style="color:red">Report as inappropriate</a>]
		</small></small>
		</li>
	{foreachelse}
		<li><i>There is no content to display at this time.</i></li>
	{/foreach}
	
	</ul>
</div>

<div style="position:relative;float:left;width:47%;margin:5px;padding:5px;">
	<h3>Unanswered Searches</h3>
	<p>Click a title to begin suggesting photos for the search...</p>
	
	<ul>
	{foreach from=$pending item=item}
		<li style="border-top:1px solid silver;padding-top:2px;margin-top:2px">
		<a href="{$script_name}?id={$item.search_id}&amp;mode=answer" title="for {$item.q|escape:'html'}{if $item.location} near '{$item.location|escape:'html'}'{/if}">
		{if $item.title}
			<b>{$item.title|escape:'html'}</b>
		{else}
			{if $item.location}<i>for</i>{/if} <b>{$item.q|escape:'html'}</b> {if $item.location}<i>near</i> <b>{$item.location|escape:'html'}</b>{/if}
		{/if}
		</a><br/>
		<small><small>	
		[<a href="{$script_name}?id={$item.search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" style="color:red">Report as inappropriate</a>]
		{if $item.images}
			<span style="color:gray">{$item.images} images found</span>{/if}
		</li>
		</small></small>
		</li>
	{foreachelse}
		<li><i>There is no content to display at this time.</i></li>
	{/foreach}
	
	</ul>

</div>
<br style="clear:both"/>


	
	{if $user->registered}
		<div class="interestBox">
			&middot; <a href="{$script_name}?create">Create a new search now</a>!
		</div>
	{else}
		<p>During this experimental phrase creating new searches is only available to registered site users</p>
	{/if}
{/dynamic}


<p><small>Note: If/when start getting lots of 'searches' will look at ways to make browsing the list easier.</small></p>


{include file="_std_end.tpl"}
