{include file="_std_begin.tpl"}

<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
{/literal}
</style>

<h2>Shared description</h2>



{if $comment}
	{dynamic}
		{if $user->registered}
			<div style="float:right;position:relative" id="votediv{$snippet_id}"><a href="javascript:void(record_vote('snip',{$snippet_id},5));" title="I like this description! - click to agree"><img src="{$static_host}/img/thumbs.png" width="20" height="20" alt="I like this description!"/></a></div>
		{/if}
	{/dynamic}
{/if}

<h2 align="center">{$title|escape:'html'|default:'Untitled'}</h2>


{if $comment}
<div style="margin:auto; padding:5px; margin-top:5px; margin-bottom:5px; text-align:center; max-width:80vw">{$comment}</div>


{/if}

{if $user_id}
<div style="text-align:center;position:relative;"><b>by <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></b></div>
{/if}

<br style="clear:both"/>
<div style="color:grey; float:right">Created: {$created|date_format:"%a, %e %b %Y"}, Updated: {$updated|date_format:"%a, %e %b %Y"}</div>
<br style="clear:both"/>

{dynamic cached_user_id=$user_id}
{if $is_mod || $user->user_id == $cached_user_id}
<div style="float:right"><a href="/snippets.php?edit[{$snippet_id}]=edit&amp;onlymine=on">Edit this Description</a></div>
{/if}
{/dynamic}

<br style="clear:both"/>

{if $images}
	{if $images > 25}
		<p><b><a href="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;do=1">{$images} images</a> use this description. Preview sample shown below:</b></p>
	{else}
		<p><b>{$images} image{if $images == 1} uses{else}s use{/if} this description:</b></p>
	{/if}
{/if}

	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:{$thumbw+10}px; height:{$thumbh+10}px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full-size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true,'loading="lazy" src')}</a></div>
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

<br style="clear:both"/>

<div class="threecolsetup">

<div class="threecolumn">
<h3>Shared descriptions</h3>


<h4>This shared description</h4>
<p>The 'Shared Description' text on this page is Copyright {$created|date_format:"%Y"} <a href="/profile/{$user_id}">{$realname|escape:'html'}</a>.<p>
<p>Shared descriptions are specifically licensed so that contributors can reuse them on their own images, without restriction.</p>


<h4>About shared descriptions</h4>

<p>These <a href="/article/Shared-Descriptions" title="read more about shared descriptions in our documentation section">Shared Descriptions</a> are common to multiple images.</p>
<p>For example, you can create a generic description for an object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.</p>

</div> 

<div class="threecolumn">
<h3>Explore images</h3>
  

<h4>View images using "{$title|escape:'html'}" Shared Description(s)</h4>
<ul class="buttonbar">
<li>
<select onchange="window.location.href=this.value" style="width:300px">
    <option>In the search</option>
    <optgroup label="Most recent first">
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=full&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=thumbsmore&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=bigger&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=grid&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=slide&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Map</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=black&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="One image per">
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;displayclass=full&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;do=1">Day taken</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;displayclass=full&amp;groupby=auser_id&amp;breakby=user_id&amp;do=1">Contributor</option>
        <option value="/search.php?searchtext=snippet_title%3A{$title|escape:'url'}&amp;displayclass=full&amp;groupby=scenti&amp;do=1">Centisquare</option>
    </optgroup>
</select>
</li>
<li><a href="/browser/content-redirect.php?id={$snippet_id}&amp;source=snippet">In the Browser</a></li>
</ul>

<h4>View images using just this shared description</h4>
<ul class="buttonbar">
<li>
<select onchange="window.location.href=this.value" style="width:300px">
    <option>In the search</option>
    <optgroup label="Most recent first">
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=full&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=thumbsmore&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=bigger&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=grid&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=slide&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Map</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=black&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="One image per">
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;displayclass=full&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;do=1">Day taken</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;displayclass=full&amp;groupby=auser_id&amp;breakby=user_id&amp;do=1">Contributor</option>
        <option value="/search.php?searchtext=snippet_id%3A{$snippet_id}&amp;displayclass=full&amp;groupby=scenti&amp;do=1">Centisquare</option>
    </optgroup>
</select>
</li>
</ul>

{if $title}
<h4>View images mentioning the words [{$title|escape:'html'}]</h4>
<ul class="buttonbar">
<li>
<select onchange="window.location.href=this.value" style="width:300px">
    <option>In the search</option>
    <optgroup label="Most recent first">
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=full&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=thumbs&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=thumbsmore&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=bigger&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=grid&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=slide&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=map&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Map</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=black&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="One image per">
        <option value="/search.php?searchtext={$title|escape:'url'}&amp;displayclass=full&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;do=1">Day taken</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&amp;displayclass=full&amp;groupby=auser_id&amp;breakby=user_id&amp;do=1">Contributor</option>
        <option value="/search.php?searchtext={$title|escape:'url'}&amp;displayclass=full&amp;groupby=scenti&amp;do=1">Centisquare</option>
    </optgroup>
</select>
</li>
<li><a href="/browser/#!/q={$title|escape:'url'}/">In the Browser</a></li>
</ul>
{/if}

{if $grid_reference}
<h4>Links for {$grid_reference}</h4>
<p>This description is located in {$grid_reference}.</p>

<ul class="buttonbar">
<li><a href="/gridref/{$grid_reference}">Browse page for {$grid_reference}</a></li>

<li>
<select onchange="window.location.href=this.value" style="width:300px">
    <option>Search for images in {$grid_reference}</option>
    <optgroup label="Most recent first">
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=full&amp;orderby=submitted&reverse_order_ind=1&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=thumbs&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=thumbsmore&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=bigger&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=grid&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=slide&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=map&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=black&reverse_order_ind=1&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="Oldest first">
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=full&amp;orderby=submitted&amp;do=1">Full details</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=thumbs&amp;orderby=submitted&amp;do=1">Thumbnails</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=thumbsmore&amp;orderby=submitted&amp;do=1">Thumbnails + links</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=bigger&amp;orderby=submitted&amp;do=1">Thumbnails - bigger</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=grid&amp;orderby=submitted&amp;do=1">Thumbnails grid</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1">Slideshow</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=map&amp;orderby=submitted&amp;do=1">Map</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=black&amp;orderby=submitted&amp;do=1">Georiver</option>
    </optgroup>
    <optgroup label="One image per">
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=full&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;do=1">Day taken</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=full&amp;groupby=auser_id&amp;breakby=user_id&amp;do=1">Contributor</option>
        <option value="/search.php?gridref={$grid_reference}&amp;distance=1&amp;displayclass=full&amp;groupby=scenti&amp;do=1">Centisquare</option>
    </optgroup>
</select>
</li>

<li><a href="/gridref/{$grid_reference}/links"><img src="{$static_host}/img/geotag_32.png" width="20" height="20" align="absmiddle" style="padding:2px;" alt="More Links for {$grid_reference}"/></a> <a href="/gridref/{$grid_reference}/links">More links for {$grid_reference}</a></li>
</ul>
{/if}




</div>
  







<div class="threecolumn">
<h3>Other shared descriptions</h3>
{if $others || $related}   
		{if $others}
			<h4>Descriptions nearby</h4>
			<ul style="padding:0 0 0 1em;">
				{foreach from=$others item=item}
					<li><a href="/snippet/{$item.snippet_id}">{$item.title|escape:'html'}</a></li>
				{/foreach}
			</ul>
		{/if}
		{if $related}
			{if $hassame}
				<h4>Others with same title</h4>
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
				<h4>Related descriptions</h4>
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
			<p>The above selections are automatic and approximate, it might not always select closely matching descriptions</p>
		{/if}
{else}
<p>Search for other <a href="/snippets.php">Shared Descriptions</a>.</p>
{/if}


</div>



</div>




<br style="clear:both"/>



{include file="_std_end.tpl"}
