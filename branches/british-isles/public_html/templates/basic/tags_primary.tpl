{assign var="page_title" value="Primary Categories"}
{include file="_std_begin.tpl"}

        <div class="tabHolder">
                <span class="tabSelected">Geographical Context</span>
                <a href="/article/Image-Buckets" class="tab">Image Buckets</a>
                <a href="/tags/" class="tab">Tags</a>
        </div>
        <div style="position:relative;margin-bottom:10px" class="interestBox">
		<h2 style="margin:0"><a name="top"></a>Geographical Context <small>(Primary Categories)</small></h2>
	</div>

{assign var="lastcat" value=""}
{foreach from=$results item=item}

	{if $lastcat != $item.grouping}
		{if $lastcat}
			</div>
		{/if}
		<div class="plist">
		<div class="title">{$item.grouping}</div>
		{assign var="lastcat" value=$item.grouping}
	{/if}
	<label class="item">&middot; <a href="#{$item.tag|replace:' ':'_'|escape:'url'}">{$item.tag|escape:'html'}</a></label>
{/foreach}
{if $lastcat}
	</div>
	<br style="clear:both"/>
{/if}


{assign var="lastcat" value=""}
{foreach from=$results item=item}

	{if $lastcat != $item.grouping}
		{if $lastcat}
			</ol>
		{/if}
		<h1>{$item.grouping}</h1>
		<ol>
		{assign var="lastcat" value=$item.grouping}
	{/if}
	<li>
	<div class="interestBox">
	<a name="{$item.tag|replace:' ':'_'|escape:'url'}"></a>
	{if $item.resultCount > 3}
		<div style="float:right"><a href="/stuff/tagmap.php?tag=top:{$item.tag|escape:'url'}">Coverage Map</a> | <a href="/tags/?tag=top:{$item.tag|escape:'url'}">View <b>{$item.resultCount|thousends}</b> images</a></div>
	{/if}

	<big><b>{$item.tag|escape:'html'}</b></big>

	</div>
	{if $item.description}
	<p>{$item.description|escape:'html'}</p>
	{/if}
	{foreach from=$item.images item=image}

		<div style="float:left;width:160px" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
	{foreachelse}
		{if $item.skipped}
			<div><small><i>matching images in square not checked</i></small></div>
		{else}
			<div><small><i>no images found</i></small></div>
		{/if}
	{/foreach}
	<div style="clear:left;text-align:right"><a href="#top">back to top</a></div>

	</li>
{foreachelse}
	<ol>
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>


{include file="_std_end.tpl"}
