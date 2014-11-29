{include file="_std_begin.tpl"}

{dynamic cached_user_id=$user_id}
{if $is_mod || $user->user_id == $cached_user_id}
<div style="float:right;position:relative"><a href="/snippets.php?edit[{$snippet_id}]=edit&amp;onlymine=on">Edit this Description</a></div>
{/if}
{/dynamic}

<h2 style="margin:0;padding:0">{$title|escape:'html'|default:'Untitled'}{if $comment} <small>:: Shared Description</small>{/if}</h2>


{if $comment}
	{dynamic}
		{if $user->registered}
			<div style="float:right;position:relative" id="votediv{$snippet_id}"><a href="javascript:void(record_vote('snip',{$snippet_id},5));" title="I like this description! - click to agree"><img src="http://{$static_host}/img/thumbs.png" width="20" height="20" alt="I like this description!"/></a></div>
		{/if}
	{/dynamic}
	<div class="caption640" style="border:1px solid silver;padding:10px;">{$comment}</div>
{/if}
{if $user_id}
<div style="text-align:center;position:relative;"><b>by <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></b></div>
{/if}

{if $others || $related} 
	<div style="float:right;position:relative;padding:8px; border-left:2px solid gray;width:250px">
		<div style="float:right;height:300px;"></div>
		{if $others}
			<b>More nearby...</b>
			<ul style="padding:0 0 0 1em;">
				{foreach from=$others item=item}
					<li><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></li>
				{/foreach}
			</ul>
		{/if}
		{if $related}
			{if $hassame}
				<b>Others with same title</b>
				<ul style="padding:0 0 0 1em;">
					{foreach from=$related item=item}
						{if $item.title == $title}
						<li><b><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></b>
						<div style="font-size:0.7em;color:gray;margin-left:2px;">
						By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
						Used on {$item.images|thousends} images</div>
						</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
			
			{if $hassame < count($related)}
				<b>Related descriptions</b>
				<ul style="padding:0 0 0 1em;">
					{foreach from=$related item=item}
						{if $item.title != $title}
						<li style="padding-bottom:4px"><b><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></b>
						<div style="font-size:0.7em;color:gray;margin-left:2px;">
						By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
						Used on {$item.images|thousends} images</div>
						</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
			<small>Selection is automatic and approximate, it might not always select closely matching descriptions</small>
		{/if}
	</div>
	<div style="padding-right:260px">
{/if}

{if $images}
	{if $images > 25}
		<p><b><a href="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;do=1">{$images} images</a> use this description. Preview sample shown below:</b></p>
	{else}
		<p><b>{$images} image{if $images == 1} uses{else}s use{/if} this description:</b></p>
	{/if}
{/if}

	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full-size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{foreachelse}
		<p><i>No images to display{if $images}, this could be because they are still pending and/or recently rejected{/if}</i></p>
	{/foreach}
	<br style="clear:both"/>

	{if $images > 25}
		... and {$images-25} more images.
	{/if}
{if $others || $related}
	</div>
{/if}

<ul class="explore">
	{if $images > 2}
		<li class="interestBox"><a href="/browser/content-redirect.php?id={$snippet_id}&amp;source=snippet">View all images using <b>this description</b> in the <b>Browser</b></a>
			| <a href="/browser/content-redirect.php?id={$snippet_id}&amp;source=snippet&amp;map">On a <b>Map</b></a></li>
	{/if}

	{if $images && $images <= 1000}
		{if $title}

			<li class="interestBox"><a href="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;do=1"><b>View all images</b> using "{$title|escape:'html'}" Shared Description(s)</a>
			{if $images < 15}
				| <a href="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;do=1&displayclass=map">On a <b>Map</b></a>
			{/if}
			| (<a href="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;do=1">Just <i>this</i> shared description</a>)
			</li>
		{else}
			<li class="interestBox"><b><a href="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;do=1">List all {$images} images</a> using this description.</b></li>
		{/if}
	{/if}
	{if $grid_reference}
		<li class="interestBox">This description is located in {$grid_reference}, <a href="/gridref/{$grid_reference}/links"><img src="http://{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" style="padding:2px;" alt="More Links for {$grid_reference}"/></a> <a href="/gridref/{$grid_reference}/links">Links for <b>{$grid_reference}</b></a> | <a href="/gridref/{$grid_reference}"><b>Photos</b> for {$grid_reference}</a></li>
	{/if}
	{if $title}<li class="interestBox"><a href="/search.php?searchtext={$title|escape:'url'}&amp;gridref={$grid_reference}&amp;do=1">Find {if $grid_reference}nearby{/if} images <b>mentioning the words [ {$title|escape:'html'} ]</b></a> | (<a href="/browser/#!/q={$title|escape:'url'}/">in ther Browser</a>)</li>{/if}
</ul>

<br/>

<div class="interestBox" style="font-size:0.7em">These <a href="/article/Shared-Descriptions" title="read more about shared descriptions in our documentation section">Shared Descriptions</a> are common to multiple images. For example, you can create a generic description for an object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.</div>
<div style="color:silver;text-align:right;font-size:0.8em">Created: {$created|date_format:"%a, %e %b %Y"}, Updated: {$updated|date_format:"%a, %e %b %Y"}</div>

<p>The 'Shared Description' text on this page is Copyright {$created|date_format:"%Y"} <a href="/profile/{$user_id}">{$realname|escape:'html'}</a>, however it is specifically licensed so that contributors can reuse it on their own images without restriction.</p>

{include file="_std_end.tpl"}
