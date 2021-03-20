{if $thetag}
{if $description}{assign var="meta_description" value=$description}{/if}
{if $images && $images > 1}{assign var="title" value="`$images` images"}{else}{assign var="title" value="Images"}{/if}
{if $gridref}{assign var="page_title" value="`$title` tagged with '`$thetag`' near `$gridref`"|escape:'html'}
{else}{assign var="page_title" value="`$title` tagged with '`$thetag`'"|escape:'html'}{/if}
{if $theprefix}{assign var="tag2" value="$theprefix:$thetag"|escape:'urlplus'}{else}{assign var="tag2" value=$thetag|escape:'urlplus'}{/if}
{else}
{assign var="page_title" value="Tags"}
{/if}
{include file="_std_begin.tpl"}

{if $thetag && $gridref}
<div class="breadcrumb" style="margin-bottom:20px">
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <a href="/tags/" itemprop="url"><span itemprop="title">Tagged Images</span></a> &gt;
</span>
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <a href="/tagged/{if isset($theprefix)}{$theprefix|escape:'urlplus'}:{/if}{$thetag|escape:'urlplus'}" itemprop="url"><span itemprop="title">Tagged with <b>{$thetag|escape:'html'}</b></span></a> &gt;
</span>
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <span itemprop="title"><b>Near {$gridref|escape:'html'}</b></span>
</span>
</div>
{/if}


	<h2>Private Tags</h2>

	<p>These are images you have tagged with "Private Tags", these lists are only visible to you (unless you share it below!).</p>

{if $prefixes}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;">
	PREFIXES: {foreach from=$prefixes item=item}
		{if isset($theprefix) && $item.prefix eq $theprefix}
			<span class="nowrap">&nbsp;<b>{$item.prefix|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}?share=1">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.tags} tags" {if $item.tags > 10} style="font-weight:bold"{/if} href="{$script_name}?share=1&amp;prefix={$item.prefix|escape:'url'}">{$item.prefix|escape:'html'|replace:' ':'&middot;'|default:'<i>none</i>'}</a> &nbsp;
		{/if}
	{/foreach}
	</div>

{/if}

{if $tags}
	<form method=post>
	<table>
	<tr>
		<th>Prefix</th>
		<th>Tag</th>
		<th>Images</th>
		<th>Share</th>
	{foreach from=$tags item=item}
		<tr>
			<td align=right>{if $item.prefix}{$item.prefix|escape:'html'}:{/if}</td>
			<td align=left><a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="{$script_name}?{if $item.prefix}prefix={$item.prefix|escape:'urlplus'}&amp;{/if}tag={$item.tag|escape:'urlplus'}">{$item.tag|escape:'html'|replace:' ':'&middot;'}</a></td>
			<td align=right style="font-weight:bold">{$item.images}</td>
			<td style="color:gray;font-size:small">(<input type=checkbox name="share[{$item.tag_id}]" {if $item.checked}checked{/if}> share list publically, 
				<input type=checkbox name="own_too[{$item.tag_id}]" {if $item.own_too}checked{/if}> include own images with this tag too)
				{if $item.checked}<input type=hidden name="was[{$item.tag_id}]" value="1">{/if}</td>
		</tr>
	{/foreach}
	</table>
	<input type=submit value="save changes">
	</form>
{/if}


{include file="_std_end.tpl"}

