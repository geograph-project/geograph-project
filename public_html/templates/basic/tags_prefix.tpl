{assign var="page_title" value="Prefixed Tags"}
{include file="_std_begin.tpl"}

<h2>{$h2title}</h2>

<p>{$headnote}</p>

<table>
{assign var="last" value=""}
{foreach from=$table item=row}
	{assign var="letter" value=$row.tag[0]|lower}
	{if $last != $letter}
		{if $last}
			</td><tr>
		{/if}
		<tr>
			<th style=font-size:3em valign=top>{$letter}</th>
			<td>
	{/if}{assign var="last" value=$letter}

	<span class=nowrap><a href="/tagged/{$p}{$row.tag|escape:'urlplus'}">{$row.tag|escape:'html'}</a>({$row.images})</span> &nbsp;
{/foreach}

</table>

        {if $footnote}
		<p>{$footnote}</p>
	{/if}

{include file="_std_end.tpl"}
