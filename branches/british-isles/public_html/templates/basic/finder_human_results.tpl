{assign var="page_title" value="Cooperative Searching"}
{include file="_std_begin.tpl"}


	<div style="float:right;position:relative;text-align:center">
		[<a href="{$script_name}" target="_top">Back to Search List</a>]
		{if $created}<br/><small class="nowrap">[<a href="{$script_name}?id={$search_id}&amp;mode=report" onclick="return confirm('Are you sure?');" rel="nofollow" target="_top" style="color:red">Report as inappropriate</a>]</small>{/if}
	</div>

	<h2><a href="/finder/">Finder</a> :: <a href="{$script_name}">Cooperative Search</a> Results</h2>


{if $created}
	<div class="interestBox">
	<a href="/profile/{$user_id}" target="mainframe">{$realname|escape:'html'}</a>, said: I am looking 
	
	<span style="border:1px solid silver;padding:2px"><i>for</i> <b>{$q|escape:'html'}</b>{if $location} <i>near</i> <b>{$location|escape:'html'}</b>{/if}</span>, 
	can you help?<br/>
	
	{if $comment}
		<small>Comments: {$comment|escape:'html'}</small>
	{/if}
	</div>

	{if $results}
		<p>These are the {if $images > 50}latest 50 of the{/if}<b>{$images|thousends}</b> images other users have found to answer this search...</p>
		
		
		{foreach from=$results item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">
			  <div style="float:right; position:relative;font-size:0.7em;text-align:right">
			  	Suggested by <a href="/profile/{$image->finder_id|escape:'html'}" class="nowrap">{$image->finder|escape:'html'}</a><br/>
			  	using <a href="/search.php?i={$image->query_id}&amp;page={$image->page}&amp;temp_displayclass=excerpt">this search</a><br/>
			  	<span style="color:silver">{$image->created}</span><br/>
			  	[<a href="{$script_name}?gid={$image->gridimage_id}&amp;id={$search_id}&amp;mode=report" onclick="{literal}if (confirm('Are you sure?')) { document.getElementById('result{/literal}{$image->gridimage_id}{literal}').style.display='none';return true;} else {return false;}{/literal}" rel="nofollow" style="color:red">Report as inappropriate</a>]
			  </div>
			  <div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			  </div>
			  <div style="float:left; position:relative; ">
				<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
				by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
				{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				<br/>
				
				{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
				{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

				{if $image->comment}
				<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
				{/if}
			  </div><br style="clear:both;"/>
			 </div>
		{/foreach}
		
		<div class="interestBox" style="text-align:center">
			<a href="{$script_name}?id={$search_id}&amp;mode=export">View as Search Results</a> {if $images > 50}(Displays upto about 500 images){/if}
		</div>
	{/if}
	
	
	

{else}
	<p>Unable to load this search. a href="{$script_name}" target="_top">Back to Search List</a></p>
{/if}


<hr/>

<p>Note: This is an experimental feature, its subject to change, or might disappear if doesnt seem worth maintaining. If/when start getting lots of 'searches' will look at ways to make browsing the list easier.</p>


{include file="_std_end.tpl"}
