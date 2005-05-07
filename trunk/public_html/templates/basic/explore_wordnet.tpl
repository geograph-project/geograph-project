{assign var="page_title" value="Popular Phrases"}
{include file="_std_begin.tpl"}

<h2>Popular Phrases {if $words}related to <i>{$words}</i>{/if}</h2>

<p>Here are the most common phrases used within the titles of submitted images.</p>

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

<h3>100 Most Common in all time</h3>
<p>
{foreach from=$toplist key=words item=obj}
<a style="color:#{$obj.color};font-size:{$obj.size}px;background-color:{cycle values="#f0f0f0,#e9e9e9"};text-decoration:none;" title="used {$obj.sum_title} times" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}&amp;len={$len}">{$words}</a>
{/foreach}
</p>

<p>Last generated at {$generation_time|date_format:"%H:%M"}.</p>
 		
{include file="_std_end.tpl"}
