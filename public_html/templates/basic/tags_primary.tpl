{assign var="page_title" value="Primary Categories"}
{include file="_std_begin.tpl"}


<h2><a name="top"></a>Primary Categories</h2>

{assign var="lastcat" value=""}
{foreach from=$results item=item}

	{if $lastcat != $item.grouping}
		{if $lastcat}
			</div>
		{/if}
		<div style="float:left;width:180px;border-left:1px solid silver;padding-left:20px;text-indent: -20px ;">
		<b>{$item.grouping}</b><br/>
		{assign var="lastcat" value=$item.grouping}
	{/if}
	<div>&middot; <a href="#{$item.tag|replace:' ':'_'|escape:'url'}">{$item.tag|escape:'html'}</a></div>
{/foreach}
{if $lastcat}
	</div>
	<br style="clear:both"/>
{/if}


{assign var="lastcat" value=""}
{foreach from=$results item=item}

	{if $lastcat != $item.grouping}
		{if $lastcat}
			</ol>
		{/if}
		<h1>{$item.grouping}</h1>
		<ol>
		{assign var="lastcat" value=$item.grouping}
	{/if}
	<li>
	<div class="interestBox">
	<a name="{$item.tag|replace:' ':'_'|escape:'url'}"></a>
	{if $item.resultCount > 3}
		<div style="float:right"><a href="/tags/?tag={$item.tag|escape:'url'}">View {$item.resultCount} images</a></div>
	{/if}

	<big><b>{$item.tag|escape:'html'}</b></big>

	</div>
	{if $item.description}
	<p>{$item.description|escape:'html'}</p>
	{/if}
	{foreach from=$item.images item=image}

		<div style="float:left;width:160px" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
	{foreachelse}
		{if $item.skipped}
			<div><small><i>matching images in square not checked</i></small></div>
		{else}
			<div><small><i>no images found</i></small></div>
		{/if}
	{/foreach}
	<div style="clear:left;text-align:right"><a href="#top">back to top</a></div>

	</li>
{foreachelse}
	<ol>
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>


{include file="_std_end.tpl"}
