{assign var="page_title" value="Geograph $what"}
{assign var="meta_description" value="A listing of all $what by nickname from Geograph Channel Islands"}
{include file="_std_begin.tpl"}

{if $where}
<div style="float:right">Switch to <a href="/credits.php?where={$where}{if $whenname}&amp;when={$when}/{/if}">list version</a></div>
{else}
<div style="float:right">Switch to <a href="/credits/{if $whenname}{$when}/{/if}">list version</a> or <a href="/statistics/breakdown.php?by=user{if $whenname}&amp;when={$when}{/if}">statistics version</a>.</div>
{/if}

<h2>Geograph Channel Islands {$what} by Nickname</h2>
{if $whenname}
	<h3>Submitting images March 2005 though {$whenname}</h3>
{/if}
{if $where}
	<h3>Submitting in {$where} Myriad</h3>
{/if}
<ul style="font-size:0.7em"><li>The bigger the text the more images contributed</li>
<li>You can also click a nickname to see the users profile.</li>
<li>Note: This is not a complete list of contributors, as it only includes members who have set a Nickname in their profile.</li>
</ul>

<p class="wordnet" align="justify"> 
{foreach from=$users key=nick item=obj}
<a style="font-size:{$obj.size}px;" title="{$obj.realname|escape:'html'}, {$obj.images} images" href="/profile/{$obj.user_id}">{$nick|escape:'html'|replace:' ':'&middot;'}</a>
{/foreach}
</p>
 		
{include file="_std_end.tpl"}
