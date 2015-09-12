{include file="_std_begin.tpl"}

{if $image}
<div style="float:right; position:relative; width:5em; height:4em;"></div>
<div style="float:right; position:relative; width:2.5em; height:1em;"></div>

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
 {place place=$place h3=true}
{/if}

{if $image->moderation_status eq 'rejected'}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">

<h3 style="color:black"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Rejected</h3>

<p>This photograph has been rejected by the site moderators, and is only viewable by you.</p>

<p>You can find any messages related to this image on the <a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">edit page</a>, where you can reply or raise new concerns in the "Please tell us what is wrong..." box, which will be communicated with site moderators. You may also like to read this general article on common <a href="http://www.geograph.org.uk/article/Reasons-for-rejection">Reasons for rejection</a>.

</div>
<br/>
{/if}
{dynamic}
{if $searchid}
	<div class="interestBox" style="text-align:center; font-size:0.9em;width:400px;margin-left:auto;margin-right:auto">
		{if $searchidx}
			<a href="/results/browse/{$searchid}/{$searchidx-1}">&lt; prev image</a>
                {else}
			<span style="color:silver" title="first image">&lt; prev image</span>
		{/if} |
		<a href="/search.php?i={$searchid}&amp;page={$searchpg}"><b>back to search results</b></a> |
		<a href="/results/browse/{$searchid}/{$searchidx+1}" >next image &gt;</a>
	</div>
{elseif $search_keywords && $search_count}
	<div class="interestBox" style="text-align:center; font-size:0.9em">
		We have at least <b>{$search_count} images</b> that match your query [{$search_keywords|escape:'html'}] in the area! <a href="/search.php?searchtext={$search_keywords|escape:'url'}&amp;gridref={$image->grid_reference}&amp;do=1">View them now</a>
	</div>
{/if}
{/dynamic}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
        {if $image->original_width || $user->user_id eq $image->user_id || $notes || $altimg neq '' || $user->registered}
	<div class="caption640" style="text-align:right;">
	{if $notes || $altimg neq ''}
		Move the mouse pointer over the image to display <a href="/geonotes.php?id={$image->gridimage_id}&amp;size=original">image annotations</a>
	{elseif $user->registered}
		<a href="/geonotes.php?id={$image->gridimage_id}">Create image annotations</a>
	{/if}
	{if ($image->original_width || $user->user_id eq $image->user_id) && ($notes || $altimg neq '' || $user->registered)}|{/if}
	{if $image->original_width}
		<a href="/more.php?id={$image->gridimage_id}">More sizes</a>
	{elseif $user->user_id eq $image->user_id}
		<a href="/resubmit.php?id={$image->gridimage_id}">Upload a larger version</a>
	{/if}
	</div>
	{/if}
  <div class="img-shadow" id="mainphoto"><!-- comment out whitespace
  {if $notes}
    --><div class="notecontainer" id="notecontainer">
    {$image->getFull(true,"class=\"geonotes\" usemap=\"#notesmap\" id=\"gridimage\" style=\"position:relative;top:0px;left:0px;z-index:3;\"")}<!--
    {if $altimg neq ''}--><img src="{$altimg}" height="{$std_height}px" width="{$std_width}px" id="gridimagealt" alt="" style="position:absolute;top:0px;left:0px;z-index:2;" /><!--{/if}-->
    <map name="notesmap" id="notesmap">
    {foreach item=note from=$notes}
    <area alt="" title="{$note->comment|escape:'html'}" id="notearea{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}" />
    {/foreach}
    </map>
    {foreach item=note from=$notes}
    <div id="notebox{$note->note_id}" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1}px;height:{$note->y2-$note->y1+1}px;z-index:{$note->z+50}" class="notebox"><span></span></div>
    {/foreach}
    {foreach item=note from=$notes}
    <div id="notetext{$note->note_id}" class="geonote"><p>{$note->comment|escape:'html'|nl2br|geographlinks:false:true:true}</p></div>
    {/foreach}
    <script type="text/javascript" src="{"/js/geonotes.js"|revision}"></script>
    </div><!--
  {elseif $altimg neq ''}
    --><div class="notecontainer" id="notecontainer">
    {$image->getFull(true,"class=\"geonotes\" id=\"gridimage\" style=\"position:relative;top:0px;left:0px;z-index:3;\"")}
    <img src="{$altimg}" height="{$std_height}px" width="{$std_width}px" id="gridimagealt" alt="" style="position:absolute;top:0px;left:0px;z-index:2;" />
    <script type="text/javascript" src="{"/js/geonotes.js"|revision}"></script>
    </div><!--
  {else}
  -->{$image->getFull(true,"id=\"gridimage\"")}<!--
  {/if}
  --></div>

  {if $image->comment1 neq '' && $image->comment2 neq '' && $image->comment1 neq $image->comment2}
     {if $image->title1 eq ''}
       <div class="caption640"><b>{$image->title2|escape:'html'}</b></div>
       <div class="caption640">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       <div class="caption640">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {else}
       <div class="caption640"><b>{$image->title1|escape:'html'}</b></div>
       <div class="caption640">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       {if $image->title2 neq ''}
       <div class="caption640"><b>{$image->title2|escape:'html'}</b></div>
       {/if}
       <div class="caption640">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {else}
     {if $image->title1 neq ''}
       {if $image->title2 neq '' && $image->title2 neq $image->title1 }
       <div class="caption640"><b>{$image->title1|escape:'html'} ({$image->title2|escape:'html'})</b></div>
       {else}
       <div class="caption640"><b>{$image->title1|escape:'html'}</b></div>
       {/if}
     {else}
       <div class="caption640"><b>{$image->title2|escape:'html'}</b></div>
     {/if}
     {if $image->comment1 neq ''}
       <div class="caption640">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {elseif $image->comment2 neq ''}
       <div class="caption640">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {/if}

</div>

<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->

{if $image_taken}
<div class="keywords" style="top:-3em;float:right;position:relative;font-size:0.8em;height:0em;z-index:-10" title="year photo was taken">year taken <div style="font-size:3em;line-height:0.5em">{$image->imagetaken|truncate:4:''}</div></div>
{/if}

<div class="buttonbar">

<table style="width:100%">
<tr>
	<td colspan="6" align="center" style="background-color:#c0c0c0;font-size:0.7em;"><b><a href="/reuse.php?id={$image->gridimage_id}">Find out how to reuse this Image</a></b> For example on your webpage, blog, a forum, or Wikipedia. </td>
</tr>
<tr>
{if $enable_forums}
<td style="width:50px"><a href="/discuss/index.php?gridref={$image->grid_reference}"><img src="http://{$static_host}/templates/basic/img/icon_discuss.gif" alt="Discuss" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
{if $discuss}
	There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a
	<a href="/discuss/index.php?gridref={$image->grid_reference}">discussion<br/>on {$image->grid_reference}</a> (preview on the left)
{else}
	<a href="/discuss/index.php?gridref={$image->grid_reference}#newtopic">Start a discussion on {$image->grid_reference}</a>
{/if}
</td>
{/if}

<td style="width:50px"><a href="/editimage.php?id={$image->gridimage_id}"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	{if $user->user_id eq $image->user_id}
		<big><a href="/editimage.php?id={$image->gridimage_id}"><b>Change Image Details</b></a></big><br/>
		(or raise a query with a moderator)
	{else}
		<a href="/editimage.php?id={$image->gridimage_id}">Suggest an Update to this Image</a>
	{/if}
</td>
{if $user->user_id ne $image->user_id}
<td style="width:50px"><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}"><img  src="http://{$static_host}/templates/basic/img/icon_email.gif" alt="Email" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	<a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the contributor</a>
</td>
{/if}
</tr>
</table>

</div>




<div class="picinfo">

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative">
	{$rastermap->getImageTag($image->subject_gridref)}
	{if $rastermap->getFootNote()}
	<div class="interestBox" style="margin-top:3px;margin-left:2px;padding:1px;"><small>{$rastermap->getFootNote()}</small></div>
	{/if}
	{if count($image->grid_square->services) > 1}
	<form method="get" action="/photo/{$image->gridimage_id}">
	<p>Karte:
	<select name="sid">
	{html_options options=$image->grid_square->services selected=$sid}
	</select>
	<input type="submit" value="Los"/></p></form>
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
 <dd><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} images{/if} &nbsp; (<a title="More pictures near {$image->grid_reference}" href="/search.php?q={$image->grid_reference}" rel="nofollow">more nearby</a>) 
</dd>

{if $image->credit_realname}
	<dt>Photographer</dt>
	 <dd>{$image->realname|escape:'html'}</dd>

	<dt>Contributed by</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}">{$image->user_realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->user_realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
{else}
	<dt>Photographer</dt>
	 <dd><a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> &nbsp; (<a title="pictures near {$image->grid_reference} by {$image->realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">find more nearby</a>)</dd>
{/if}

<dt>Image classification</dt>
<dd>{if $image->ftf}
	Geograph (First for {$image->grid_reference})
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
{/if}</dd>


{if $image_taken}
<dt>Date Taken</dt>
 <dd>{$image_taken} &nbsp; (<a title="pictures near {$image->grid_reference} taken on {$image_taken}" href="/search.php?gridref={$image->grid_reference}&amp;orderby=submitted&amp;taken_start={$image->imagetaken}&amp;taken_end={$image->imagetaken}&amp;do=1" class="nowrap" rel="nofollow">more nearby</a>)</dd>
{/if}
<dt>Submitted</dt>
	<dd>{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>

<dt>Category</dt>

<dd>{if $image->imageclass}
	{$image->imageclass} &nbsp; (<a title="pictures near {$image->grid_reference} of {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:'url'}" rel="nofollow">more nearby</a>)
{else}
	<i>n/a</i>
{/if}</dd>

<dt>Subject Location</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}Germany, MGRS 32{elseif $image->grid_square->reference_index eq 4}Germany, MGRS 33{elseif $image->grid_square->reference_index eq 5}Germany, MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links">{$image->subject_gridref_spaced}</a> [{$image->subject_gridref_precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>

{if $image->photographer_gridref}
<dt>Photographer Location</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}Germany, MGRS 32{elseif $image->grid_square->reference_index eq 4}Germany, MGRS 33{elseif $image->grid_square->reference_index eq 5}Germany, MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->photographer_gridref}/links">{$image->photographer_gridref_spaced}</a></dd>
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

	<a title="Send an Electronic Card" href="/ecard.php?image={$image->gridimage_id}">Forward to a<br/>Friend &gt; &gt;</a><br/><br/>

	<a href="{$sitemap}">Text listing of Images in {$image->grid_reference}</a>


	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>
<div class="interestBox" style="text-align:center">View this location: 

{if $image->moderation_status eq "geograph" || $image->moderation_status eq "accepted"}

<small><a title="Open in Google Earth" href="http://{$http_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</small>, 
{external title="Open in Google Maps" href="http://maps.google.de/maps?q=http://`$http_host`/photo/`$image->gridimage_id`.kml" text="Google Maps"}, 

{/if}

{if $rastermap->reference_index == 1}<a href="/mapper/?t={$map_token}&amp;gridref_from={$image->grid_reference}">OS Map Checksheet</a>, {/if}

<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$image->grid_reference}">Geograph Map</a>
(<a href="/mapbrowse2.php?t={$map_token2}&amp;gridref_from={$image->grid_reference}">without zones</a>), 

{if $image_taken}
	{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;taken=%Y-%m-%d"}
{/if}

<span class="nowrap"><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links?{$imagetakenurl}&amp;title={$image->title|escape:'url'}&amp;id={$image->gridimage_id}"><b>More Links for this image</b></a></span>
</div>


<div style="text-align:center;margin-top:3px" class="interestBox" id="styleLinks"></div>
<script type="text/javascript">
/* <![CDATA[ */

{literal}
function addStyleLinks() {
{/literal}
	document.getElementById('styleLinks').innerHTML = 'Background for photo viewing: {if $maincontentclass eq "content_photowhite"}<b>white</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex">White</a>{/if}/{if $maincontentclass eq "content_photoblack"}<b>black</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex">Black</a>{/if}/{if $maincontentclass eq "content_photogray"}<b>grey</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex">Grey</a>{/if}';
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
  
function fixIE()
{
	var photo = document.getElementById('gridimage');
	//var notecontainer = document.getElementById('notecontainer');
	var container = document.getElementById('mainphoto');

	/*if (notecontainer) {
		notecontainer.style.width = photo.offsetWidth + 'px';
		notecontainer.style.height = photo.offsetHeight + 'px';
		notecontainer.style.display = 'block';
		notecontainer.style.overflow = 'visible';
	}*/

	//container.style.position = 'relative';
	container.style.overflowX = 'auto';
	container.style.overflowY = 'hidden';
	var scrolldiff = photo.offsetHeight - container.clientHeight;
	if (scrolldiff > 0) {
		container.style.height = photo.offsetHeight + scrolldiff;
	}
}
{/literal}
/* ]]> */
</script>

<!--[if lte IE 7]>
<script type="text/javascript">
/* <![CDATA[ */
AttachEvent(window,'load',fixIE,false);
/* ]]> */
</script>
<![endif]-->

{if $notes || $altimg}
<script type="text/javascript">
/* <![CDATA[ */
AttachEvent(window,"load",gn.init);
/* ]]> */
</script>
{/if}

<div style="width:100%;position:absolute;top:0px;left:0px;height:0px">
	<div class="interestBox" style="float: right; position:relative; padding:2px;">
		<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/gridref/{$neighbours.0}">NW</a></td>
		<td align="center"><a href="/gridref/{$neighbours.1}">N</a></td>
		<td><a href="/gridref/{$neighbours.2}">NE</a></td></tr>
		<tr><td><a href="/gridref/{$neighbours.3}">W</a></td>
		<td><b>Go</b></td>
		<td align="right"><a href="/gridref/{$neighbours.5}">E</a></td></tr>
		<tr><td><a href="/gridref/{$neighbours.6}">SW</a></td>
		<td align="center"><a href="/gridref/{$neighbours.7}">S</a></td>
		<td align="right"><a href="/gridref/{$neighbours.8}">SE</a></td></tr>
		</table>
	</div>
  {dynamic}
    {if $user->registered}

{* display current votes even if the browser gets this from the cache *}
<script type="text/javascript">
/* <![CDATA[ */
 AttachEvent(window,'load',function() {ldelim}imgvote({$imageid}, '', 0);{rdelim},false);
/* ]]> */
</script>

	<div class="interestBox thumbbox"><span id="hideside"></span>
		<img src="http://{$static_host}/img/thumbs.png" width="20" height="20" onmouseover="show_tree('side','block')"/>
	</div>
		
	<div class="thumbwincontainer"><div class="thumbwindow" id="showside" onmouseout="hide_tree('side')">
		<div class="interestBox" onmousemove="event.cancelBubble = true" onmouseout="event.cancelBubble = true">
			<h4 style="margin-top:0px">Image rating</h4>
			<p>
			Please read <a href="/discuss/index.php?&action=vthread&forum=2&topic=126">this forum thread</a> to learn what this is about.
			This is especially important as there were some misconceptions regarding the "Geographical information" rating which
			should take into account both the description and the subject of the image.
			</p>
			<div class="votebox">
				General impression: <span class="votebuttons">
				<span class="invisible"  >[</span><a id="vote{$imageid}like1" class="voteneg{if $vote.like==1}active{/if}" href="#" onclick="imgvote({$imageid}, 'like', 1); return false;" title="I don't like this image at all"><b>--</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}like2" class="voteneg{if $vote.like==2}active{/if}" href="#" onclick="imgvote({$imageid}, 'like', 2); return false;" title="I like this image below average"><b>-</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}like3" class="voteneu{if $vote.like==3}active{/if}" href="#" onclick="imgvote({$imageid}, 'like', 3); return false;" title="This is an average image"><b>o</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}like4" class="votepos{if $vote.like==4}active{/if}" href="#" onclick="imgvote({$imageid}, 'like', 4); return false;" title="I like this image above average"><b>+</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}like5" class="votepos{if $vote.like==5}active{/if}" href="#" onclick="imgvote({$imageid}, 'like', 5); return false;" title="I like this image a lot"><b>++</b></a>
				<span class="invisible">]</span></span>
			</div><div class="votebox">
				Location or scenic beauty: <span class="votebuttons">
				<span class="invisible"  >[</span><a id="vote{$imageid}site1" class="voteneg{if $vote.site==1}active{/if}" href="#" onclick="imgvote({$imageid}, 'site', 1); return false;" title="I don't like this place at all"><b>--</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}site2" class="voteneg{if $vote.site==2}active{/if}" href="#" onclick="imgvote({$imageid}, 'site', 2); return false;" title="I like this place below average"><b>-</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}site3" class="voteneu{if $vote.site==3}active{/if}" href="#" onclick="imgvote({$imageid}, 'site', 3); return false;" title="This is an average place"><b>o</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}site4" class="votepos{if $vote.site==4}active{/if}" href="#" onclick="imgvote({$imageid}, 'site', 4); return false;" title="This is a nice place"><b>+</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}site5" class="votepos{if $vote.site==5}active{/if}" href="#" onclick="imgvote({$imageid}, 'site', 5); return false;" title="This place is beautiful"><b>++</b></a>
				<span class="invisible">]</span></span>
			</div><div class="votebox">
				Image quality: <span class="votebuttons">
				<span class="invisible"  >[</span><a id="vote{$imageid}qual1" class="voteneg{if $vote.qual==1}active{/if}" href="#" onclick="imgvote({$imageid}, 'qual', 1); return false;" title="The quality of this image is much below average"><b>--</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}qual2" class="voteneg{if $vote.qual==2}active{/if}" href="#" onclick="imgvote({$imageid}, 'qual', 2); return false;" title="The quality of this image is below average"><b>-</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}qual3" class="voteneu{if $vote.qual==3}active{/if}" href="#" onclick="imgvote({$imageid}, 'qual', 3); return false;" title="This is an average image"><b>o</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}qual4" class="votepos{if $vote.qual==4}active{/if}" href="#" onclick="imgvote({$imageid}, 'qual', 4); return false;" title="This is an image of high quality"><b>+</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}qual5" class="votepos{if $vote.qual==5}active{/if}" href="#" onclick="imgvote({$imageid}, 'qual', 5); return false;" title="This is an image of very high quality"><b>++</b></a>
				<span class="invisible">]</span></span>
			</div><div class="votebox">
				Geographical information: <span class="votebuttons">
				<span class="invisible"  >[</span><a id="vote{$imageid}info1" class="voteneg{if $vote.info==1}active{/if}" href="#" onclick="imgvote({$imageid}, 'info', 1); return false;" title="I don't see much geographical information"><b>--</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}info2" class="voteneg{if $vote.info==2}active{/if}" href="#" onclick="imgvote({$imageid}, 'info', 2); return false;" title="This is not very interesting"><b>-</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}info3" class="voteneu{if $vote.info==3}active{/if}" href="#" onclick="imgvote({$imageid}, 'info', 3); return false;" title="This is an average image"><b>o</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}info4" class="votepos{if $vote.info==4}active{/if}" href="#" onclick="imgvote({$imageid}, 'info', 4); return false;" title="This is interesting"><b>+</b></a>
				<span class="invisible">] [</span><a id="vote{$imageid}info5" class="votepos{if $vote.info==5}active{/if}" href="#" onclick="imgvote({$imageid}, 'info', 5); return false;" title="This is very interesting"><b>++</b></a>
				<span class="invisible">]</span></span>
			</div>
			<p>
			<a href="/imgvote.php">Show my recent votes</a>
			</p>
		</div>
	</div></div>
    {/if}
  {/dynamic}
	<div style="float:right">
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}" title="Add this image to your site marked list">Mark</a>]&nbsp;
	</div>
</div>


{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
