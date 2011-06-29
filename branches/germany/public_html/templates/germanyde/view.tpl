{include file="_std_begin.tpl"}

{if $image}
<div style="float:right; position:relative; width:5em; height:4em;"></div>
<div style="float:right; position:relative; width:2.5em; height:1em;"></div>

<h2><a title="Planquadrat {$image->grid_reference}{if $square_count gt 1} :: {$square_count} Bilder{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
 {place place=$place h3=true}
{/if}

{if $image->moderation_status eq 'rejected'}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">

<h3 style="color:black"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Abgelehnt</h3>

<p>Dieses Bild wurde von den Moderatoren abgelehnt und ist für andere nicht sichtbar.</p>

<p>Mitteilungen zu diesem Bild können auf der <a title="Bildinformationen ändern" href="/editimage.php?id={$image->gridimage_id}">Editier-Seite</a> angesehen werden, wo auch
Fragen der Moderatoren beantwortet oder Rückfragen gestellt werden können. Allgemeine Informationen über Ablehnungsgründe sind im englischen Artikel <a href="http://www.geograph.org.uk/article/Reasons-for-rejection">Reasons for rejection</a> beschrieben.

</div>
<br/>
{/if}
{dynamic}
{if $search_keywords && $search_count}
	<div class="interestBox" style="text-align:center; font-size:0.9em">
		Es gibt mindestens <b>{$search_count} Bilder</b>, die die Anfrage [{$search_keywords|escape:'html'}] im Gebiet erfüllen! <a href="/search.php?searchtext={$search_keywords|escape:'url'}&amp;gridref={$image->grid_reference}&amp;do=1">Jetzt ansehen</a>
	</div>
{/if}
{/dynamic}

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	{if $image->original_width}
		<div class="caption640" style="text-align:right;"><a href="/more.php?id={$image->gridimage_id}">Andere Größen</a></div>
	{elseif $user->user_id eq $image->user_id}
		<div class="caption640" style="text-align:right;"><a href="/resubmit.php?id={$image->gridimage_id}">Größere Version hochladen</a></div>
	{/if}
  <div class="img-shadow" id="mainphoto">{$image->getFull()}</div>
  
  

  {if $image->comment1 neq '' && $image->comment2 neq '' && $image->comment1 neq $image->comment2}
     {if $image->title1 eq ''}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {else}
       <div class="caption"><b>{$image->title1|escape:'html'}</b></div>
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       {if $image->title2 neq ''}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
       {/if}
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {else}
     {if $image->title1 neq ''}
       {if $image->title2 neq '' && $image->title2 neq $image->title1 }
       <div class="caption"><b>{$image->title1|escape:'html'} ({$image->title2|escape:'html'})</b></div>
       {else}
       <div class="caption"><b>{$image->title1|escape:'html'}</b></div>
       {/if}
     {else}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
     {/if}
     {if $image->comment1 neq ''}
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {elseif $image->comment2 neq ''}
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {/if}

</div>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="Profil betrachten" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> und  
lizenziert unter <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">dieser Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!--

{include file="_rdf.tpl"}

-->

{if $image_taken}
<div class="keywords" style="top:-3em;float:right;position:relative;font-size:0.8em;height:0em;z-index:-10" title="Jahr, in dem das Foto aufgenommen wurde">Aufnahmejahr <div style="font-size:3em;line-height:0.5em">{$image->imagetaken|truncate:4:''}</div></div>
{/if}

<div class="buttonbar">

<table style="width:100%">
<tr>
	<td colspan="6" align="center" style="background-color:#c0c0c0;font-size:0.7em;"><b><a href="/reuse.php?id={$image->gridimage_id}">Wie kann dieses Bild verwertet werden</a></b>, beispielsweise für Webseiten, Blogs, Foren, Wikipedia?</td>
</tr>
<tr>
{if $enable_forums}
<td style="width:50px"><a href="/discuss/index.php?gridref={$image->grid_reference}"><img src="http://{$static_host}/templates/basic/img/icon_discuss.gif" alt="Forum" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
{if $discuss}
	Es gibt {if $totalcomments == 1}einen Beitrag{else}{$totalcomments} Beiträge{/if}
	<a href="/discuss/index.php?gridref={$image->grid_reference}">im Forum<br/>zu {$image->grid_reference}</a> (Vorschau links)
{else}
	<a href="/discuss/index.php?gridref={$image->grid_reference}#newtopic">Diskussion zu {$image->grid_reference} beginnen</a>
{/if}
</td>
{/if}

<td style="width:50px"><a href="/editimage.php?id={$image->gridimage_id}"><img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Ändern" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	{if $user->user_id eq $image->user_id}
		<big><a href="/editimage.php?id={$image->gridimage_id}"><b>Bildinformationen ändern</b></a></big><br/>
		(oder Frage an einen Moderator richten)
	{else}
		<a href="/editimage.php?id={$image->gridimage_id}">Änderung für dieses Bild vorschlagen</a>
	{/if}
</td>
{if $user->user_id ne $image->user_id}
<td style="width:50px"><a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}"><img  src="http://{$static_host}/templates/basic/img/icon_email.gif" alt="Email" width="50" height="44"/></a></td>
<td style="font-size:0.7em;vertical-align:middle">
	<a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Einreicher kontaktieren</a>
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



<dt>Planquadrat</dt>
 <dd><a title="Planquadrat {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $square_count gt 1}, {$square_count} Bilder{/if} &nbsp; (<a title="Mehr Bilder in der Nähe von {$image->grid_reference}" href="/search.php?q={$image->grid_reference}" rel="nofollow">Weitere in der Nähe</a>) 
</dd>

{if $image->credit_realname}
	<dt>Fotograf</dt>
	 <dd>{$image->realname|escape:'html'}</dd>

	<dt>Eingereicht von</dt>
	 <dd><a title="View profile" href="/profile/{$image->user_id}">{$image->user_realname|escape:'html'}</a> &nbsp; (<a title="Bilder um {$image->grid_reference} von {$image->user_realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">Weitere in der Nähe</a>)</dd>
{else}
	<dt>Fotograf</dt>
	 <dd><a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> &nbsp; (<a title="Bilder um {$image->grid_reference} von {$image->realname|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;u={$image->user_id}" class="nowrap" rel="nofollow">Weitere in der Nähe</a>)</dd>
{/if}

<dt>Klassifikation</dt>
<dd>{if $image->ftf}
	Geobild (Erstes für {$image->grid_reference})
{else}
	{if $image->moderation_status eq "rejected"}
	Abgelehnt
	{/if}
	{if $image->moderation_status eq "pending"}
	Noch nicht moderiert
	{/if}
	{if $image->moderation_status eq "geograph"}
	Geobild
	{/if}
	{if $image->moderation_status eq "accepted"}
	Extrabild
	{/if}
{/if}</dd>


{if $image_taken}
<dt>Aufgenommen</dt>
 <dd>{$image_taken} &nbsp; (<a title="Bilder um {$image->grid_reference} vom {$image_taken}" href="/search.php?gridref={$image->grid_reference}&amp;orderby=submitted&amp;taken_start={$image->imagetaken}&amp;taken_end={$image->imagetaken}&amp;do=1" class="nowrap" rel="nofollow">Weitere in der Nähe</a>)</dd>
{/if}
<dt>Eingereicht</dt>
	<dd>{$image->submitted|date_format:"%A, %e %B, %Y"}</dd>

<dt>Kategorie</dt>

<dd>{if $image->imageclass}
	{$image->imageclass} &nbsp; (<a title="Bilder um {$image->grid_reference} von {$image->imageclass|escape:'html'}" href="/search.php?gridref={$image->grid_reference}&amp;imageclass={$image->imageclass|escape:'url'}" rel="nofollow">Weitere in der Nähe</a>)
{else}
	<i>n/a</i>
{/if}</dd>

<dt>Koordinaten des Motivs</dt>
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}MGRS 32{elseif $image->grid_square->reference_index eq 4}MGRS 33{elseif $image->grid_square->reference_index eq 5}MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links">{$image->subject_gridref}</a> [{$image->subject_gridref_precision}m Genauigkeit]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>

{if $image->photographer_gridref}
<dt>Koordinaten des Fotografen</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $image->grid_square->reference_index eq 1}OSGB36{elseif $image->grid_square->reference_index eq 2}Irish{elseif $image->grid_square->reference_index eq 3}MGRS 32{elseif $image->grid_square->reference_index eq 4}MGRS 33{elseif $image->grid_square->reference_index eq 5}MGRS 31{/if}: <img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->photographer_gridref}/links">{$image->photographer_gridref}</a></dd>
{/if}

{if $view_direction && $image->view_direction != -1}
<dt>Blickrichtung</dt>

<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{*{if     $view_direction eq "Southeast"}
Südost
{elseif $view_direction eq "South-southeast"}
Südsüdost
{elseif $view_direction eq "South"}
Süd
{elseif $view_direction eq "South-southwest"}
Südsüdwest
{elseif $view_direction eq "Southwest"}
Südwest
{elseif $view_direction eq "West-southwest"}
Westsüdwest
{elseif $view_direction eq "West"}
West
{elseif $view_direction eq "West-northwest"}
Westnordwest
{elseif $view_direction eq "Northwest"}
Nordwest
{elseif $view_direction eq "North-northwest"}
Nordnordwest
{elseif $view_direction eq "North"}
Nord
{elseif $view_direction eq "North-northeast"}
Nordnordost
{elseif $view_direction eq "Northeast"}
Nordost
{elseif $view_direction eq "East-northeast"}
Ostnordost
{elseif $view_direction eq "East"}
Ost
{elseif $view_direction eq "East-southeast"}
Ostsüdost
{else}
{$view_direction}
{/if}*}
{$view_direction}
(etwa {$image->view_direction} Grad)</dd>
{/if}

</dl>

</div>

{if $overview}
  <div style="float:left; text-align:center; width:{$overview_width}px; position:relative">
	{include file="_overview.tpl"}
	<div style="width:inherit;margin-left:20px;"><br/>

	<a title="Elektronische Postkarte schicken" href="/ecard.php?image={$image->gridimage_id}">Einem Freund<br/>schicken &gt; &gt;</a><br/><br/>

	<a href="{$sitemap}">Auflistung der Bilder in {$image->grid_reference}</a>


	</div>
  </div>
{/if}

</div>
<br style="clear:both"/>
<div class="interestBox" style="text-align:center">Diesen Ort betrachten: 

{if $image->moderation_status eq "geograph" || $image->moderation_status eq "accepted"}

<small><a title="In Google Earth öffnen" href="http://{$http_host}/photo/{$image->gridimage_id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</small>, 
{external title="In Google Maps öffnen" href="http://maps.google.de/maps?q=http://`$http_host`/photo/`$image->gridimage_id`.kml" text="Google Maps"}, 

{/if}

{if $rastermap->reference_index == 1}<a href="/mapper/?t={$map_token}&amp;gridref_from={$image->grid_reference}">OS Map Checksheet</a>, {/if}

<a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$image->grid_reference}">Geograph Map</a>, 

{if $image_taken}
	{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;taken=%Y-%m-%d"}
{/if}

<span class="nowrap"><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" alt="geotagged!" style="vertical-align:middle;" /> <a href="/gridref/{$image->subject_gridref}/links?{$imagetakenurl}&amp;title={$image->title|escape:'url'}&amp;id={$image->gridimage_id}"><b>Mehr Links zum Bild</b></a></span>
</div>


<div style="text-align:center;margin-top:3px" class="interestBox" id="styleLinks"></div>
<script type="text/javascript">
/* <![CDATA[ */
{literal}
function addStyleLinks() {
{/literal}
	document.getElementById('styleLinks').innerHTML = 'Hintergrundfarbe: {if $maincontentclass eq "content_photowhite"}<b>weiß</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex">weiß</a>{/if}/{if $maincontentclass eq "content_photoblack"}<b>schwarz</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex">schwarz</a>{/if}/{if $maincontentclass eq "content_photogray"}<b>grau</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex">grau</a>{/if}';
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
  
{/literal}
/* ]]> */
</script>


<div style="width:100%;position:absolute;top:0px;left:0px;height:0px">
	<div class="interestBox" style="float: right; position:relative; padding:2px;">
		<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=-1">NW</a></td>
		<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=0">N</a></td>
		<td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=1&amp;dx=1">NO</a></td></tr>
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=-1">W</a></td>
		<td><b>Nach</b></td>
		<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=0&amp;dx=1">O</a></td></tr>
		<tr><td><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=-1">SW</a></td>
		<td align="center"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=0">S</a></td>
		<td align="right"><a href="/browse.php?x={$x}&amp;y={$y}&amp;dy=-1&amp;dx=1">SO</a></td></tr>
		</table>
	</div>
	<div style="float:right">
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}" title="Bild in Merkliste aufnehmen">Markieren</a>]&nbsp;
	</div>
</div>


{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{else}
<h2>Bild nicht verfügbar</h2>
<p>Das gewünschte Bild ist nicht vorhanden. Das kann an einem Softwarefehler liegen oder daran, dass
das Bild nach dem Einreichen abgelehnt oder zurückgezogen wurde - Fragen dazu können über das <a title="Contact Us" href="/contact.php">Kontaktformular</a>
gestellt werden.</p>
{/if}

{include file="_std_end.tpl"}
