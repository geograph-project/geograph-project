{assign var="page_title" value="Shared Description Statistics"}
{include file="_std_begin.tpl"}

<h2>Basic Statistics for Shared Descriptions</h2>

<h3>Overview</h3>
 
 <p>So far we have <b>{$snippets.snippets|thousends}</b> {external href="/article/Shared-Descriptions" text="Shared Descriptions" title="read more about shared descriptions in our documentation section"}, for <b>{$snippets.squares|thousends}</b> different squares; contributed by <b>{$snippets.users|thousends}</b> users, over the course of <b>{$snippets.days|thousends}</b> days.</p>
 
 <p>These are used on <b>{$gridimage_snippets.images|thousends}</b> images; attached by <b>{$snippets.users|thousends}</b> different users, over the course of <b>{$snippets.days|thousends}</b> days.</p>


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


{include file="_std_end.tpl"}
