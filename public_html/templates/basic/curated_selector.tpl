{include file="_std_begin.tpl"}


<h2>Curated Images :: {$title}</h2>

<p>

Here are some images we have pre-selected, to be high quality and clearly show the specific topic. Geograph images are available reuse thanks to the Creative Commons licence, so can download these images for use in your projects or for example in creating teaching material

{dynamic}{if $user->registered}
<p>If you would like to help curate these images (which involves finding and sorting the best images on the various topics), <a href="education.php">click here</a>.</p>
{/if}{/dynamic}

<hr>

 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

{include file="_location-selector.tpl"}

<hr>

<div class=list>
{assign var=last value=""}
{foreach from=$images item=image}
	{if $image->head}
		{if $last}</div>{/if}
		{$image->head}
		<div class="thumbnails shadow" style="margin-left:80px;">
		{assign var=last value=$image->head}
	{/if}

	<div>
	        <a class=thumb href="/photo/{$image->gridimage_id}" title="{$image->title|escape:'html'} by {$image->realname|escape:'html'}">
        	        {$image->getThumbnail(213,160)}
	        </a>
		{if $image->download}
			<a class=download href="{$image->download|escape:'html'}" data-filename="geograph-{$image->gridimage_id}.jpg">download example image</a>
		{/if}
		{if $image->label}
			<a class=section href="{$image->link|escape:'html'}">{$image->label|escape:'html'} [{$image->images}]</a>
		{/if}
	</div>

{/foreach}
        {if $last}</div>{/if}
</div>

<p>Dont see the subject you interested in here? We have an <a href="/curated/sample.php">even bigger selection</a> of images available.</p>

<hr>

{include file="_download-function.tpl"}

{include file="_std_end.tpl"}


