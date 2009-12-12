{assign var="page_title" value="Search by Gridquare"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>
{/literal}

<h2><a href="/finder/">Finder</a> :: Search by Gridquare</h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
</form>

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}

<ol start="{$offset}">
{foreach from=$results item=item}
	<li>
	<div class="interestBox">
	
	{if $item.resultCount > 3}
		<div style="float:right"><a href="/search.php?gridref={$item.grid_reference}&amp;searchtext={$q|escape:'url'}&amp;do=1&amp;distance=1">View {$item.resultCount} text matches in {$item.grid_reference}</a></div>
	{elseif $item.skipped}
		<div style="float:right"><a href="/search.php?gridref={$item.grid_reference}&amp;searchtext={$q|escape:'url'}&amp;do=1&amp;distance=1">Look for text matches in {$item.grid_reference}</a></div>
	{/if}
	
	<b><a href="/gridref/{$item.grid_reference|escape:'url'}" title="{$item.imagecount} images in {$item.grid_reference}">{$item.grid_reference}</a></b> 
	
	{if $item.imagecount}
		<small style="color:green">({$item.imagecount|thousends} images)</small>
	{/if}
	{if $item.place}
		<i>near {$item.place|escape:'html'}</i>
	{/if}
	
	</div>
	
	{foreach from=$item.images item=image}
		<div style="float:left;width:160px" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
	{foreachelse}
		{if $item.skipped}
			<div><small><i>matching images in square not checked</i></small></div>
		{else}
			<div><small><i>no images found matching {$q|escape:'html'} in square</i></small></div>
		{/if}
	{/foreach}
	<br style="clear:left;"/>
	
	</li>
{foreachelse}
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>

<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example: <tt>stone wall -sheep</tt></li>
		<li>use gridsquares, hectads or myriads as keywords <tt>stone wall sh65</tt> or <tt>stone wall tq</tt></li>
		<li>find images in specific eastings and/or northings (in any myriad) <tt>easting:34 northing:24</tt> </li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>bridge river OR canal</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
