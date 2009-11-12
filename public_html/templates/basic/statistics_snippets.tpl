{assign var="page_title" value="Shared Description Statistics"}
{include file="_std_begin.tpl"}

<h2>Statistics :: Shared Descriptions</h2>

<h3>Overview</h3>
 
 <p>So far we have {$snippets.snippets|thousends} 'Shared Descriptions', for {$snippets.squares|thousends} different squares; contributed by {$snippets.users|thousends} users, over the course of {$snippets.days|thousends} days.</p>
 
 <p>These are used on {$gridimage_snippets.images|thousends} images; attached by {$snippets.users|thousends} different users, over the course of {$snippets.days|thousends} days.</p>

<h3>Some of the biggest collections</h3>

{foreach from=$results item=item}
	
	<div style="border-top:1px solid gray;padding-top:2px;padding-bottom:8px">

		<b><a href="/snippet.php?id={$item.snippet_id}">{$item.title|escape:'html'|default:'Untitled'}</a></b> {if $item.grid_reference}<small> :: {$item.grid_reference}</small>{/if}<br/>
		<div style="font-size:0.7em;padding-top:2px">{$item.comment|escape:'html'|truncate:180:"... (<u>more</u>)"}</div>
		<div style="font-size:0.7em;color:gray;margin-left:10px;">
		
		By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
		
		Used on {$item.images|thousends} <a href="/snippet.php?id={$item.snippet_id}" class="hidelink">images</a></div>
	</div>

{foreachelse}
	<p><i>no shared descriptions found</i></p>
{/foreach}

<h3>and a sample of recent collections <a href="/snippet-syndicator.php" class="xml-rss">RSS</a></h3>

{foreach from=$results2 item=item}
	
	<div style="border-top:1px solid gray;padding-top:2px;padding-bottom:8px">

		<b><a href="/snippet.php?id={$item.snippet_id}">{$item.title|escape:'html'|default:'Untitled'}</a></b> {if $item.grid_reference}<small> :: {$item.grid_reference}</small>{/if}<br/>
		<div style="font-size:0.7em;padding-top:2px">{$item.comment|escape:'html'|truncate:180:"... (<u>more</u>)"}</div>
		<div style="font-size:0.7em;color:gray;margin-left:10px;">
		
		By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
		
		Used on {$item.images|thousends} <a href="/snippet.php?id={$item.snippet_id}" class="hidelink">images</a></div>
	</div>

{foreachelse}
	<p><i>no shared descriptions found</i></p>
{/foreach}

{include file="_std_end.tpl"}
