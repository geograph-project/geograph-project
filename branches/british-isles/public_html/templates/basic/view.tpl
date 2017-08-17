{include file="_std_begin.tpl"}

{if $image}
<div style="float:right; position:relative; width:5em; height:4em;"></div>
<div style="float:right; position:relative; width:2.5em; height:1em;"></div>
<div itemscope itemtype="schema.org/Photograph"><meta itemprop="isFamilyFriendly" content="true"/>
<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
 {place place=$place h3=true takenago=$takenago}
{/if}

{if $image->moderation_status eq 'rejected'}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">

<h3 style="color:black"><img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Rejected</h3>

<p>This photograph has been rejected by the site moderators, and is only viewable by you.</p>

<p>You can find any messages related to this image on the <a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">edit page</a>, where you can reply or raise new concerns in the "Please tell us what is wrong..." box. These will be communicated to site moderators. You may also like to read this general article on common <a href="/article/Reasons-for-rejection">reasons for rejection</a>.

</div>
<br/>
{/if}
{dynamic}
{if $current_search}
	<div class="interestBox" style="text-align:center; font-size:0.9em;width:400px;margin-left:auto;margin-right:auto">
		{if $current_search.l}
			<a href="/photo/{$current_search.l}">&lt; prev image</a>
		{elseif $current_search.c > 1}
                        <a href="/search.php?i={$current_search.i}&amp;page={$current_search.p-1}">&lt; prev page</a>
                {else}
			<s style="color:silver" title="first image on this page - you may be able to get to another page via the 'back to search results' itself">&lt; prev image</s>
		{/if} |
		<a href="/search.php?i={$current_search.i}&amp;page={$current_search.p}"><b>back to search results</b></a> |
		{if $current_search.n}
			<a href="/photo/{$current_search.n}">next image &gt;</a>
		{elseif $current_search.c < $current_search.t}
                        <a href="/search.php?i={$current_search.i}&amp;page={$current_search.p+1}">next page &gt;</a>
                {else}
			<s style="color:silver" title="last image on this page - you may be able to get to another page via the 'back to search results' itself">next image &gt;</s>
		{/if}
	</div>
{elseif $search_keywords && $search_count}
	<div class="interestBox" style="text-align:center; font-size:0.9em">
		{if !$user->registered}
		<div style="width:640px;margin-left:auto;margin-right:auto"><i>The Geograph Britain and Ireland project aims to collect geographically representative photographs and information for every square kilometre of Great Britain and Ireland, and you can be part of it.</i> <br/><a href="/faq.php">Read more...</a></div><br/>
		{/if}

		<b>We have at least <b>{$search_count} images</b> that match your query [{$search_keywords|escape:'html'}] in the area! <a href="/search.php?searchtext={$search_keywords|escape:'url'}&amp;gridref={$image->grid_reference}&amp;do=1&amp;form=external">View them now</a></b>
	</div>
{/if}
{/dynamic}
{if $ireland_prompt}
	<div class="interestBox" style="text-align:center; font-size:0.9em">
		<a href="http://www.geograph.ie/photo/{$image->gridimage_id}" title="View {$image->bigtitle|escape:'html'} on Geograph Ireland">View this photo on Geograph Ireland</a>
	</div>
{/if}

<div about="{$imageurl}" xmlns:dct="http://purl.org/dc/terms/" xmlns:cc="http://creativecommons.org/ns#">
<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	{if $image->original_width}
		<div class="caption640" style="text-align:right;"><a href="/more.php?id={$image->gridimage_id}">More sizes</a></div>
	{elseif $user->user_id eq $image->user_id}
		<div class="caption640" style="text-align:right;"><a href="/resubmit.php?id={$image->gridimage_id}">Upload a larger version</a></div>
	{/if}

  {dynamic}
    {if $user->registered || !$is_bot}

        <div style="float:left;position:relative;width:30px;height:20px"></div>
	<div class="interestBox" style="float:right;position:relative;width:20px"><span  id="hideside"></span>
		<img src="{$static_host}/img/thumbs.png" width="20" height="20" onmouseover="show_tree('side'); if (!loadedBuckets || loadedBuckets.length == 0) jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js', function() {literal}{{/literal} refreshMainList({$image->gridimage_id}); {literal}});{/literal}"/>
	</div>

	<div style="float:right;position:relative">
	<div style="position:absolute;left:-210px;top:-20px;width:220px;padding:10px;display:none;text-align:left" id="showside" onmouseout="hide_tree('side')">
		<div class="interestBox" onmousemove="event.cancelBubble = true" onmouseout="event.cancelBubble = true">
			<div id="votediv{$image->gridimage_id}img"><a href="javascript:void(record_vote('img',{$image->gridimage_id},5,'img'));" title="I like this image! - click to agree">I like this image!</a></div>
			{if $image->comment}
				<div id="votediv{$image->gridimage_id}desc"><a href="javascript:void(record_vote('desc',{$image->gridimage_id},5,'desc'));" title="I like this description! - click to agree">I like this description!</a></div>
			{/if}
			<br/>
			<b>Image Buckets</b><br/>
			{foreach from=$buckets item=item}
					<label id="{$image->gridimage_id}label{$item|escape:'html'}" for="{$image->gridimage_id}check{$item|escape:'html'}" style="color:gray">
					<input type=checkbox id="{$image->gridimage_id}check{$item|escape:'html'}" onclick="submitBucket({$image->gridimage_id},'{$item|escape:'html'}',this.checked?1:0,this.value,{if $user->user_id eq $image->user_id}1{else}0{/if});"> {$item|escape:'html'}
					{if $item == 'CloseCrop'} (was Telephoto){/if}
					</label><br/>

			{/foreach}<br/>
			<small>IMPORTANT: Please read the {newwin href="/article/Image-Buckets" title="Article about Buckets" text="Buckets Article"} before picking from this list</small>

		</div>
	</div>
	</div>

    {/if}
  {/dynamic}

  <div class="img-shadow" id="mainphoto" itemscope itemtype="http://schema.org/ImageObject">{$image->getFull(true,true)|replace:'/>':' itemprop="contentURL"/>'}<meta itemprop="representativeOfPage" content="true"/></div>

  <div class="caption640"><strong property="dct:title" itemprop="name">{$image->title|escape:'html'}</strong></div>

  {if $image->comment}
  <div class="caption640" itemprop="description">{$image->comment|escape:'html'|nl2br|geographlinks:$expand}</div>
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
				<blockquote>{$item.comment|escape:'html'|nl2br|geographlinks}</blockquote>
				</div>
			{/if}
		{/foreach}
	{/if}

  {/if}
</div>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a> and
licensed for <a href="/reuse.php?id={$image->gridimage_id}">reuse</a> under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="{$imageurl}" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->
</div>

<div class="buttonbar">

<table style="width:100%">
{if $image->gridimage_id && ($image->moderation_status eq "geograph" || $image->moderation_status eq "accepted")}
<tr>
<td colspan="6" align="center" style="padding:5px;border-bottom:1px solid gray;font-size:0.8em;">
	&middot; <a href="/reuse.php?id={$image->gridimage_id}">Find out <b>How to reuse</b> this image</a> &middot;

	Share:
	<a title="Share this photo via Twitter" href="https://twitter.com/intent/tweet?text={$image->title|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}&url={$self_host}/photo/{$image->gridimage_id}" onclick="window.open(this.href,'share','width=500;height=400'); return false;"><img alt="Twitter" src="{$static_host}/img/twitter_16.png" width=16 height=16 style="vertical-align:middle"></a>
	<a title="Share this photo via Facebook" href="https://www.facebook.com/sharer/sharer.php?u={$self_host}/photo/{$image->gridimage_id}" onclick="window.open(this.href,'share','width=500;height=400'); return false;"><img alt="Facebook" src="{$static_host}/img/facebook_16.png" width=16 height=16 style="vertical-align:middle"></a>
	<a title="Share this photo via Google Plus" href="https://plus.google.com/share?url={$self_host}/photo/{$image->gridimage_id}&t={$image->title|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}" onclick="window.open(this.href,'share','width=500;height=400'); return false;"><img alt="Google Plus" src="{$static_host}/img/googleplus_16.png" width=16 height=16 style="vertical-align:middle"></a>
	<a title="Share this photo via Pinterest" href="http://www.pinterest.com/pin/create/button/?media={$imageurl}&url={$self_host}/photo/{$image->gridimage_id}&description={$image->title|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}" onclick="window.open(this.href,'share','width=500;height=400'); return false;"><img alt="Pinterest" src="{$static_host}/img/pinterest_16.png" width=16 height=16 style="vertical-align:middle"></a>
	<a title="Share this photo via Flipboard" href="https://share.flipboard.com/bookmarklet/popout?v=2&title={$image->title|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}&url={$self_host}/photo/{$image->gridimage_id}" onclick="window.open(this.href,'share','width=500;height=400'); return false;"><img alt="Flipboard" src="{$static_host}/img/flipboard_16.png" width=16 height=16 style="vertical-align:middle"></a>
	<a title="Send an this via email/e-card" href="/ecard.php?image={$image->gridimage_id}"><img src="{$static_host}/img/email_16.png" width=16 height=16 style="vertical-align:middle"></a> &middot;

	<img src="{$static_host}/img/download_16.png" width=16 height=16 style="vertical-align:middle"> <b><a href="/{if $image->original_width}more{else}reuse{/if}.php?id={$image->gridimage_id}">Download Image</a></b> &middot;
</td>
{/if}
</tr>
<tr>
{if $enable_forums}
<td width="11%" align="right"><a href="/discuss/index.php?gridref={$image->grid_reference}"><img src="{$static_host}/templates/basic/img/icon_discuss.gif" alt="Discuss" width="40" height="34" style="vertical-align:middle"/></a></td>
<td style="font-size:0.8em;vertical-align:middle" align="left" width="22%">
{if $discuss}
	There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a
	<a href="/discuss/index.php?gridref={$image->grid_reference}">discussion<br/>on {$image->grid_reference}</a> (preview on the left)
{else}
	<a href="/discuss/index.php?gridref={$image->grid_reference}">Start a discussion on {$image->grid_reference}</a>
{/if}
</td>
{/if}

<td width="11%" align="right"><a {if $image->gridimage_id}href="/editimage.php?id={$image->gridimage_id}"{/if}><img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="40" height="34" style="vertical-align:middle"/></a></td>
<td style="font-size:0.8em;vertical-align:middle" align="left" width="22%">
	{if $user->user_id eq $image->user_id}
		<big><a {if $image->gridimage_id}href="/editimage.php?id={$image->gridimage_id}"{/if}><b>Change Image Details</b></a></big><br/>
		(or raise a query with a moderator)
	{else}
		<a {if $image->gridimage_id}href="/editimage.php?id={$image->gridimage_id}"{/if}>Suggest an update to this image</a>
	{/if}
</td>
{if $user->user_id ne $image->user_id}
<td width="11%" align="right"><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}"><img  src="{$static_host}/templates/basic/img/icon_email.gif" alt="Email" width="40" height="34" style="vertical-align:middle"/></a></td>
<td style="font-size:0.8em;vertical-align:middle" align="left" width="22%">
	<a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the contributor</a>
</td>
{/if}
</tr>
</table>

</div>

{if !$image->tags && $user->user_id eq $image->user_id}
	<div style="text-align:right;font-size:0.8em" id="hidetag"><a href="#" onclick="document.getElementById('tagframe').src='/tags/tagger.php?gridimage_id={$image->gridimage_id}';show_tree('tag');return false;">Open <b>Tagging</b> Box</a></div>

	<div class="interestBox" id="showtag" style="display:none">
		<iframe src="about:blank" height="300" width="100%" id="tagframe">
		</iframe>
		<div><a href="#" onclick="hide_tree('tag');return false">- Close <i>Tagging</I> box</a> ({newwin href="/article/Tags" text="Article about Tags"})</div>
	</div>
{/if}


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
		Map coming soon...
	</div>
{/if}

<div style="float:left;position:relative"><dl class="picinfo" style="margin-top:0px">



<dt>Grid Square</dt>
 <dd><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if} &nbsp; (<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->subject_gridref|escape:'url'}" rel="nofollow">more nearby</a>
	<a href="/browser/#!/loc={$image->subject_gridref|replace:' ':''|escape:'url'}/dist=2000" title="view area in Browser"><img src="{$static_host}/img/links/20/search.png" width=14 height=14></a>)
</dd>

{if $image->credit_realname}
	<dt>Photographer</dt>
	 <dd property="dc:creator" itemprop="author" rel="author">{$image->realname|escape:'html'} &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;searchtext=name:%22{$image->realname|escape:'url'}%22&amp;do=1" class="nowrap" rel="nofollow">find more nearby</a>)</dd>

	<dt>Contributed by</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}" itemprop="publisher">{$image->user_realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->user_realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
{else}
	<dt>Photographer</dt>
	 <dd><a title="View profile" href="{$image->profile_link}" property="dc:creator" itemprop="author" rel="author">{$image->realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
{/if}

{if $image_taken}
	<dt>Date Taken</dt>
	<dd title="{$takenago}"><span itemprop="exifData">{$image_taken}</span> &nbsp; (<a title="pictures near {$image->grid_reference} taken on {$image_taken}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;orderby=submitted&amp;taken_start={$image->imagetaken}&amp;taken_end={$image->imagetaken}&amp;do=1" class="nowrap" rel="nofollow">more nearby</a>)</dd>
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
		{if $prefix ne 'top' && $prefix ne '' && $prefix ne 'term' && $prefix ne 'cluster' && $prefix ne 'wiki' && $prefix ne 'type'}
			{if $prefix == 'bucket'}
				<dt>Image Buckets <sup><a href="/article/Image-Buckets" class="about" style="font-size:0.7em">?</a></sup></dt>
			{elseif $prefix == 'subject'}
				<dt>Primary Subject of Photo</dt>
			{else}
				<dt>{$prefix|capitalize|escape:'html'} (from Tags)</dt>
			{/if}
			<dd style="width:256px;font-size:0.9em" class="tags" itemprop="keywords">
			{foreach from=$image->tags item=item name=used}{if $item.prefix == $prefix}
				<span class="tag">
				<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span>&nbsp;
			{/if}{/foreach}</dd>
		{/if}
	{/foreach}
{/if}

{if $image->imageclass}
	<dt>Category</dt>

	<dd>{if $image->canonical}
		<a href="/search.php?gridref={$image->grid_reference}&amp;canonical={$image->canonical|escape:'url'}&amp;do=1">{$image->canonical|escape:'html'}</a> &gt;
	{/if}
	<span itemprop="keywords">{$image->imageclass}</span> &nbsp; (<a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;imageclass={$image->imageclass|escape:'url'}" rel="nofollow">more nearby</a>)
	</dd>
{/if}

<dt>Subject Location</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: <img src="{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$image->subject_gridref}/links">{$image->subject_gridref}</a> [{$image->subject_gridref_precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude"
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
  <div itemprop="contentLocation">
  <div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
    <meta itemprop="latitude" content="{$lat|string_format:"%.5f"}" />
    <meta itemprop="latitude" content="{$long|string_format:"%.5f"}" />
  </div>
  </div>
</dd>

{if $image->photographer_gridref}
<dt>Camera Location</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: <img src="{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$image->photographer_gridref}/links">{$image->photographer_gridref}</a></dd>
{/if}

{if $view_direction && $image->view_direction != -1}
<dt>View Direction</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{$view_direction} (about {$image->view_direction} degrees)</dd>
{/if}

</dl>

{if $image->grid_square->reference_index eq 1}
<div style="font-size:0.8em;text-align:center">Looking for a postcode? {external href="http://www.nearby.org.uk/coord.cgi?p=`$image->subject_gridref`&amp;f=lookup" text="Try this page"}</div>
{/if}

</div>

{if $overview}
  <div style="float:left; width:{$overview_width}px; position:relative">
	{include file="_overview.tpl"}
	<div style="width:inherit;margin-left:20px;"><br/>

{if $image->tags && ($image->tag_prefix_stat.$blank || $image->tag_prefix_stat.term || $image->tag_prefix_stat.cluster || $image->tag_prefix_stat.wiki)}
	<p style="margin-top:0px">
	<b>Other Tags</b><br/><span class="tags" itemprop="keywords">
	{foreach from=$image->tags item=item name=used}{if $item.prefix eq '' || $item.prefix eq 'term' || $item.prefix eq 'cluster' || $item.prefix eq 'wiki'}
		<span class="tag"><a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span>&nbsp;
	{/if}{/foreach}</span>
	</p>
	<small>Click a tag, to view other nearby images.</small>
{/if}



	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>

<div style="float:left;font-size: 0.8em;margin-bottom:2px;margin-left:5px">

{if $image->tags && $image->tag_prefix_stat.type}
	Image Type <a href="/article/Image-Type-Tags-update">(about)</a>:

	{assign var="seperator" value=""}

	{foreach from=$image->tags item=item name=used}{if $item.prefix eq 'type'}
		<a href="/tagged/type:{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" title="{$item.description|escape:'html'}">{$item.tag|lower|escape:'html'}</a>&nbsp;
	{assign var="seperator" value=","}{/if}{/foreach}

	{if !$seperator}
		{if $image->moderation_status ne "accepted"}{$image->moderation_status|ucfirst}{/if}
	{/if}

	{if $image->ftf eq 1}
		(First Geograph for {$image->grid_reference})
	{elseif $image->ftf eq 2}
		(Second Visitor for {$image->grid_reference})
	{elseif $image->ftf eq 3}
		(Third Visitor for {$image->grid_reference})
	{elseif $image->ftf eq 4}
		(Fourth Visitor for {$image->grid_reference})
	{/if}

{else}
	Image classification<a href="/faq.php#points">(about)</a>:
	{if $image->ftf eq 1}
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
{/if}

	{if strpos($image->points,'tpoint') !== false}
	&middot; First in 5 Years (TPoint) <a href="/faq3.php?q=tpoint#61">(about)</a>{/if}

</div>



{if $image->hits}
	<div class="hits">This page has been <a href="/help/hit_counter">viewed</a> about <b>{$image->hits}</b> times.</div>
{/if}
<div class="interestBox" style="text-align:center">View this location:

{if $image->moderation_status eq "geograph" || $image->moderation_status eq "accepted"}

<small style="font-size:0.8em"><a title="Open in Google Earth" href="{$self_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</small> &middot;
{external title="Open in Google Maps" href="http://www.google.co.uk/maps?q=`$lat`,`$long`&t=h&z=14" text="Google Maps"} &middot;
{external title="Open in Bing Maps" href="http://www.bing.com/maps?where1=`$lat`,`$long`&style=h&lvl=14" text="Bing Maps"} &middot;

{/if}

{if $rastermap->reference_index == 1}<a href="/mapper/?t={$map_token}&amp;gridref_from={$image->grid_reference}">OS Map Checksheet</a> &middot; {/if}

<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$image->grid_reference}">Geograph Map</a> &middot;

{if $image_taken}
	{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;taken=%Y-%m-%d"}
{/if}

<span class="nowrap"><img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$image->subject_gridref}/links?{$imagetakenurl}&amp;title={$image->title|escape:'url'}&amp;id={$image->gridimage_id}"><b>More Links for this image</b></a></span>
</div>

</div>
<div style="text-align:center;margin-top:3px" class="interestBox" id="styleLinks"></div>
<script type="text/javascript">
/* <![CDATA[ */
{literal}
function addStyleLinks() {
{/literal}
	document.getElementById('styleLinks').innerHTML = 'Background for photo viewing: <a hr'+'ef="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photowhite"} hidelink{/if}{/dynamic}">White</a> / <a hr'+'ef="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photoblack"} hidelink{/if}{/dynamic}">Black</a> / <a hr'+'ef="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex{dynamic}{if $maincontentclass eq "content_photogray"} hidelink{/if}{/dynamic}">Grey</a>';
{literal}
}
 AttachEvent(window,'load',addStyleLinks,false);


function redrawMainImage() {
	el = document.getElementById('mainphoto');
	el.style.display = 'none';
	el.style.display = '';
}
 AttachEvent(window,'load',redrawMainImage,false);
 AttachEvent(window,'load',showMarkedImages,false);
 AttachEvent(window,'load',function () {
		collapseSnippets({/literal}{$image->snippet_count}{literal});
	},false);

{/literal}
/* ]]> */
</script>



<div style="width:100%;position:absolute;top:0px;left:0px;height:0px">
	<div class="interestBox" style="float: right; position:relative; padding:2px;">
		<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}">NW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}">N</a></td>
		<td><a href="/browse.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}">NE</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}">W</a></td>
		<td><b>Go</b></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}">E</a></td></tr>
		<tr><td><a href="/browse.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}">SW</a></td>
		<td align="center"><a href="/browse.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}">S</a></td>
		<td align="right"><a href="/browse.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}">SE</a></td></tr>
		</table>
	</div>
	<div style="float:right">
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}" title="Add this image to your site marked list">Mark</a>]&nbsp;
	</div>
</div>


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
