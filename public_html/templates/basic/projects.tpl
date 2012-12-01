{if $user_id && $realname}
{assign var="page_title" value="Projects by `$realname`"}
{else}
{assign var="page_title" value="Projects"}
{/if}
{include file="_std_begin.tpl"}
<style>{literal}
div.tags {
	line-height:1.5em;
	font-size:0.85em;
}
span.tag, a.tag {
	padding:2px;
	border-radius:3px;
	background-color:lightgray;
	font-family:monospace;
	text-decoration:none;
	color:brown;
	white-space:nowrap;
}

.listing-sidebar {
	line-height:1.4em;
	width:200px;
	float:right;
	background-color:#eee;
}
.listing-sidebar table {
	font-size:0.9em;
}
.listing-sidebar .cell {
	background-color:cyan;
}
.listing-sidebar .cell a {
	text-decoration:none;
}

.entry {
	position:relative;
	width:233px;
	float:left;
	border-left:2px solid #eee;
	padding-left:5px;
	margin-left:5px;
	margin-bottom:20px;
	height:28em;
	overflow:hidden;
}
.entry h4 {
	background-color:#eee;
	margin-top:0px;
	font-size:1.2em;
	margin-bottom:0;
	border-bottom: 1px solid silver;
	margin-bottom:2px;
	padding:2px;
}
.entry h4 a {
	text-decoration:none;
}
.entry div.date {
	float:right;
	margin-bottom:3px;
	color:gray
}
.entry div.textual {
	font-size:0.8em;
	padding-left:3px;

	overflow:none;
	font-family:'Comic Sans MS',Georgia,Verdana,Arial,serif
}
.entry div.footer {
	margin-top:8px;
	border-top:1px solid gray;
	color:gray;
}
.entry div.footer a {
	color:#9372E8;
}

{/literal}</style>

<h2>Geograph Projects

{dynamic}
{if $user->registered}
	<small>(<a href="/project/edit.php?id=new">Add your own Project</a>)</small>
{/if}
{/dynamic}</h2>

{if $user_id && $realname}
	<p style="clear:both"><b>{if $when}{$when}{else}Recent{/if} Projects by <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></b></p>
{elseif $when}
	<p style="clear:both"><b>{$when} Projects</b></p>
{/if}

{if $tags || $archive}
	<div style="clear:both" class="interestBox wordnet listing-sidebar">

	{if $archive}
		<b>Project Archive</b>:
		<table border="0" cellspacing="1" cellpadding="1">
		{assign var="last" value="0"}
		{foreach from=$archive item=item}
			{if $last != $item.year}
				{if $last}
					</tr>
				{/if}
				<tr>
					<td>{$item.year}</td>
					{if $item.month > 01}
						{section name="loop" start=1 loop=$item.month}
							<td width="10" height="10">&nbsp;</td>
						{/section}
					{/if}
				{assign var="last" value=$item.year}
			{elseif $item.month-1 > $lastmonth}
				{section name="loop" start=$lastmonth loop=$item.month-1}
					<td width="10" height="10">&nbsp;</td>
				{/section}
			{/if}
			{if $when == "`$item.year`-`$item.month`"}
				<td class="cell" width="10" height="10" align="center"><b>{$item.c}</b></td>
			{else}
				<td class="cell" width="10" height="10" align="center"><a href="?when={$item.year}-{$item.month}{if $user_id && $realname}&amp;u={$user_id}{/if}" title="{$item.year}/{$item.month}">{$item.c}</a></td>
			{/if}
			{assign var="lastmonth" value=$item.month}
		{/foreach}
		{if $last}
			</tr>
		{/if}
		</table>

	{/if}

	{if $tags}
		<b>Tag listing</b>:<br/>
		<div class="tags">
		{foreach from=$tags key=tag item=count name=foo}
			{if $tag eq $thetag}
				<span class="tag"><b>{$tag|escape:'html'}</b> [<a href="/project/">remove filter</a>]</span>
			{else}
				<a class="tag" title="{$count} entries" {if $count > 10} style="font-weight:bold"{/if} href="/project/?tag={$tag|escape:'url'}{if $user_id && $realname}&amp;u={$user_id}{/if}" rel="nofollow" class="tag">{$tag|escape:'html'}</a>
			{/if}
		{/foreach}
		</div>
	{/if}
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
	</div>
{/if}


{if $list}


{foreach from=$list item=item}

	<div class="entry" style="{if $item.approved < 1}background-color:pink{/if}">
		<h4><a href="/project/entry.php?id={$item.project_id}">{$item.title|escape:'html'}</a></h4>
		<div class="date">{$item.created}</div>

		{if $item.tags}
			<div class="tags">
			<span class="tag">{$item.tags|escape:'html'|lower|replace:',':'</span> <span class="tag">'}</span>
			</div>
		{/if}
		{if $item.reason && $item.purpose}
			<b>Purpose/Goal</b>
			<div class="textual" style="padding-left:3px;">{$item.purpose|truncate:255|escape:'html'}</div>
			<b>Why do this project?</b>
			<div class="textual" style="padding-left:3px;">{$item.reason|truncate:255|escape:'html'}</div>
		{else}

			<div class="textual">{$item.content|truncate:500|escape:'html'|replace:'/':'/<wbr/>'|regex_replace:'/\[\[\[(\d+)\]\]\]/':'<a href="/photo/\1">Photo</a>'}</div>
		{/if}

		<div class="footer">
		Posted by <a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a> <a href="?u={$item.user_id}">+</a> <span class="nowrap">on {$item.published|date_format:"%a, %e %b"}</span>
		<a href="/project/entry.php?id={$item.project_id}"><b>Read More...</b></a>

			{if $user->user_id == $item.user_id || $isadmin}<br/><br/>
					[<a href="/project/edit.php?id={$item.project_id}">edit</a>]
			{/if}
			{if $isadmin}
				{if $item.approved eq 1}
					[<a href="/project/?id={$item.project_id}&amp;approve=0">disapprove</a>]
				{else}
					[<a href="/project/?id={$item.project_id}&amp;approve=1">approve</a>]
					[<a href="/project/?id={$item.project_id}&amp;approve=-1">delete</a>]
				{/if}
			{elseif $item.approved < 1}
				[Not Approved]
			{/if}

		</div>
	</div>

{/foreach}
<br style="clear:both"/>

{else}
  <p>There are no listed entries.</p>
{/if}


{dynamic}
{if $user->registered}
<br/><br/><br/><br/><br/><br/>
<div class="interestBox">
	<ul style="margin:0px;"><li><a href="/project/edit.php?id=new">Add your own entry</a></li></ul>
</div>

{/if}
{/dynamic}


{include file="_std_end.tpl"}

