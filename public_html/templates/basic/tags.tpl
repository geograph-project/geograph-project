{if $thetag}
{if $description}{assign var="meta_description" value=$description}{/if}
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
	<h2>Images for the "Geographical Features" project</h2>

{elseif $private}
	<h2>Private Tags</h2>

	<p>These are images you have tagged with "Private Tags", these lists are only visible to you.</p>

{elseif $example}
	<h2>Example tagged Images</h2>

{elseif $bucket}
        <div class="tabHolder">
                <a href="/tags/primary.php" class="tab">Geographical Context</a>
                <span class="tabSelected">Image Buckets</span>
                <a href="/tags/" class="tab">Tags</a>
        </div>
        <div style="position:relative;" class="interestBox">
		<h2>Image Buckets</h2>
        </div>

	<p>This is only a prototype, to get the ball rolling; more features will be added soon. <a href="/article/Image-Buckets">Read more about buckets here</a></p>

{else}
        <div class="tabHolder">
                <a href="/tags/primary.php" class="tab">Geographical Context</a>
                <a href="/article/Image-Buckets" class="tab">Image Buckets</a>
		{if $thetag || $theprefix || $prefixes}
                <a href="/tags/" class="tabSelected">Tags</a>
		{else}
                <span class="tabSelected">Tags</span>
		{/if}
        </div>
        <div style="position:relative;padding-bottom:3px" class="interestBox">
		<h2 style="margin:0">Public Tags <sup><a href="/article/Tags" class="about" style="font-size:0.7em">About tags on Geograph</a></sup></h2>
        </div>
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

{elseif $theprefix}
	<p>Prefix: <b>{$theprefix|escape:'html'}</b></p>
{/if}

{if $tags}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;{if count($tags) > 100 && $results} height:150px;overflow:auto{/if}">
	TAGS: {foreach from=$tags item=item}
		{if $item.tag eq $thetag}
			<span class="nowrap">&nbsp;<b>{$item.tag|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}{if isset($theprefix)}?prefix={$theprefix|escape:'url'}{/if}">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="{$script_name}?tag={$item.tag|escape:'url'}{if isset($theprefix)}&amp;prefix={$theprefix|escape:'url'}{/if}">{$item.tag|escape:'html'|replace:' ':'&middot;'}</a> &nbsp;
		{/if}
	{/foreach}
	</div>
{/if}
{if $thetag}
	<div class="interestBox">
		 <div style="float:right">
			<a href="/finder/bytag.php?q=tags:{$thetag|escape:'url'}">Related Tags</a> |
			<a href="/stuff/tagmap.php?tag={if isset($theprefix)}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">Coverage Map</a></div>

		Tag: <span class="nowrap">&nbsp;<b>{$thetag|escape:'html'|replace:' ':'&middot;'}</b> &nbsp;</span>

		{if $onetag}
			{dynamic}{if $user->registered}
			{if $description}
				<div style="background-color:white;padding:10px">
					<b>{$description|escape:'html'}</b>
				</div>
				<a href="/tags/description.php?tag={if isset($theprefix) || $needprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">Improve description</a>
			{else}
				<a href="/tags/description.php?tag={if isset($theprefix) || $needprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">Provide a description for this tag!</a>
			{/if}

			&middot; <a href="/tags/synonym.php?tag={if isset($theprefix)}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">Synonyms</a>
			{/if}{/dynamic}
		{/if}

	</div>
	{if $others}
		Other tags: {foreach from=$others item=item}
			<span class="tag">{if $item.prefix}{$item.prefix|escape:'html'}:{/if}<a href="/tags/?tag={if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}&amp;exact=1" class="taglink">{$item.tag|escape:'html'}</a></span>
			&nbsp; &nbsp;
		{/foreach}
	{/if}
{/if}


{if $results}
	{if !$example && !$private}
		<p>Showing {if $images > 50}{if $gridref}nearest{else}latest{/if} 50 of {$images|thousends}{/if} images tagged with <span class="tag">{if $theprefix}{$theprefix|escape:'html'}:{/if}<a class="taglink">{$thetag|escape:'html'}</a></span> tag{if $gridref} near <a href="/gridref/{$gridref}">{$gridref}</a>{/if}{if $exclude} but excluding images with <span class="tag"><a class="taglink">{$exclude|escape:'html'}</a></span> tag{/if}.</p>
		<div style="text-align:right">
			{if $images > 15}
		        <form action="/search.php">
		        <label for="fq">Search within these images</label>: <input type="text" name="searchtext[]" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		        <input type="hidden" name="searchtext[]" value="[{if isset($theprefix)}{$theprefix|escape:'html'} {/if}{$thetag|escape:'html'}]"/>
		        <input type="hidden" name="do" value="1"/>
		        <input type="submit" value="Find"/>
		        </form>
			{/if}

			{if $gridref}
			<a href="/search.php?q=[{if $theprefix}{$theprefix|escape:'url'}+{/if}{$thetag|escape:'url'}]&amp;location={$gridref|escape:'url'}">Images using this tag near {$gridref}</a><br/>
			{/if}
			<b><a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View all tagged images</a></b>
		</div>
	{/if}
		<table cellspacing="0" cellpadding="2" border="0">
		{foreach from=$results item=image}
			<tr bgcolor="#{cycle values="e1e1e1,f3f3f3"}">
				<td align="center">
					<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
				</td>
				<td>
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
				</td>
			</tr>
		{/foreach}
		</table>

	{if !$example && !$private}
		<div style="text-align:right">
			<a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View more in the Image Search</a>
		</div>
	{/if}

{elseif $thetag}
	<p><i>No images found - perhaps they haven't been moderated yet</i></p>
{/if}


{include file="_std_end.tpl"}

