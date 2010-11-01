{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}

<h2><a href="?">Canonical Category Mapping</a> :: Tree</h2>
	
	<p>{$intro}</p>

	{if $list}
		{assign var="last" value=""}

		{foreach from=$list item=item}
			{if $last != $item.canonical}
				{if $last}
					</div>
				{/if}
				{assign var="last" value=$item.canonical}
				<h3><a href="/search.php?canonical={$item.canonical|escape:"url"}&amp;do=1">{$item.canonical|escape:"html"}</a></h3>
				<div>&middot; 
			{/if}
			<a href="/search.php?imageclass={$item.imageclass|escape:"url"}" class="nowrap">{$item.imageclass|escape:"html"}</a> &middot; 
		{/foreach}
		{if $last}
			</div>
		{/if}
	{else}
		- nothing to show -
	{/if}

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
