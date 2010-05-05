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
						<li><b><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></b>
						<div style="font-size:0.7em;color:gray;margin-left:2px;">
						By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
						Used on {$item.images|thousends} images</div>
						</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
		{/if}
	</div>
{/if}

{if $images}
	{if $images > 25}
		<p><b><a href="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;do=1">{$images} images</a> use this description. Preview shown below:</b></p>
	{else}
		<p><b>{$images} image{if $images == 1} uses{else}s use{/if} this description:</b></p>
	{/if}
{/if}

	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>
	{foreachelse}
		<p><i>no images to display{if $images}, this could be because still pending and/or recently rejected{/if}</i></p>
	{/foreach}
	<br style="clear:both"/>


<ul class="explore">
	{if $images && $title}
		<li class="interestBox"><a href="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;do=1"><b>View all images</b> using this description</a>
		{if $images < 15}
			| <a href="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;do=1&displayclass=gmap">On a <b>Map</b></a>
		{/if}
		</li>
	{/if}
	{if $grid_reference}
		<li class="interestBox"><a href="/gridref/{$grid_reference}/links"><img src="http://{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" style="padding:2px;" alt="More Links for {$grid_reference}"/></a> <a href="/gridref/{$grid_reference}/links">Links for <b>{$grid_reference}</b></a> | <a href="/gridref/{$grid_reference}"><b>Photos</b> for {$grid_reference}</a></li>
	{/if}
	{if $title}<li class="interestBox"><a href="/search.php?searchtext={$title|escape:'url'}&amp;gridref={$grid_reference}&amp;do=1">Find {if $grid_reference}nearby{/if} images <b>mentioning the words [ {$title|escape:'html'} ]</b></a></li>{/if}
</ul>

<br/>

<div class="interestBox" style="font-size:0.7em">These <a href="/article/Shared-Descriptions" title="read more about shared descriptions in our documentation section">shared descriptions</a> are common to multiple images. For example can create a generic description for a object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.</div>
<div style="color:silver;text-align:right;font-size:0.8em">Created: {$created|date_format:"%a, %e %b %Y"}, Updated: {$updated|date_format:"%a, %e %b %Y"}</div>

{include file="_std_end.tpl"}
