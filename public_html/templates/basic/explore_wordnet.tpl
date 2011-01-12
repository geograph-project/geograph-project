{assign var="page_title" value="Popular Phrases"}
{include file="_std_begin.tpl"}

<h2>Popular Phrases {if $words}related to <i>{$words}</i>
<span style="font-size:0.7em">
[<a href="{$script_name}?{$extra_link}">remove filter</a>]</span>
{/if}</h2>

{if $u}
<ul>
<li>from images by <a href="/profile/{$u}">{$profile->realname}</a> <span style="font-size:0.7em">
[<a href="{$script_name}?len={$len}{if $words}&amp;words={$words|escape:url}{/if}">remove filter</a>]</span></li>
</ul>
{/if}

{if $words}
<p><a href="/search.php?textsearch=^{$words|escape:url}&amp;do=1{if $u}&amp;user_id={$u}{/if}">Search for images containing <b>{$words}</b></a></p>
{/if}

<p>Here are the most common phrases used within the titles of submitted images.</p>
<ul style="font-size:0.7em"><li>The bigger the text the more common the phrase</li>
<li>Phrases get lighter the more common the words are, eg 'and the' will be light grey, but 'Romney Marsh' will be darker because it isn't using common English words.</li>
<li>You can also click a phrase to see common phrases from images using the selected phrase, and/or perform a search for all relevant images.</li>
<li>Switch to <a href="{$script_name}?t=1{if $words}&amp;words={$words|escape:url}{/if}{$extra_link}">list version</a>.</li></ul>

<p>Show for {section name=l loop=3 start=0}
{if $len == $smarty.section.l.iteration}
<b>{if %l.iteration% == 1}single words{else}{%l.iteration%} word phrases{/if}</b>
{else}
<a href="{$script_name}?{if $words}words={$words|escape:url}{/if}{$extra_link}&amp;len={%l.iteration%}">{if %l.iteration% == 1}single words{else}{%l.iteration%} word phrases{/if}</a>
{/if}
{/section}
</p>

<h3>Most Common in the last 7 days</h3>
<p> . 
{foreach from=$wordlist key=words item=obj}
<a style="color:#{$obj.color};font-size:{$obj.size}px;text-decoration:none;" title="used {$obj.sum_title} times" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}{$extra_link}">{$words}</a> .
{/foreach}
</p>

<h3>All Time Most Common</h3>
<p> . 
{foreach from=$toplist key=words item=obj}
<a style="color:#{$obj.color};font-size:{$obj.size}px;text-decoration:none;" title="used {$obj.sum_title} times" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}{$extra_link}">{$words}</a> .
{/foreach}
</p>
 		
{include file="_std_end.tpl"}
