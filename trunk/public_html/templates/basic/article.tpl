
{include file="_std_begin.tpl"}

<h2>Articles</h2>

<ul>
{foreach from=$list item=item}


	 <li><a title="View {$item.title}" href="/article/{$item.url}">{$item.title}</a> {dynamic}{if $isadmin || $item.user_id == $user->user_id}<a title="Edit {$item.title}" href="/article/edit.php?page={$item.url}">edit</a>{/if}{/dynamic}</li>
	

{/foreach}
</ul>

{if $user->registered} 
<a href="/article/edit.php?page=new">Summit a new Article</a>
{/if}
		
{include file="_std_end.tpl"}
