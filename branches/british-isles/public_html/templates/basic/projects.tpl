{if $user_id && $realname}
{assign var="page_title" value="Projects by `$realname`"}
{else}
{assign var="page_title" value="Projects"}
{/if}
{include file="_std_begin.tpl"}


<h2>Geograph Projects</h2>


{dynamic}
{if $user->registered}


	<ul style="margin:0px;"><li><a href="/project/edit.php?id=new">Add your own Entry</a></li></ul>



{/if}
{/dynamic}



<br style="clear:both"/>

{if $user_id && $realname}
	<p><b>{if $when}{$when}{else}Recent{/if} Projects by <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></b></p>
{elseif $when}
	<p><b>{$when} Projects</b></p>
{/if}

{if $tags || $archive}
	<div class="interestBox wordnet" style="font-size:0.8em;line-height:1.4em;width:200px;float:right;">

	{if $archive}
		ARCHIVE:
		<table border="0" cellspacing="1" cellpadding="1" style="font-size:0.9em">
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
				<td style="background-color:cyan" width="10" height="10" align="center"><b>{$item.c}</b></td>
			{else}
				<td style="background-color:cyan" width="10" height="10" align="center"><a href="?when={$item.year}-{$item.month}{if $user_id && $realname}&amp;u={$user_id}{/if}" style="text-decoration:none" title="{$item.year}/{$item.month}">{$item.c}</a></td>
			{/if}
			{assign var="lastmonth" value=$item.month}
		{/foreach}
		{if $last}
			</tr>
		{/if}
		</table>
		<br/><br/>
	{/if}

	{if $tags}
	TAGS:<br/> {foreach from=$tags key=tag item=count}
		{if $tag eq $thetag}
			<span class="nowrap"><b>{$tag|escape:'html'|ucwords|replace:' ':'&middot;'}</b> [<a href="/project/">remove filter</a>] &nbsp;</span>&nbsp;
		{else}
			<a title="{$count} entries" {if $count > 10} style="font-weight:bold"{/if} href="/project/?tag={$tag|escape:'url'}{if $user_id && $realname}&amp;u={$user_id}{/if}" rel="nofollow">{$tag|escape:'html'|ucwords|replace:' ':'&middot;'}</a>&nbsp;
		{/if}
	{/foreach}
	{/if}
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
	</div>
{/if}


{if $list}


{foreach from=$list item=item}

	<div style="position:relative;width:233px;float:left; border-left: 2px solid silver; padding-left:5px;margin-left:5px; margin-bottom:20px; height:28em;{if $item.approved < 1}background-color:pink{/if}">
		<h4 style="margin-top: 0px;font-size:1.2em; margin-bottom:0; background-color:lightgrey;padding:2px;"><a href="/project/entry.php?id={$item.project_id}" style="text-decoration:none">{$item.title|escape:'html'}</a></h4>
		<div style="text-align:right;margin-bottom:3px;color:gray">{$item.created}</div>

		{if $item.reason && $item.purpose}
			<b>Purpose/Goal</b>
			<div style="font-size:0.8em;padding-left:3px;text-align:justify;overflow:none;font-family:'Comic Sans MS',Georgia,Verdana,Arial,serif">{$item.purpose|truncate:255|escape:'html'}</div>
			<b>Why do this project?</b>
			<div style="font-size:0.8em;padding-left:3px;text-align:justify;overflow:none;font-family:'Comic Sans MS',Georgia,Verdana,Arial,serif">{$item.reason|truncate:255|escape:'html'}</div>
		{else}

			<div style="font-size:0.8em;text-align:justify;overflow:none;font-family:'Comic Sans MS',Georgia,Verdana,Arial,serif">{$item.content|truncate:500|escape:'html'|replace:'/':'/<wbr/>'|regex_replace:'/\[\[\[(\d+)\]\]\]/':'<a href="/photo/\1">Photo</a>'}</div>
		{/if}

		<div style="margin-top:8px;border-top:1px solid gray">
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

