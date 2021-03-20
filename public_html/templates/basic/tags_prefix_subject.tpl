{assign var="page_title" value="Prefixed Tags"}
{include file="_std_begin.tpl"}

<h2>{$h2title}</h2>

<p>{$headnote}</p>

{assign var="lastgroup" value=""}
{assign var="lastcontext" value=""}
{foreach from=$table item=row}
	{if $lastgroup != $row.grouping}
		{if $lastgroup}
			<br><br>
		{/if}
		<div class=interestBox><h2>{$row.grouping|escape:'html'}</h2></div>
	{/if}
	{assign var="lastgroup" value=$row.grouping}
	{if $lastcontext != $row.maincontext}
		<h3>{$row.maincontext|escape:'html'}</h3>
	{/if}
	{assign var="lastcontext" value=$row.maincontext}

	<span class=nowrap><a href="/tagged/{$p}{$row.tag|escape:'urlplus'}">{$row.tag|escape:'html'}</a>({$row.images})</span> &nbsp;
{/foreach}



{include file="_std_end.tpl"}
