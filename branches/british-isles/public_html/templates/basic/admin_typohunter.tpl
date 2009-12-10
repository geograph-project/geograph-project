{assign var="page_title" value="Typo Hunter"}
{include file="_std_begin.tpl"}

<h2><a href="/admin/typolist.php">Typos</a> :: Typo Hunter v0.5 {if $criteria}<small style="font-weight:normal">, submitted at or before: {$criteria|escape:'html'}</small>{/if}</h2>
	
<div class="interestBox">
	<form action="{$script_name}" method="get">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" /> | 
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude" />
		<input type="submit" value="Find" /><br/>
		<label for="size">Number of images to search:</label> <select name="size" id="size">{html_options options=$sizes selected=$size}</select> | 
		<input type="checkbox" name="title" {if $title} checked="checked"{/if} id="title" /> <label for="title">Search <b>title</b> as well as description</label> 
		
	</form>
</div>
	
	
	<br/>
	
	{foreach from=$images item=image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="display:inline">
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a>
		<br/>
		[[<a href="/photo/{$image->gridimage_id}">{$image->gridimage_id}</a>]] for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $image->realname} by <a title="view user profile" href="/profile/{$user->user_id}">{$image->realname}</a>{/if}<br/>
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		<div>{if $image->comment}<textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea>{/if}<input type="submit" name="create" value="continue &gt;"/>
		</div>
	  </div><br style="clear:both;"/>
	  </form><br/>
	 </div>
	{foreachelse}
		{if $include}
			<i>No results</i>
		{/if}
		<ul>
			<li>Only searches the <b>most recent and moderated</b> images<br/><br/></li>
			<li>Include/Exclude boxes accept a <b>single exact search string</b>, including special charactors; matches part words. (but not case sensitive)<br/><br/></li>
			<li>By default looks for in the description <b>only</b>, as that is the most useful for typo hunting. But can also search the title, but please only do that when needed<br/><br/></li>
			<li>Searches are automatically recorded so can be rerun easily. <a href="/admin/typolist.php">View results here</a><br/><br/></li>
		</ul>
	{/foreach}
	
	<br/><br/>
	
	{if $image_count}
		<p>Showing {$image_count} image(s), there might be more.</p>
	{/if}

<br/><br/>
{if $next}
<div class="interestBox">Navigation: <b>|
	<a href="{$script_name}?next={$next|escape:'url'}">Next</a> |
</b>
</div>
{/if}

<p><small>Note: Only searches the last {$size} images and only includes moderated images.<br/>
Page generated at 1 hour intervals, please don't refresh more often than that.</small></p> 


{include file="_std_end.tpl"}
