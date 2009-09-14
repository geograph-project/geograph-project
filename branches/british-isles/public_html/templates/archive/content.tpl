{assign var="page_title" value=$title}
{include file="_std_begin.tpl"}

<div class="interestBox">
<h2 style="margin-bottom:0">Collections and Articles</h2>
</div>
<h3 style="margin-bottom:0px; padding:5px; margin-top:0px; background-color:black; color:white">{$title|escape:"html"}</h3>

<ul class="content">
{foreach from=$list item=item}


	<li>
	<b><a href="{$item.url}" target="_top">{$item.title}</a></b><br/>
	<small><small style="color:gray">{if $item.source != 'article' && $item.source != 'help' && $item.source != 'other'}started{/if} by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC" target="_top">{$item.realname}</a>{if $item.posts_count}, with {$item.posts_count} posts{/if}{if $item.words}, with {$item.words} words{/if}{if $item.images}, {$item.images} images{/if}{if $item.views} and viewed {$item.views} times{/if}.</small></small>
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

<br/><br/>


{include file="_std_end.tpl"}
