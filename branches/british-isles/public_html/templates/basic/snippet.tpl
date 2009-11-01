{include file="_std_begin.tpl"}

{dynamic }
{if $is_mod || $user->user_id == $user_id}
<div style="float:right;position:relative"><a href="/snippets.php?edit[{$snippet_id}]=edit">Edit this Description</a></div>
{/if}
{/dynamic}

<h2>{$title|escape:'html'|default:'Untitled'} <small>:: Shared Description</small></h2>

<div style="text-align:right;position:relative;">By <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></div>
<div class="interestBox">

	<div>{$comment}</div>
	<div style="font-size:0.8em;margin-top:8px;border-top:1px solid silver"><a href="/gridref/{$grid_reference}/links"><img src="http://{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" style="padding:2px;" alt="More Links for {$grid_reference}"/></a> | <a href="/gridref/{$grid_reference}/links">Links for <b>{$grid_reference}</b></a> | <a href="/search.php?searchtext={$title|escape:'url'}&amp;gridref={$grid_reference}&amp;do=1">Find nearby images mentioning the words '{$title|escape:'html'}'</a> |</div>
</div>

{if $others} 
	<div style="float:right;position:relative; background-color:lightgreen;padding:8px">
		<b>More nearby...</b>
		<ul style="padding:0 0 0 1em;">
			{foreach from=$others item=item}
				<li><a href="/snippet.php?id={$item.snippet_id}">{$item.title|escape:'html'}</a></li>
			{/foreach}
		</ul>
	</div>
{/if}

{if $images}
	<p>{$images} image{if $images == 1} uses{else}s use{/if} this description{if $images > 10}. Preview shown below:{else}:{/if}</p>
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


<div class="interestBox" style="font-size:0.7em">These descriptions are common to multiple images. For example can create a generic description for a object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.</div>


{include file="_std_end.tpl"}