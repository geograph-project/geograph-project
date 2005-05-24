{assign var="page_title" value="Popular Phrases"}
{include file="_std_begin.tpl"}

<h2>Popular Phrases {if $words}related to <i>{$words}</i>{/if}</h2>
{if $words}
<p><a href="/search.php?textsearch=^{$words|escape:url}&amp;do=1">Search for images containing <b>{$words}</b></a></p>
{/if}
<p>Show for {section name=l loop=3 start=0}
{if $len == $smarty.section.l.iteration}
<b>{%l.iteration%} word phrases</b>
{else}
<a href="{$script_name}?{if $words}words={$words|escape:url}&amp;{/if}t=1&amp;len={%l.iteration%}">{%l.iteration%} word phrases</a>
{/if}
{/section}
{if $words}
[<a href="{$script_name}?len={$len}&amp;t=1">remove filter</a>]
{/if}
</p>

<div style="float:left;width:50%;position:relative">

<h3>Most Common in the last 7 days</h3>
<table class="report">
<thead><tr>
<td>Count</td>
<td>Word</td></tr></thead>
<tbody>
{foreach from=$wordlist key=words item=obj}
<tr><td align="right">{$obj.sum_title}</td>
<td><a style="color:#{$obj.color};" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}&amp;len={$len}&amp;t=1">{$words}</a></td></tr>
{/foreach}
</tbody></table>

</div><div style="float:left;width:50%;position:relative">

<h3>All Time Most Common</h3>
<table class="report">
<thead><tr>
<td>Count</td>
<td>Word</td></tr></thead>
<tbody>
{foreach from=$toplist key=words item=obj}
<tr><td align="right">{$obj.sum_title}</td>
<td><a style="color:#{$obj.color};" href="{$script_name}?words={$words|replace:"&nbsp;":"+"}&amp;len={$len}&amp;t=1">{$words}</a></td></tr>
{/foreach}
</tbody></table>

</div>

<p>Last generated at {$generation_time|date_format:"%H:%M"}.</p>
 		
{include file="_std_end.tpl"}
