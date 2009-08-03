{assign var="page_title" value="Hectad Leaderboard  :: $type"|capitalize}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Hectad Leaderboard :: {$type|capitalize}</h2>

<p>Variation: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="leaderhectad.php?type={$t}">{$t}</a>{/if}]
{/foreach}</p>

<p>Listed below are the top 200 contributors based on number of
{$desc}. This page only counts First Geograph Images.</p>

<p><small>Number in Brackets is the the number of land squares making up the Hectad. Double click a list of hectads to expand (also displays as tooltip).</small></p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td><td>List</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile/{$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.imgcount}</td>
<td style="font-size:0.8em" title="{$topuser.hectads|replace:",":", "}" ondblclick="this.innerHTML='{$topuser.hectads|replace:"[100]":""|replace:",":", "|replace:"[":"<sup>["|replace:"]":"]</sup>"}'">{$topuser.hectads|replace:"[100]":""|truncate:24:"..."|replace:"[":"<sup>["|replace:"]":"]</sup>"}</td></tr>
{/foreach}

</tbody>
</table>

 		
{include file="_std_end.tpl"}
