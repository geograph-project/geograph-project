{assign var="page_title" value="Primary Categories"}
{include file="_std_begin.tpl"}


<h2>Primary Categories</h2>

<ol start="{$offset}">
{foreach from=$results item=item}
	<li>
	<div class="interestBox">

	{if $item.resultCount > 3}
		<div style="float:right"><a href="/tags/?tag={$item.tag|escape:'url'}">View {$item.resultCount} images</a></div>
	{/if}

	<big><b>{$item.tag|escape:'html'}</b></big>

	</div>
	<p>{cycle values="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vel volutpat metus.|Duis eget velit libero, tincidunt dictum nibh. Donec mattis ullamcorper congue. Nam at quam odio, eget laoreet nulla. |Praesent vel lectus id sem condimentum porttitor non nec nunc. In non lectus accumsan turpis venenatis vulputate. Proin pharetra consectetur lobortis. Nulla ut turpis velit, in hendrerit tellus. Proin semper urna eu arcu sagittis at blandit purus varius. Etiam viverra consectetur nisi, a ultrices mauris viverra sed. | Ut sit amet nunc sed mauris rhoncus fringilla. Maecenas dignissim accumsan orci quis viverra. Nulla suscipit, orci et fermentum hendrerit, augue ipsum porta orci, quis tempus turpis tortor vel dolor. " delimiter="|"}</p>

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
	<br style="clear:left;"/>

	</li>
{foreachelse}
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>


{include file="_std_end.tpl"}
