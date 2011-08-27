{if $thetag}
{if $images && $images > 1}{assign var="title" value="`$images` images"}{else}{assign var="title" value="Images"}{/if}
{if $gridref}{assign var="page_title" value="`$title` tagged with '`$thetag`' near `$gridref`"|escape:'html'}
{else}{assign var="page_title" value="`$title` tagged with '`$thetag`'"|escape:'html'}{/if}
{assign var="tag2" value=$thetag|escape:'url'}
{assign var="extra_meta" value="<link rel=\"canonical\" href=\"http://`$http_host`/tags/?tag=`$tag2`\" />"}
{else}
{assign var="page_title" value="Tags"}
{/if}
{include file="_std_begin.tpl"}

{if $geographical}
	<h2>images for the "Geographical Features" project</h2>

{elseif $private}
	<h2>Private Tags</h2>

	<p>These are images you have tagged with "Private Tags", these lists are only visible to you.</p>

{elseif $example}
	<h2>Example tagged imags</h2>

{elseif $bucket}
	<h2>images assigned to buckets</h2>
	<p>This is only a prototype, to get the ball rolling, more features will be added soon. <a href="/article/Image-Buckets">Read more about buckets here</a></p>

{else}
	<h2>Public Tags</h2>

	<p>This is only a prototype, to get the ball rolling, more features will be added soon. <a href="/article/Tags">Read more about tags here</a></p>
{/if}

{if $prefixes}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;">
	PREFIXES: {foreach from=$prefixes item=item}
		{if isset($theprefix) && $item.prefix eq $theprefix}
			<span class="nowrap">&nbsp;<b>{$item.prefix|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.tags} tags" {if $item.tags > 10} style="font-weight:bold"{/if} href="{$script_name}?prefix={$item.prefix|escape:'url'}">{$item.prefix|escape:'html'|replace:' ':'&middot;'|default:'<i>none</i>'}</a> &nbsp;
		{/if}
	{/foreach}
	</div>

	{if $theprefix == 'geographical feature'}
		<p>There is a dedicated page for viewing images in the <b>geographical feature</b> prefix, <a href="/tags/geographical_features.php">here</a>.</p>
	{elseif $theprefix == 'bucket'}
		<p>There is a dedicated page for viewing images in the <b>buckets</b> prefix, <a href="/tags/buckets.php">here</a>.</p>
	{/if}

{/if}

{if $tags}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;{if count($tags) > 100} height:150px;overflow:auto{/if}">
	TAGS: {foreach from=$tags item=item}
		{if $item.tag eq $thetag}
			<span class="nowrap">&nbsp;<b>{$item.tag|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}{if isset($theprefix)}?prefix={$theprefix|escape:'url'}{/if}">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="{$script_name}?tag={$item.tag|escape:'url'}{if isset($theprefix)}&amp;prefix={$theprefix|escape:'url'}{/if}">{$item.tag|escape:'html'|replace:' ':'&middot;'}</a> &nbsp;
		{/if}
	{/foreach}
	</div>
{elseif $thetag}
	<div class="interestBox">Tag:
		<span class="nowrap">&nbsp;<b>{$thetag|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}{if isset($theprefix)}?prefix={$theprefix|escape:'url'}{/if}">remove filter</a>] &nbsp;</span>
	</div>
{/if}


{if $results}
	{if !$example && !$private}
		<p>Showing {if $images > 50}{if $gridref}nearest{else}latest{/if} 50 of {$images|thousends}{/if} images tagged with <span class="tag">{if $theprefix}{$theprefix|escape:'html'}:{/if}<a class="taglink">{$thetag|escape:'html'}</a></span> tag{if $gridref}near <a href="/gridref/{$gridref}">{$gridref}</a>{/if}{if $exclude} but excluding images with <span class="tag"><a class="taglink">{$exclude|escape:'html'}</a></span> tag{/if}.</p>
		<div style="text-align:right">
			{if $gridref}
			<a href="/search.php?q=tags:%22{if $theprefix}{$theprefix|escape:'url'}+{/if}{$thetag|escape:'url'}%22&amp;location={$gridref|escape:'url'}">Images using this tag near {$gridref}</a><br/>
			{/if}
			<b><a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View all tagged images</a></b>
		</div>
	{/if}

		{foreach from=$results item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">

			  <div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			  </div>

				<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
				by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
				{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				<br/>

				{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
				{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

				{if $image->comment}
				<div class="caption" style="clear:none" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
				{/if}
				{if $image->tags}
				<div class="caption" style="clear:none">Tags:
				{foreach from=$image->tags item=item name=used}
					<span class="tag">
					{if $item.prefix}{$item.prefix|escape:'html'}:{/if}<a href="/tags/?tag={if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}&amp;photo={$image->gridimage_id}" class="taglink">{$item.tag|escape:'html'}</a>{if $item.tag != $thetag}<a href="{$script_name}?tag={$thetag|escape:'url'}&amp;exclude={$item.tag|escape:'url'}" class="delete" title="Exclude this tag">X</a>{/if}
					</span>&nbsp;
				{/foreach}
				</div>
				{/if}

			  <br style="clear:both;"/>
			 </div>
		{/foreach}

	{if !$example && !$private}
		<div style="text-align:right">
			<a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View more in the Image Search</a>
		</div>
	{/if}


{/if}


{include file="_std_end.tpl"}

