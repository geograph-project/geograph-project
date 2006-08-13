
{include file="_std_begin.tpl"}

<h2>Articles</h2>

<ul>
{foreach from=$list item=item}


	 <li><a title="View {$item.title}" href="/article/{$item.url}">{$item.title}</a></li>
	

{/foreach}
</ul>
		
{include file="_std_end.tpl"}
