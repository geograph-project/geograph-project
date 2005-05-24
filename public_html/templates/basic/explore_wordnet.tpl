{assign var="page_title" value="Popular Phrases"}
{include file="_std_begin.tpl"}

<h2>Popular Phrases {if $words}related to <i>{$words}</i>{/if}</h2>

{if $words}
<p><a href="/search.php?textsearch=^{$words|escape:url}&amp;do=1">Search for images containing <b>{$words}</b></a></p>
{/if}

<p>Here are the most common phrases used within the titles of submitted images.</p>
<ul><li><small>The bigger the text the more common the phrase, phrases get lighter the more common the words are, eg 'and the' will be light gray, but 'romney marsh' will be darker because not using common english words. You can also click a phrase to see common phrases from images using the selected phrase. (If you dont like all this multi size text then try the <a href="wordnet.php?t=1">simple version</a>)</small></li></ul>

<p>Show for {section name=l loop=3 start=0}
{if $len == $smarty.section.l.iteration}
<b>{%l.iteration%} word phrases</b>
{else}
<a href="{$script_name}?{if $words}words={$words|escape:url}&amp;{/if}len={%l.iteration%}">{%l.iteration%} word phrases</a>
{/if}
{/section}
{if $words}
[<a href="{$script_name}?len={$len}">remove filter</a>]
{/if}
</p>

<h3>Most Common in the last 7 days</h3>
<p>
{foreach from=$wordlist key=words item=obj}
<a style="color:#{$obj.color};font-size:{$obj.size}px;background-color:{cycle values="#f0f0f0,#e9e9e9"};text-decoration:none;" title="used {$obj.sum_title} times" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}&amp;len={$len}">{$words}</a>
{/foreach}
</p>

<h3>All Time Most Common</h3>
<p>
{foreach from=$toplist key=words item=obj}
<a style="color:#{$obj.color};font-size:{$obj.size}px;background-color:{cycle values="#f0f0f0,#e9e9e9"};text-decoration:none;" title="used {$obj.sum_title} times" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}&amp;len={$len}">{$words}</a>
{/foreach}
</p>

<p>Last generated at {$generation_time|date_format:"%H:%M"}.</p>
 		
{include file="_std_end.tpl"}
