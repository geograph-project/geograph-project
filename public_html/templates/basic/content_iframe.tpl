{assign var="page_title" value="Content"}
{include file="_basic_begin.tpl"}
{literal}<style type="text/css">
h2 { padding: 5px; margin-top:0px; background-color: black; color:white}

</style>{/literal}


<h2>{$title|escape:"html"}</h2>

<ul class="content">
{foreach from=$list item=item}


	<li>
	<div style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
		{if $item.image}
		<a title="{$item.image->title|escape:'html'} by {$item.image->realname} - click to view full size image" href="/photo/{$item.image->gridimage_id}" target="_top">{$item.image->getSquareThumbnail(60,60)}</a>
		{/if}
	</div>
	<b><a href="{$item.url}" target="_top">{$item.title}</a></b><br/>
	<small><small style="color:gray">{if $item.type != 'article' && $item.type != 'help'}started{/if} by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC" target="_top">{$item.realname}</a>{if $item.posts_count}, with {$item.posts_count} posts{/if}{if $item.words}, with {$item.words} words{/if}{if $item.images}, {$item.images} images{/if}{if $item.views} and viewed {$item.views} times{/if}.</small></small>
	{if $item.extract}
		<div title="{$item.extract|escape:'html'}" style="font-size:0.7em;">{$item.extract|escape:'html'|truncate:90:"... (<u>more</u>)"}</div>
	{/if}
	<div style="clear:both"></div>
	</li>


{foreachelse}
	<li><i>There is no content to display at this time.</i></li>
{/foreach}

</ul>


<br style="clear:both"/>

</body>
</html>
