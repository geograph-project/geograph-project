{include file="_std_begin.tpl"}

{if $image}

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="{$sitemap}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
 {place place=$place h3=true}
{/if}

{if $image->moderation_status eq 'rejected'}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">

<h3 style="color:black"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Rejected</h3>

<p>This photograph has been rejected by the site moderators, and is only viewable by you.</p>

<p>You can find any messages related to this image on the <a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">edit page</a>, where you can reply or raise new concerns in the "Please tell us what is wrong..." box. These will be communicated to site moderators. You may also like to read this general article on common <a href="/article/Reasons-for-rejection">reasons for rejection</a>.

</div>
<br/>
{/if}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	{if $image->original_width}
		<div class="caption640" style="text-align:center;"><a href="{$image->_getOriginalpath(false,true)}">view large version ({$image->original_width} x {$image->original_height})</a></div>
	{/if}
  <div class="img-shadow" id="mainphoto" itemscope itemtype="http://schema.org/ImageObject">{$image->getFull()|replace:'/>':' itemprop="contentURL"/>'}<meta itemprop="representativeOfPage" content="true"/></div>

  <div class="caption640" style="font-weight:bold" xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title" itemprop="name">{$image->title|escape:'html'}</div>

  {if $image->comment}
  <div class="caption640" itemprop="description">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
  {/if}
  {if $image->snippet_count}
	{if !$image->comment && $image->snippet_count == 1}
		{assign var="item" value=$image->snippets[0]}
		<div class="caption640" itemprop="description">
		{$item.comment|escape:'html'|nl2br|geographlinks}{if $item.title}<br/><br/>
		<small>See other images of <a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname ne $image->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'}</a></small>{/if}
		</div>
	{else}
		{foreach from=$image->snippets item=item name=used}
			{if !$image->snippets_as_ref && !$item.comment}
				<div class="caption640 searchresults"><br/>
				<small>See other images of <a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname ne $image->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'}</a></small>
				</div>
			{else}
				<div class="snippet640 searchresults" id="snippet{$smarty.foreach.used.iteration}">
				{if $image->snippets_as_ref}{$smarty.foreach.used.iteration}. {/if}<b><a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname ne $image->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'|default:'untitled'}</a></b> {if $item.grid_reference && $item.grid_reference != $image->grid_reference}<small> :: <a href="/gridref/{$item.grid_reference}">{$item.grid_reference}</a></small>{/if}
				<blockquote>{$item.comment|escape:'html'|nl2br|geographlinks|hidekeywords}</blockquote>
				</div>
			{/if}
		{/foreach}
	{/if}

  {/if}
</div>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="http://{$static_host}/img/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL">{$image->realname|escape:'html'}</a> and
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="{$image->_getFullpath(true,true)}" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->

{if $image_taken && $image->imagetaken > 1}
<div class="keywords yeardisplay" title="year photo was taken">year taken <div class="year">{$image->imagetaken|truncate:4:''}</div></div>
{/if}

<div class="buttonbar" style="border:1px solid silver">
</div>




<div class="picinfo">

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative">
	{$rastermap->getImageTag($image->subject_gridref)}
	{if $rastermap->getFootNote()}
	<div class="interestBox" style="margin-top:3px;margin-left:2px;padding:1px;"><small>{$rastermap->getFootNote()}</small></div>
	{/if}
	</div>

	{$rastermap->getScriptTag()}
{else}
	<div class="rastermap" style="width:{$rastermap->width}px;height:{$rastermap->width}px;position:relative">
		Map Coming Soon...

	</div>
{/if}

<div style="float:left;position:relative"><dl class="picinfo" style="margin-top:0px">



<dt>Grid Square</dt>
 <dd><a title="Grid Reference {$image->grid_reference}" href="{$sitemap}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if}
</dd>

{if $image->credit_realname}
	<dt>Photographer</dt>
	 <dd property="dc:creator" itemprop="author">{$image->realname|escape:'html'}</dd>

	<dt>Contributed by</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}" itemprop="publisher">{$image->user_realname|escape:'html'}</a></dd>
{else}
	<dt>Photographer</dt>
	 <dd><a title="View profile" href="{$image->profile_link}" property="dc:creator" itemprop="author" rel="author">{$image->realname|escape:'html'}</a></dd>
{/if}

<dt>Image classification</dt>
<dd>{if $image->ftf eq 1}
	Geograph (First for {$image->grid_reference})
{elseif $image->ftf eq 2}
	Geograph (Second Visitor for {$image->grid_reference})
{elseif $image->ftf eq 3}
	Geograph (Third Visitor for {$image->grid_reference})
{elseif $image->ftf eq 4}
	Geograph (Fourth Visitor for {$image->grid_reference})
{else}
	{if $image->moderation_status eq "rejected"}
	Rejected
	{/if}
	{if $image->moderation_status eq "pending"}
	Awaiting moderation
	{/if}
	{if $image->moderation_status eq "geograph"}
	Geograph
	{/if}
	{if $image->moderation_status eq "accepted"}
	Supplemental image
	{/if}
{/if}
{if strpos($image->points,'tpoint') !== false}
<br/>First in 5 Years (TPoint) <sup><a href="/faq3.php?q=tpoint#61" class="about" style="font-size:0.7em">?</a></sup>{/if}</dd>


{if $image_taken}
	<dt>Date Taken</dt>
	<dd><span itemprop="exifData">{$image_taken}</span></dd>
{/if}
<dt>Submitted</dt>
	<dd itemprop="uploadDate" datetime="{$image->submitted|replace:' ':'T'}Z">{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>


{if $image->tags}
	{if $image->tag_prefix_stat.top}
		<dt>Geographical Context</dt>
		<dd style="width:256px" class="tags" itemprop="keywords">
			{foreach from=$image->tags item=item name=used}{if $item.prefix eq 'top'}
			<span class="tag">
			<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|escape:'html'}</a></span>&nbsp;
		{/if}{/foreach}</dd>
	{/if}

	{foreach from=$image->tag_prefix_stat key=prefix item=count}
		{if $prefix ne 'top' && $prefix ne '' && $prefix ne 'term' && $prefix ne 'cluster' && $prefix ne 'wiki'}
			{if $prefix == 'bucket'}
				<dt>Image Buckets <sup><a href="/article/Image-Buckets" class="about" style="font-size:0.7em">?</a></sup></dt>
			{else}
				<dt>{$prefix|capitalize|escape:'html'} (from Tags)</dt>
			{/if}
			<dd style="width:256px;font-size:0.9em" class="tags" itemprop="keywords">
			{foreach from=$image->tags item=item name=used}{if $item.prefix == $prefix}
				<span class="tag">
				<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|escape:'html'}</a></span>&nbsp;
			{/if}{/foreach}</dd>
		{/if}
	{/foreach}
{/if}

{if $image->imageclass}
	<dt>Category</dt>

	<dd>{if $image->canonical}
		{$image->canonical|escape:'html'} &gt;
	{/if}
	<span itemprop="keywords">{$image->imageclass}</span>
	</dd>
{/if}

<dt>Subject Location</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$image->subject_gridref} [{$image->subject_gridref_precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude"
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>

{if $image->photographer_gridref}
<dt>Photographer Location</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: {$image->photographer_gridref}</dd>
{/if}

{if $view_direction && $image->view_direction != -1}
<dt>View Direction</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{$view_direction} (about {$image->view_direction} degrees)</dd>
{/if}

</dl>

</div>

{if $overview}
  <div style="float:left; text-align:center; width:{$overview_width}px; position:relative">
	{include file="_overview.tpl"}
	<div style="width:inherit;margin-left:20px;"><br/>

	<a href="{$sitemap}">Text listing of Images in {$image->grid_reference}</a><br/><br/>


{if $image->tags && ($image->tag_prefix_stat.$blank || $image->tag_prefix_stat.term || $image->tag_prefix_stat.cluster || $image->tag_prefix_stat.wiki)}
	<p style="margin-top:0px">
	<b>Other Tags</b><br/><span class="tags" itemprop="keywords">
	{foreach from=$image->tags item=item name=used}{if $item.prefix eq '' || $item.prefix eq 'term' || $item.prefix eq 'cluster' || $item.prefix eq 'wiki'}
		<span class="tag"><a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|lower|escape:'html'}</a></span>&nbsp;
	{/if}{/foreach}</span>
	</p>
{/if}



	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>
{if $image->hits}
	<div class="hits">This page has been <a href="/help/hit_counter">viewed</a> about <b>{$image->hits}</b> times.</div>
	<br/>
{/if}







{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
