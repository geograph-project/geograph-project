{assign var="page_title" value="Human Powered Search"}
{include file="_std_begin.tpl"}


	<h2><a href="/finder/">Finder</a> :: Community Powered Search</h2>

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
		</ul>
		This is a new way to find obscure images you aren't able to find via the <a href="/search.php">site search</a> yourself, or you can use it simply to learn how to use the search. 
		
		<p>Another interesting use case: can use this section to record 'searches' that you <i>know</i> currently return no images, that local photographers can use for inspiration when they out photographing - so your search can be forfilled with a fresh image.</p>
	</div>

{dynamic}
<div style="position:relative;float:left;width:47%;margin:5px;padding:5px; border-right:2px solid gray;">
	<h3>Answered Searches</h3>

	<ul>
	{foreach from=$answered item=item}
		<li style="border-top:1px solid silver;padding-top:2px;margin-top:2px">
		<b><a href="{$script_name}?id={$item.search_id}&amp;mode=results" title="for {$item.q|escape:'html'}{if $item.location} near '{$item.location|escape:'html'}'{/if}">
		{if $item.title}
			<b>{$item.title|escape:'html'}</b>
		{else}
			<i>for</i> <b>{$item.q|escape:'html'}</b> {if $item.location}<i>near</i> <b>{$item.location|escape:'html'}</b>{/if}
		{/if}
		</a></b><br/>
		<small><small>
		{if $item.images}
			<span style="color:gray">{$item.images} images found</span>{/if}
		[<a href="{$script_name}?id={$item.search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" style="color:red">Report as inappropriate</a>]
		[<a href="{$script_name}?id={$item.search_id}&amp;mode=answer">Add more images</a>]
		</small></small>
		</li>
	{foreachelse}
		<li><i>There is no content to display at this time.</i></li>
	{/foreach}
	
	</ul>
</div>

<div style="position:relative;float:left;width:47%;margin:5px;padding:5px;">
	<h3>Unanswered Searches</h3>
	<p>Click a title to begin looking...</p>
	
	<ul>
	{foreach from=$pending item=item}
		<li style="border-top:1px solid silver;padding-top:2px;margin-top:2px">
		<a href="{$script_name}?id={$item.search_id}&amp;mode=answer" title="for {$item.q|escape:'html'}{if $item.location} near '{$item.location|escape:'html'}'{/if}">
		{if $item.title}
			<b>{$item.title|escape:'html'}</b>
		{else}
			<i>for</i> <b>{$item.q|escape:'html'}</b> {if $item.location}<i>near</i> <b>{$item.location|escape:'html'}</b>{/if}
		{/if}
		</a></b><br/>
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
{/dynamic}
<br style="clear:both"/>


	<h3 style="border-top:2px solid gray;padding-top:10px">or run your own search now...</h3>

	{if $user->registered}
		<form method="post" action="{$script_name}">
			<small style="font-size:0.7em">You have tried looking for this in the normal <a href="/search.php">image search</a> haven't you?</small>
			<div style="position:relative;" class="interestBox">

				<label for="searchq" style="line-height:1.8em"><b>For</b>:</label>
				<input id="searchq" type="text" name="q" value="{$searchtext|escape:"html"}" size="50" style="font-size:1.3em"/> 
				<br/>
				<label for="searchlocation" style="line-height:1.8em">near</b>:</label> 
				<input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"}" size="30" style="font-size:1.3em"/>&nbsp;&nbsp;&nbsp;
					<input id="searchgo" type="submit" name="create" value="Create Search..." style="font-size:1.3em"/>
				<br/>
				<input type="checkbox" name="notify" id="notify" value="1" checked> <label for="notify">Email me when someone 'Finds' an image (not currently functional - no emails will be sent)</label>
				<br/><br/>
				<label for="title">Optional short title for this search</label><br/>
				<input type="text" id="title" name="title" value="{$title|escape:"html"}" size="60"/><br/>
				<label for="comment">Optional comments - to help finders...</label><br/>
				<textarea name="comment" id="comment" rows="4" cols="50"></textarea>
			</div>
		</form>
		(Note: The search is recorded with your profile name, and shown to others. All searches are public - so keep it clean)
	{else}
		<p>During this experimental phrase creating new searches is only available to registered site users</p>
	{/if}


<p>Note: This is an experimental feature, its subject to change, or might disappear if doesnt seem worth maintaining. If/when start getting lots of 'searches' will look at ways to make browsing the list easier.</p>


{include file="_std_end.tpl"}
