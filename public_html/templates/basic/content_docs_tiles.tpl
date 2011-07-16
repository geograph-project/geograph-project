{assign var="page_title" value="Guides, Tutorials"}
{assign var="meta_description" value="Geograph Information, Guides and Tutorials"}
{include file="_std_begin.tpl"}


<div class="interestBox">
<h2 style="margin:0">Information, Guides and Tutorials</h2>
</div>
<div style="float:right;margin-right:20px"><a href="/content/?docs&amp;order=updated">View by last updated</a></div>


<form action="/content/" method="get">
<div class="interestBox" style="margin-top:2px;width:420px">
<lable for="qs">Search:</label>
<input type="text" name="q" id="qs" size="20" {if $q} value="{$q|escape:'html'}"{/if}/>
Scope: <select name="scope" style="width:90px">
	<option value="">All</option>
	<option value="article">Articles</option>
	<option value="gallery">Galleries</option>
	{dynamic}
	  {if $enable_forums && $user->registered}
		  <option value="themed">Themed Topics</option>
	  {/if}
	{/dynamic}
	<option value="help">Help Pages</option>
	<option value="document" selected>Information Pages</option>
</select>
<input type="hidden" name="order" value="relevance"/>
<input type="submit" value="Find"/>
</div>
</form>


{assign var="lastid" value="0"}
{foreach from=$list item=item}
	{if $lastcat != $item.category_name}
		{if $lastcat}
			<br style="clear:both"/>
		{/if}
		<h3>{$item.category_name}</h3>
	{/if}

	<div style="position:relative;width:233px;float:left; border-left: 2px solid silver; padding-left:5px;margin-left:5px; margin-bottom:20px; height:230px">
		<h4 style="margin-top: 0px;font-size:1.2em; margin-bottom:4px"><a href="{$item.url}" title="{$item.extract|default:'View Article'}" style="text-decoration:none">{$item.title|escape:'html'}</a></h4>
		{if $item.image}
			<div style="float:left;padding-right:6px;padding-bottom:2px;"><a title="{$item.image->title|escape:'html'} by {$item.image->realname} - click to view full size image" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a></div>
		{/if}

		<div style="font-size:0.8em;text-align:justify">{if $item.extract}{$item.extract|escape:'html'}{else}{$item.words|truncate:200|escape:'html'|regex_replace:'/\[\[\[(\d+)\]\]\]/':'<a href="/photo/\1">Photo</a>'}{/if}</div>
		<div style="margin-top:8px;border-top:1px solid gray">
		Posted by <a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a> <span class="nowrap">on {$item.created|date_format:"%a, %e %b"}</span>
		<a href="{$item.url}"><b>Read More...</b></a>

		</div>
	</div>

	{assign var="lastcat" value=$item.category_name}
{foreachelse}
	<li><i>There are no Articles to display at this time.</i></li>
{/foreach}

<br style="clear:both"/>

	<div class="interestBox" style="font-size:1.3em;margin-bottom:20px">Can't find what you looking for? <a href="/ask.php">Ask us</a>!</div>

{include file="_std_end.tpl"}
