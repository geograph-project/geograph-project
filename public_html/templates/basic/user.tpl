{assign var="page_title" value="Geograph Contributors"}
{include file="_std_begin.tpl"}

{if $where}
<div style="float:right">Switch to <a href="/credits.php?where={$where}{if $whenname}&amp;when={$when}/{/if}">list version</a></div>
{else}
<div style="float:right">Switch to <a href="/credits/{if $whenname}{$when}/{/if}">list version</a> or <a href="/statistics/breakdown.php?by=user{if $whenname}&amp;when={$when}{/if}">statistics version</a>.</div>
{/if}

<h2>Geograph British Isles Nicknames</h2>
{if $whenname}
	<h3>Submitting images March 2005 though {$whenname}</h3>
{/if}
{if $where}
	<h3>Submitting in {$where} Myriad</h3>
{/if}
<ul style="font-size:0.7em"><li>The bigger the text the more images contributed</li>
<li>You can also click a nickname to see the users profile.</li></ul>

<p class="wordnet" align="justify"> 
{foreach from=$users key=nick item=obj}
<a style="font-size:{$obj.size}px;" title="{$obj.realname}, {$obj.images} images" href="/profile/{$obj.user_id}">{$nick|replace:' ':'&middot;'}</a>
{/foreach}
</p>
 		
{include file="_std_end.tpl"}
