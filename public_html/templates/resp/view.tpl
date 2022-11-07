{include file="_std_begin.tpl"}
<!--INFOLINKS_OFF-->

<div class="titlebar">
	<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
	{if $image->imagetaken && $image->imagetaken > 1000}
		<div class=numeric style="color:gray;font-size:clamp( 1rem , 2vw, 3rem );">{$image->imagetaken|date_format:"%Y"}</div>
	{/if}
</div>
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

<!-- ----------------------------------------------------- -->

<div style="text-align:center">
	<div class="shadow shadow_large" id="mainphoto">{$image->getFull(true,true)}</div>

	<div><strong>{$image->title|escape:'html'}</strong></div>

	{if $image->comment}
		<div class=caption>{$image->comment|escape:'html'|nl2br|geographlinks:$expand}</div>
	{/if}
</div>

<br>

<!-- Creative Commons Licence -->
<div class="ccmessage"><a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a> and
licensed for <a href="/reuse.php?id={$image->gridimage_id}">reuse</a> under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="{$imageurl}" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!-- ----------------------------------------------------- -->

{literal}<style>
/* todo, move this to modification.css, just here while in development! */

div.titlebar {
	display:flex;
        align-items:baseline;
	justify-content: space-between;
	gap:10px;
	padding-left:10px;
	padding-top:10px;
}
#maincontent h2 {
	padding:0;
}

div.caption {
	max-width:900px;
	margin:0 auto;
	font-size:1em;
}
div.ccmessage {
	font-size:1em;
	color:#000066;
}

.buttonbar { /* there is an existing buttonbar, which may need to check compatibltiy */
	background-color:white;
	padding:5px;
	display: flex;
	justify-content: space-between;
	flex-wrap:wrap;
	align-items:baseline;
	gap:6px;
	font-family:verdana, arial, sans serif; /* Georgia not great for numbers */
	font-size:0.9em; /* verdana is bit bigger than Georgia */
	font-weight:bold;
	max-width:1024px;
	margin:0 auto;
}
.buttonbar li {
	background-color:#eee;
	border-radius:10px;
	flex: auto ;
	list-style:none;
	text-align:center;
	padding:4px;
}
.buttonbar a {
	text-decoration:none;
}
.buttonbar select {
	background:none;
	border:0;
	color:blue;
	font-family:verdana, arial, sans serif;
	font-weight:bold;
}

.tagbar {
	margin-top:10px;
	text-align:center;
}
.tagbar a {
	border-radius:4px;
	text-decoration:none;
}
.tagbar small {
	color:gray;
	font-style:italic;
}
.tagbar > * {
	white-space:nowrap;
}

.detailbar {
        display: flex;
        justify-content: space-between;
	gap:10px;
	padding:10px;
	max-width:1024px;
	margin:0 auto;
}
div.rastermap {
	border:none;
	padding:0;
	margin:0 auto;
}
div.rastermap img[name=tile] {
	border:1px solid silver;
}
div.rastermap .footnote {
	background:none;
	color:gray;
        font-style:italic;
	padding:3px;
	text-align:center;
}
.detailbar .picinfo {
	margin-top:0;
}
.detailbar div.overview {
	min-width:200px;
}
.detailbar div.map {
	border: 1px solid #000066 !important;
	margin:0 auto !important;
}
.bottombar {
	margin:5px;
        display: flex;
	justify-content: space-between;
}
.numeric {
	font-family:verdana, arial, sans serif; /* Georgia not great for numbers */
	font-size:0.9em; /* verdana is bit bigger than Georgia */
}
@media only screen and (max-width: 912px) {
	div.titlebar {
		display:block;
	}
	.titlebar > * {
		display:inline;
	}
	.detailbar, .bottombar {
		flex-direction:column;
	}
}
</style>{/literal}

<ul class="buttonbar">
	{if $image->gridimage_id}
		<li><a href="/reuse.php?id={$image->gridimage_id}">Licencing</a>

		<li><a href="/{if $image->original_width}more{else}reuse{/if}.php?id={$image->gridimage_id}">Download</a>

		{if $image->original_width}
			<li><a href="/more.php?id={$image->gridimage_id}">More Sizes</a></li>
		{elseif $user->user_id eq $image->user_id}
			<li><a href="/resubmit.php?id={$image->gridimage_id}">Upload a larger version</a></li>
		{/if}

		{if $image->moderation_status eq "geograph" || $image->moderation_status eq "accepted"}
			<li><select onchange="window.open(this.value,'share','width=500;height=400'); return false;" style="width:80px">
				<option value="">Share...</option>
				<option value="https://twitter.com/intent/tweet?text={$image->title_utf8|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}&amp;url={$self_host}/photo/{$image->gridimage_id}">Share this photo via Twitter</option>
				<option value="https://www.facebook.com/sharer/sharer.php?u={$self_host}/photo/{$image->gridimage_id}">Share this photo via Facebook</option>
				<option value="http://www.pinterest.com/pin/create/button/?media={$imageurl}&amp;url={$self_host}/photo/{$image->gridimage_id}&amp;description={$image->title_utf8|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}">Share this photo via Pinterest</option>
				<option value="https://share.flipboard.com/bookmarklet/popout?v=2&amp;title={$image->title_utf8|escape:'urlplus'}+by+{$image->realname|escape:'urlplus'}&amp;url={$self_host}/photo/{$image->gridimage_id}">Share this photo via Flipboard</option>
				<option value="/ecard.php?image={$image->gridimage_id}">Share this photo via email/e-card</option>
				<option value="/stamp.php?id={$image->gridimage_id}">Grab a Stamped/Watermarked Image</option>
			</select></li>
		{/if}

		<li><a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}" title="Add this image to your site marked list">Mark</a>

		<li id="votediv{$image->gridimage_id}img"><img src="{$static_host}/img/thumbs.png" width="16" height="16" alt="thumbs up icon"/>
		<a href="javascript:void(record_vote('img',{$image->gridimage_id},5,'img'));">Like Image</a>

		{if $image->comment}
			<li id="votediv{$image->gridimage_id}desc"><img src="{$static_host}/img/thumbs.png" width="16" height="16" alt="thumbs up icon"/>
			<a href="javascript:void(record_vote('desc',{$image->gridimage_id},5,'desc'));">Like Description</a>
		{/if}
	{/if}

	{if $enable_forums}
		{if $discuss}
			<li><a href="/discuss/index.php?gridref={$image->grid_reference}">View Discussion</a>
		{else}
			<li><a href="/discuss/index.php?gridref={$image->grid_reference}">Discuss {$image->grid_reference}</a>
		{/if}
	{/if}

        {if $user->user_id eq $image->user_id}
                <li><a {if $image->gridimage_id}href="/editimage.php?id={$image->gridimage_id}"{/if}>Change Image Details</a>
        {else}
                <li><a href="/editimage.php?id={$image->gridimage_id}">Suggest an Update</a>
        {/if}

	{if $user->user_id ne $image->user_id}
	        <li><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact Contributor</a>
	{/if}

	<li><img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/>
			<select onchange="window.location.href=this.value" style=width:200px>
				<option value="">View this location on ...</option>

				<option value="/mapper/combined.php#14/{$lat}/{$long}">Geograph Coverage Map (includes OSM etc)</option>
				<option value="/gridref/{$image->subject_gridref}/links?{if $image_taken}&amp;taken={$image->imagetaken}{/if}&amp;title={$image->title|escape:'urlplus'}&amp;id={$image->gridimage_id}">Geograph Links Page</a>

				<option value="https://www.google.co.uk/maps?q={$lat},{$long}&amp;t=h&amp;z=14">Open in Google Maps</option>
				<option value="https://maps.google.com/maps?daddr=loc:{$lat},{$long}">Navigate to location with Google Maps</option>

				<option value="{$self_host}/photo/{$image->gridimage_id}.kml">Open in Google Earth Pro</option>
				<option value="https://earth.google.com/web/search/{$lat},{$long}/">Open in Google Earth for Web</option>

				<option value="https://www.bing.com/maps?where1={$lat},{$long}&amp;style=h&amp;lvl=14">Open in Bing Maps</option>
			</select></li>

	{dynamic}{if $mobile_browser}
		<li><a href="https://maps.google.com/maps?daddr=loc:{$lat},{$long}">Navigate</a>
	{/if}{/dynamic}

	{if $image->grid_square->reference_index eq 1}
		<li>{external href="http://www.nearby.org.uk/coord.cgi?p=`$image->subject_gridref`&amp;f=lookup" text="Lookup nearest Postcode"}</li>
	{/if}

</ul>

<!-- ----------------------------------------------------- -->

{if $image->tags || $image->imageclass}
	<div class=tagbar>
		{if $image->tag_prefix_stat.top}
			Geographical Context:
				{foreach from=$image->tags item=item name=used}{if $item.prefix eq 'top'}
				<span class="tag">
				<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|escape:'html'}</a></span>&nbsp;
			{/if}{/foreach}
		{/if}

		{foreach from=$image->tag_prefix_stat key=prefix item=count}
			{if $prefix ne 'top' && $prefix ne '' && $prefix ne 'term' && $prefix ne 'cluster' && $prefix ne 'wiki' && $prefix ne 'type'}
				{if $prefix == 'bucket'}
					Image Buckets <sup><a href="/article/Image-Buckets" class="about" style="font-size:0.7em">?</a></sup>:
				{elseif $prefix == 'subject'}
					Primary Subject:
				{else}
					{$prefix|capitalize|escape:'html'}:
				{/if}
				{foreach from=$image->tags item=item name=used}{if $item.prefix == $prefix}
					<span class="tag">
					<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span>&nbsp;
				{/if}{/foreach}
			{/if}
		{/foreach}

		{if $image->imageclass}
			Category:

			{if $image->canonical}
				<a href="/search.php?gridref={$image->grid_reference}&amp;canonical={$image->canonical|escape:'url'}&amp;do=1">{$image->canonical|escape:'html'}</a> &gt;
			{/if}
			<a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;imageclass={$image->imageclass|escape:'url'}" rel="nofollow">{$image->imageclass|escape:'html'}</a>
		{/if}

		{if $image->tags && ($image->tag_prefix_stat.$blank || $image->tag_prefix_stat.term || $image->tag_prefix_stat.cluster || $image->tag_prefix_stat.wiki)}
			{foreach from=$image->tags item=item name=used}{if $item.prefix eq '' || $item.prefix eq 'term' || $item.prefix eq 'cluster' || $item.prefix eq 'wiki'}
				<span class="tag"><a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$image->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span>&nbsp;
			{/if}{/foreach}

			<small>Click a tag, to view other nearby images.</small>
		{/if}
	</div>
{/if}

{if !$image->tags && $user->user_id eq $image->user_id}
	<div style="text-align:center" id="hidetag"><a href="#" onclick="document.getElementById('tagframe').src='/tags/tagger.php?gridimage_id={$image->gridimage_id}';show_tree('tag');return false;">Open <b>Tagging</b> Box</a></div>

	<div class="interestBox" id="showtag" style="display:none">
		<iframe src="about:blank" height="300" width="100%" id="tagframe">
		</iframe>
		<div><a href="#" onclick="hide_tree('tag');return false">- Close <i>Tagging</I> box</a> ({newwin href="/article/Tags" text="Article about Tags"})</div>
	</div>
{/if}

<!-- ----------------------------------------------------- -->

<div class="detailbar">
	{if $rastermap->enabled}
	        <div class="rastermap" style="width:{$rastermap->width}px; position:relative">
		        {$rastermap->getImageTag($image->subject_gridref)}
			{if $rastermap->getFootNote()}
				<div class=footnote>{$rastermap->getFootNote()}</div>
		        {/if}
	        </div>

	        {$rastermap->getScriptTag()}
	{else}
	        <div class="rastermap" style="width:{$rastermap->width}px;height:{$rastermap->width}px;position:relative">
	                Map coming soon...
	        </div>
	{/if}

<!-- ----------------------------------------------------- -->

	<dl class="picinfo">

		<dt>Grid Square</dt>
			<dd class=numeric><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if} &nbsp; (<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->subject_gridref|escape:'url'}" rel="nofollow">more nearby</a>
				<a href="/browser/#!/loc={$image->subject_gridref|replace:' ':''|escape:'url'}/dist=2000" title="view area in Browser"><img src="{$static_host}/img/links/20/search.png" width="14" height="14" alt="search"/></a>)
			</dd>

		{if $image->credit_realname}
			<dt>Photographer</dt>
				<dd>{$image->realname|escape:'html'} &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;searchtext=name:%22{$image->realname|escape:'url'}%22&amp;do=1" class="nowrap" rel="nofollow">more nearby</a>)</dd>

			<dt>Contributed by</dt>
				<dd><a title="View profile" href="/profile/{$image->user_id}">{$image->user_realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->user_realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">more nearby</a>)</dd>
		{else}
			<dt>Photographer</dt>
				<dd><a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">more nearby</a>)</dd>
		{/if}

		{if $image_taken}
			<dt>Date Taken</dt>
				<dd class=numeric title="{$takenago}">{$image_taken} &nbsp; (<a title="pictures near {$image->grid_reference} taken on {$image_taken}" href="/search.php?gridref={$image->subject_gridref|escape:'url'}&amp;orderby=submitted&amp;taken_start={$image->imagetaken}&amp;taken_end={$image->imagetaken}&amp;do=1" class="nowrap" rel="nofollow">more nearby</a>)</dd>
		{/if}

		<dt>Submitted</dt>
			<dd class=numeric>{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>

		<dt>Subject Location</dt>
			<dd class=numeric>
			{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: <img src="{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$image->subject_gridref}/links">{$image->subject_gridref}</a> [{$image->subject_gridref_precision}m precision]<br/>
			WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude"
			title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
			</dd>

		{if $image->photographer_gridref}
			<dt>Camera Location</dt>
				<dd class=numeric>
				{if $image->grid_square->reference_index eq 1}OSGB36{else}Irish{/if}: <img src="{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$image->photographer_gridref}/links">{$image->photographer_gridref}</a></dd>
		{/if}

		{if $view_direction && $image->view_direction != -1}
			<dt>View Direction</dt>
				<dd class=numeric>
				{$view_direction} (about {$image->view_direction} degrees)</dd>
		{/if}
	</dl>

<!-- ----------------------------------------------------- -->

	{if $overview}
		<div class="overview">
		        {include file="_overview.tpl"}
		</div>
	{/if}
</div>


<!-- ----------------------------------------------------- -->

<br style="clear:both"/>

<div class="bottombar">
	<div>
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
		        {elseif $image->moderation_status eq "rejected"}
		                Rejected
		        {elseif $image->moderation_status eq "pending"}
		                Awaiting moderation
		        {elseif $image->moderation_status eq "geograph"}
		                Geograph
		        {elseif $image->moderation_status eq "accepted"}
		                Supplemental image
		        {/if}
		{/if}

	        {if strpos($image->points,'tpoint') !== false}
		        &middot; First in 5 Years (TPoint) <a href="/faq3.php?q=tpoint#61">(about)</a>
		{/if}
	</div>

	{if $image->hits}
	        <div>This page has been <a href="/help/hit_counter">viewed</a> about <b>{$image->hits}</b> times</div>
	{/if}
</div>

<!-- ----------------------------------------------------- -->

{literal}
	<script type="application/ld+json">
	{
	      "@context": "https://schema.org",
	      "@type": "BreadcrumbList",
	      "itemListElement": [{
	        "@type": "ListItem",
	        "position": 1,
	        "name": "Photos",{/literal}
	        "item": "{$self_host}/" {literal}
	      },{
	        "@type": "ListItem",
	        "position": 2,{/literal}
	        "name": {"by `$image->realname`"|latin1_to_utf8|json_encode},
	        "item": "{$self_host}{$image->profile_link|escape:'javascript'}" {literal}
	      },{
	        "@type": "ListItem",
	        "position": 3,{/literal}
	        "name": {$image->title|latin1_to_utf8|json_encode} {literal}
	      }]
	}
	</script>
	<script type="application/ld+json">
	{
	      "@context": "https://schema.org/",
	      "@type": "ImageObject",{/literal}
	      "name": {$image->title|latin1_to_utf8|json_encode},
	      "contentUrl": {$imageurl|json_encode},
	      "license": "http://creativecommons.org/licenses/by-sa/2.0/",
	      "acquireLicensePage": "{$self_host}/reuse.php?id={$image->gridimage_id}",
	      "copyrightNotice": {$image->realname|latin1_to_utf8|json_encode}, {literal}
	      "creator": {{/literal}
	        "@type": "Person",
	        "name": {$image->realname|latin1_to_utf8|json_encode},
                "url": "{$self_host}{$image->profile_link|escape:'javascript'}" {literal}
	      },
	      "isFamilyFriendly": true,
	      "representativeOfPage": true
	}
	</script>
{/literal}

<!--INFOLINKS_ON-->
{include file="_std_end.tpl"}
