{if $thetag}
{if $description}{assign var="meta_description" value=$description}{/if}
{if $images && $images > 1}{assign var="title" value="`$images` images"}{else}{assign var="title" value="Images"}{/if}
{if $gridref}{assign var="page_title" value="`$title` tagged with '`$thetag`' near `$gridref`"|escape:'html'}
{else}{assign var="page_title" value="`$title` tagged with '`$thetag`'"|escape:'html'}{/if}
{if $theprefix}{assign var="tag2" value="$theprefix:$thetag"|escape:'urlplus'}{else}{assign var="tag2" value=$thetag|escape:'urlplus'}{/if}
{assign var="extra_meta" value="<link rel=\"canonical\" href=\"http://`$http_host`/tagged/`$tag2`\" />"}
{else}
{assign var="page_title" value="Tags"}
{/if}
{include file="_std_begin.tpl"}

{if $ireland}
	<div class=interestBox>This page only shows images from Ireland - Great Britain is automatically excluded.</div>
{/if}

{if $thetag && $gridref}
<div class="breadcrumb" style="margin-bottom:20px">
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <a href="/tags/" itemprop="url"><span itemprop="title">Tagged Images</span></a> &gt;
</span>
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <a href="/tagged/{if isset($theprefix)}{$theprefix|escape:'urlplus'}:{/if}{$thetag|escape:'urlplus'}" itemprop="url"><span itemprop="title">Tagged with <b>{$thetag|capitalizetag|escape:'html'}</b></span></a> &gt;
</span>
<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
  <span itemprop="title"><b>Near {$gridref|escape:'html'}</b></span>
</span>
</div>
{/if}


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
		<a href="/tags/?prefix=subject" class="tab">Subjects</a>
                <a href="/tags/" class="tab">Tags</a>
                <span class="tabSelected">Image Buckets</span>
        </div>
        <div style="position:relative;" class="interestBox">
		<h2>Image Buckets</h2>
        </div>

	<p>This is only a prototype, to get the ball rolling; more features will be added soon. <a href="/article/Image-Buckets">Read more about buckets here</a></p>

{else}
	<div style="float:right">
		<a href="/article/Tags" class="about" style="font-size:0.7em">About tags on Geograph</a>
	</div>
        <div class="tabHolder">
                <a href="/tags/primary.php" class="tab">Geographical Context</a>
		{if $theprefix == 'subject'} 
		<a href="/tags/?prefix=subject" class="tabSelected">Subjects</a>
		<a href="/tags/" class="tab">Tags</a>
		{else}
		<a href="/tags/?prefix=subject" class="tab">Subjects</a>
		{if $thetag || $theprefix || $prefixes}
                <a href="/tags/" class="tabSelected">Tags</a>
		{else}
                <span class="tabSelected">Tags</span>
		{/if}
		{/if}
                <a href="/article/Image-Buckets" class="tab">Image Buckets</a>
        </div>
        <div style="position:relative;padding-bottom:3px" class="interestBox">
		<h2 style="margin:0">Public Tags</h2>
        </div>
{/if}

{if $prefixes}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;">
	PREFIXES: {foreach from=$prefixes item=item}
		{if isset($theprefix) && $item.prefix eq $theprefix}
			<span class="nowrap">&nbsp;<b>{$item.prefix|escape:'html'|lower|replace:' ':'&middot;'}</b> [<a href="{$script_name}">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.tags} tags" {if $item.tags > 10} style="font-weight:bold"{/if} href="{$script_name}?prefix={$item.prefix|escape:'url'}">{$item.prefix|escape:'html'|lower|replace:' ':'&middot;'|default:'<i>none</i>'}</a> &nbsp;
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
	{if $theprefix && !$thetag && !$results}
		<h3>Tags prefixed with {$theprefix|escape:'html'|lower}</h3>
	{/if}
	<div class="interestBox" style="font-size:0.8em;line-height:1.4em; text-align:center;margin:20px;{if count($tags) > 100 && $results} height:150px;overflow:auto{/if}">
	TAGS: {foreach from=$tags item=item}
		{if $item.tag eq $thetag}
			<span class="nowrap">&nbsp;<b>{$item.tag|capitalizetag|escape:'html'|replace:' ':'&middot;'}</b> [<a href="{$script_name}{if isset($theprefix)}?prefix={$theprefix|escape:'url'}{/if}">remove filter</a>] &nbsp;</span>
		{else}
			&nbsp;<a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="/tagged/{if isset($theprefix)}{$theprefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}">{$item.tag|capitalizetag|escape:'html'|replace:' ':'&middot;'}</a> &nbsp;
		{/if}
	{/foreach}
	</div>
{/if}
{if $thetag}

	<div class="interestBox">
		 <div style="float:right">
			<a href="/finder/bytag.php?q=tags:{$thetag|escape:'url'}">Related Tags</a> |
			<a href="/stuff/tagmap.php?tag={if isset($theprefix)}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">Coverage Map</a></div>

		{if $theprefix}
			Prefix: <b>{$theprefix|escape:'html'|lower}</b> &nbsp;&nbsp;
		{/if}
		Tag: <span class="nowrap">&nbsp;<b>{$thetag|capitalizetag|escape:'html'|replace:' ':'&middot;'}</b> &nbsp;</span>

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
		<div style="font-size:0.8em;text-align:center">Other tags: {foreach from=$others item=item}
			<span class="tag">{if $item.prefix}{$item.prefix|escape:'html'|lower}:{/if}<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}?exact=1" class="taglink">{$item.tag|capitalizetag|escape:'html'}</a></span>
			&nbsp; &nbsp;
		{/foreach}
		</div>
	{/if}
{/if}

{if !$gridref}
	<script>{literal}
	if (window.location.hash && window.location.hash.length > 7 && (m = window.location.hash.match(/photo=(\d+)/)) && window.location.search.indexOf('photo=') == -1) {
		if (window.location.search && window.location.hash.length > 2) {
			url = window.location.search + '&photo=' + m[1];
		} else {
			url = '?photo=' + m[1];
		}
		document.write('<div class=interestBox><big><a href="'+url+'">Look for matching images near Image ID#'+m[1]+'</a></big></div>');
	}
	{/literal}</script>
{/if}

{if $results}
	{if !$example && !$private}
		<p><b>Showing {if $images > 50}{if $gridref}nearest{else}latest{/if} 50 of {$images|thousends}{/if} images tagged with <span class="tag">{if $theprefix}{$theprefix|escape:'html'|lower}:{/if}<a class="taglink">{$thetag|capitalizetag|escape:'html'}</a></span> tag{if $gridref} near <a href="/gridref/{$gridref}">{$gridref}</a>{/if}{if $exclude} but excluding images with <span class="tag"><a class="taglink">{$exclude|capitalizetag|escape:'html'}</a></span> tag{/if}.</b></p>

		<div style="float:right">
			{if $gridref}
			<a href="/search.php?q=[{if $theprefix}{$theprefix|escape:'url'}+{/if}{$thetag|escape:'url'}]&amp;location={$gridref|escape:'url'}">Images using this tag near {$gridref}</a><br/>
			{/if}
			<b><a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View all tagged images</a> - <a href="/browser/#!/tags+%22{$thetag|escape:'url'}%22">In Browser</a></b>
		</div>

		{if $images > 15}
		        <form action="/search.php">
			<input type="hidden" name="form" value="tags"/>
		        <label for="fq">Search within these images</label>: <input type="text" name="searchtext[]" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		        <input type="hidden" name="searchtext[]" value="[{if isset($theprefix)}{$theprefix|escape:'html'} {/if}{$thetag|escape:'html'}]"/>
		        <input type="hidden" name="do" value="1"/>
		        <input type="submit" value="Find"/>
		        </form>
		{/if}
		<br style="clear:both"\>
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
				<div class="caption" style="clear:none;font-size:0.9em">Tags:
				{foreach from=$image->tags item=item name=used}
					<span class="tag">
					{if $item.prefix}{$item.prefix|escape:'html'|lower}:{/if}<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink">{$item.tag|capitalizetag|escape:'html'}</a>{if $item.tag != $thetag}<a href="/tagged/{$thetag|escape:'urlplus'}?exclude={$item.tag|escape:'url'}" rel="nofollow" class="delete" title="Exclude this tag">X</a>{/if}
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
			<a href="/search.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">View more in the Image Search</a> or <a href="/browser/#!/tags+%22{if $theprefix}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}%22">in Browser</a><br/>
			<a href="/search.php?text=tags:{$thetag|escape:'url'}">View images matching '{$thetag|capitalizetag|escape:'html'}' anywhere <i>in</i> the tags</a>
		</div>
	{/if}

{elseif $thetag}
	<p><i>No images found - perhaps they haven't been moderated yet</i></p>

	&middot; <a href="/search.php?text=tags:{$thetag|escape:'url'}">Search for images matching '{$thetag|capitalizetag|escape:'html'}' anywhere <i>in</i> the tags</a>
{elseif $q}
	<p><i>No images found with [{$q|escape:'html'}] tag</i></p>

	&middot; Search for <a href="/search.php?text={$q|escape:'url'}">images matching '{$q|capitalizetag|escape:'html'}'</a> instead
{/if}


{include file="_std_end.tpl"}

