{assign var="page_title" value="Hectad Leaderboard  :: $type"|capitalize}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Hectad Leaderboard :: {$type|capitalize}</h2>

<p>Variation: {foreach from=$types item=t}
[{if $t == $type}<b>{$type}</b>{else}<a href="leaderhectad.php?type={$t}">{$t}</a>{/if}]
{/foreach}</p>

<p>Listed below are the top 200 contributors based on number of
{$desc}.</p>

<table class="report">
<thead><tr><td>Position</td><td>Contributor</td><td>{$heading}</td><td>List</td></tr></thead>
<tbody>

{foreach from=$topusers item=topuser}
<tr><td>{$topuser.ordinal}</td><td><a title="View profile" href="/profile.php?u={$topuser.user_id}">{$topuser.realname}</a></td>
<td align="right">{$topuser.imgcount}</td>
<td style="font-size:0.8em">{$topuser.hectads|truncate:17:"..."}</td></tr>
{/foreach}

</tbody>
</table>

 		
{include file="_std_end.tpl"}
