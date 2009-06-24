{if !$inline}
{assign var="page_title" value=$title}
{include file="_basic_begin.tpl"}
{literal}<script language="JavaScript" type="text/javascript">

  if (top.location == location) {
    top.location.href = document.location.href.replace(/inner/,'');
  }

</script>{/literal}
{/if}

<h2 style="margin-bottom:0px; padding:5px; margin-top:0px; background-color:black; color:white">{$title|escape:"html"}</h2>
<div style="text-align:right;font-size:0.8em;margin-top:0px;">Order by : | {foreach from=$orders item=name key=key}
	{if $order eq $key}
		<b>{$name}</b>
	{else}
		<a href="?{$extra|replace:'order=':'old='}&amp;order={$key}">{$name}</a>
	{/if} |
{/foreach}</div>


<div style="margin-top:5px; font-size:0.6em"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	
<ul class="content">
{foreach from=$list item=item}


	<li>
	<div style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
		{if $item.image}
		<a title="{$item.image->title|escape:'html'} by {$item.image->realname} - click to view full size image" href="/photo/{$item.image->gridimage_id}" target="_top">{$item.image->getSquareThumbnail(60,60)}</a>
		{/if}
	</div>
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

<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

<br style="clear:both"/>

	{if $query_info}
	<p>{$query_info}</p>
	
	<p>to access more results simply add more keywords to refine your search, including negation by prefixing keywords with -</p>
	{/if}
	
</body>
</html>
