{assign var="page_title" value="All Hectad Leaderboard  :: $type"|capitalize}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>All Hectad Leaderboard :: {$type|capitalize}</h2>

<p>Variation: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="leaderallhectad.php?type={$t}">{$t}</a>{/if}]
{/foreach}</p>

<p>Listed below are the top 200 contributors based on number of
{$desc}. (This page counts all images, but still only lists completed hectads)</p>

<p><small>Number in brackets is the the number of land squares making up the hectad. Double click a list of hectads to expand (also displays as tooltip).</small></p>

<div style="overflow:auto;">
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
</div>
 		
{include file="_std_end.tpl"}
