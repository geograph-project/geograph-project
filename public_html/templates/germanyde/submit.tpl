{assign var="page_title" value="Bild Einreichen"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.tab {
	cursor:pointer;
	cursor:hand;
}

.navButtons A {
	border: 1px solid lightgrey;
	padding: 2px;
}

</style>{/literal}
{dynamic}

    <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if $step eq 1}	

	<h2>Einreichen: Schritt 1 von 4: Planquadrat wählen</h2>

{if $user->stats.images eq 0} 
	<div style="background-color:pink; color:black; border:2px solid red; padding:10px;"><b>Zum ersten mal hier?</b> &ndash; Wenn ja, könnte ein Blick in usere  <a href="/faq.php">FAQ</a> hilfreich sein..</div>

{/if}

{if $user->stats.images < 20} 
<div style="width:180px;margin-left:10px;margin-bottom:100px;float:right;font-size:0.8em;padding:10px;background:#dddddd;position:relative; border:1px solid gray; z-index:100">
<h3 style="margin-bottom:0;margin-top:0">Hilfe benötigt?</h3>

<p>Wird der exakte Standort eingegeben, z.B. <b>TPT 278695</b>, wird das passende Planquadrat
(<b>TPT 2769</b>) verwendet, die genaueren Koordinaten aber für den nächsten Schritt
beibehalten.</p>

<p>Nach Betätigen des "Weiter"-Buttons wird geprüft, ob bereits Fotos für dieses Quadrat eingereicht wurden.</p>

<p>Für Neulinge kann sich ein Blick in die <a href="/article/Anleitung">Anleitung</a> lohnen.</p>

</div>
{/if} 

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}
	
	<p>Zum Wählen einer anderen Methode einen der Reiter anklicken:</p>
	
<div style="position:relative;">
	<div class="tabHolder">
		<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,4)">Koordinaten eingeben</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,4)">Quadrat wählen</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,4)">Koordinaten aus Bild</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,4); if (!document.getElementById('innerFrame4').src) document.getElementById('innerFrame4').src = '/submitmap.php?inner'">Karte/Ortsname</a>
	</div>

	<div style="position:relative;{if $tab != 1}display:none{/if}" class="interestBox" id="div1">
		<p>Für welches Planquadrat soll das Bild eingereicht werden?</p>

		<p><b>Hinweis:</b> Hier sollte der Standort des <i>Hauptmotivs</i> angegeben werden, die Position des Fotografen kann im nächsten Schritt eingegeben werden.</p>

		<p><label for="grid_reference">Planquadrat 
		(<u title="z.B. TPT2769 oder TPT 27 69">4</u>,
		<u title="z.B. TPT277695 oder TPT 277 695">6</u>,
		<u title="z.B. TPT27796951 oder TPT 2779 6951">8</u> oder 
		<u title="z.B. TPT2779269513 oder TPT 27792 69513">10</u> Ziffern) des Motivs</label><br /><br />
		{if $grid_reference}<small><small>(<a href="javascript:void(document.getElementById('grid_reference').value = '');">löschen</a>)<br/></small></small>{/if}
		<input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14"/><small class="navButtons"><small><a href="javascript:doMove('grid_reference',-1,0);">W</a></small><sup><a href="javascript:doMove('grid_reference',0,1);">N</a></sup><sub><a href="javascript:doMove('grid_reference',0,-1);">S</a></sub><small><a href="javascript:doMove('grid_reference',1,0);">O</a></small></small>
		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="setpos" value="Weiter &gt;"/> {if $picnik_api_key}or <input type="submit" name="picnik" value="Upload via Picnik &gt;"/>{/if}
		</p>
		
		{if $picnik_api_key}<p>Clicking the <i>Upload via Picnik</i> button above allows submission via an online image manipulation service that allows tweaking of the image prior to automatically transfering it to Geograph.</p>{/if}
	</div>		

	<div style="position:relative;{if $tab != 2}display:none{/if}" class="interestBox" id="div2">
		<p>Für welches Planquadrat soll das Bild eingereicht werden?</p>

		<p><b>Hinweis:</b> Hier sollte der Standort des <i>Hauptmotivs</i> angegeben werden, die Position des Fotografen kann im nächsten Schritt eingegeben werden.</p>

		<p><label for="gridsquare">1km-Planquadrat wählen...</label><br/><br/>
		<select id="gridsquare" name="gridsquare">
			{html_options options=$prefixes selected=$gridsquare}
		</select>&nbsp;&nbsp;
		<label for="eastings">O</label>
		<select id="eastings" name="eastings">
			{html_options options=$kmlist selected=$eastings}
		</select>
		<small><small><a href="javascript:doMove2(-1,0);">W</a></small><small><a href="javascript:doMove2(1,0);">O</a></small></small>&nbsp;&nbsp;
		<label for="northings">N</label>
		<select id="northings" name="northings">
			{html_options options=$kmlist selected=$northings}
		</select>
		<small><sup><a href="javascript:doMove2(0,1);">N</a></sup><sub><a href="javascript:doMove2(0,-1);">S</a></sub></small>
		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="setpos2" value="Weiter &gt;"/> {if $picnik_api_key}or <input type="submit" name="picnik" value="Upload via Picnik &gt;"/>{/if}
		</p>
	</div>

	<div style="position:relative;{if $tab != 3}display:none{/if}" class="interestBox" id="div3">
		<p><label for="jpeg_exif"><b>Bild, das Ortsinformationen enthält, hochladen</b></label> <br/>
		
		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
		
		<input type="submit" name="setpos" value="Weiter &gt;"/> <br/>
		
		<div>Momentan werden unterstützt:<ul>
		<li>GPS-EXIF-tags, die Breiten- und Längengrad enthalten (WGS84)</li>
		<li>Motivkoordinaten im Dateinamen (z.B. "<tt>photo-<b style="padding:1px">TPT278695</b>A.jpg</tt>")</li>
		<li>Motivkoordinaten im EXIF-Kommentar</li>
		</ul></div>
	</div>

	<div style="position:relative;{if $tab != 4}display:none{/if}" class="interestBox" id="div4">
		<iframe {if $tab == 4}src="/submitmap.php?inner"{/if} id="innerFrame4" width="613" height="660" frameborder="0"><a href="/submitmap.php">Hier klicken um Google Map zu öffnen</a></iframe>
	</div>
	
</div>
	<br/><br/><br/>
	<p>Bei Unsicherheit über die Koordinaten ist vielleicht die <a href="article/Anleitung">Anleitung</a> hilfreich.</p>

	<script type="text/javascript" src="{"/mapping1.js"|revision}"></script>
	<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
{else}
	<input type="hidden" name="gridsquare" value="{$gridsquare|escape:'html'}">
	<input type="hidden" name="eastings" value="{$eastings|escape:'html'}">
	<input type="hidden" name="northings" value="{$northings|escape:'html'}">
{/if}
{if $step > 2}
	<input type="hidden" name="grid_reference" value="{$grid_reference|escape:'html'}">
{/if}

{if $step eq 2}

	<h2>Einreichen: Schritt 2 von 4: Bild für {$gridref} hochladen</h2>
	
	{if !$user->stats.images || $user->stats.images < 100 || !$last_imagetaken}
	<div style="color:black; background-color:yellow; font-size:0.7em; padding:3px; border: 1px solid orange">Bitte nach Möglichkeit keine Bilder mit Rahmen oder Text (z.B. Datum) einreichen; diese sollten zuvor entsprechend zugeschnitten werden.<br/><br/>
	Es sollten nur selbst aufgenommene Bilder eingereicht werden, oder Bilder für die man an Stelle des Fotografen als Lizenzgeber auftreten darf.</div><br/>
	{/if}
	
	{if $rastermap->enabled}
		<div style="float:left;width:50%;position:relative">
	{else}
		<div>
	{/if}
		{if $imagecount gt 0}
			<p style="color:#440000">Momentan
			{if $imagecount eq 1}ist ein Bild{else}sind {$imagecount} Bilder{/if} {if $totalimagecount && $totalimagecount > $imagecount} ({$totalimagecount} einschließlich verborgener Bilder){/if} 
			für {newwin title="Bilder für `$gridref` ansehen" href="/gridref/`$gridref`" text=`$gridref`} verfügbar. Wir freuen uns über weitere!</p>
		{else}
			<p style="color:#004400">Fantastisch! Wir haben noch kein Bild für {$gridref}! {if $totalimagecount && $totalimagecount ne $imagecount} (es sind aber {$totalimagecount} verborgen){/if}</p>
		{/if}

		{if $transfer_id}
		<img src="{$preview_url}" width="{$preview_width*0.2|string_format:"%d"}" height="{$preview_height*0.2|string_format:"%d"}" alt="Verkleinertes Bild"/>	
		<input name="transfer_id" type="hidden" value="{$transfer_id|escape:"html"}"/>
		{elseif $jpeg_url}
		<label for="jpeg_url"><b>JPEG Image URL</b></label>
		<input id="jpeg_url" name="jpeg_url" type="text" size="40" value="{$jpeg_url|escape:"html"}"/>
		{else}
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />
		<label for="jpeg"><b>JPEG Image File</b></label>
		<input id="jpeg" name="jpeg" type="file" />
		
		{if $picnik_api_key}<br/>or <input type="submit" name="picnik" value="Upload Image via Picnik.com"/><span style="color:red">New!</span>{/if}
		
		{/if}
		<div><small><small style="color:gray"><i>Bilder, die in einer Dimension mehr als 640 Pixel haben, werden verkleinert. Bei schon verkleinerten Bildern bitten wir darum, die Dateigröße nach Möglichkeit unter 100kb, auf jeden Fall aber unter 200kb zu halten.</i></small></small></div>
		{if $error}<br /><p style="color:#990000;font-weight:bold;">{$error}</p>{/if}
		<br />

		{if $reference_index == 2} 
		{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland" target="_blank"} includes 1:50,000 mapping for Northern Ireland.
		{/if}
		
		{if $last_grid_reference || $last_photographer_gridref}
			<div style="font-size:0.8em">
			<a href="javascript:{if $last_photographer_gridref}void(document.theForm.photographer_gridref.value = '{$last_photographer_gridref}');void(updateMapMarker(document.theForm.photographer_gridref,false));{/if}{if $last_grid_reference}void(document.theForm.grid_reference.value = '{$last_grid_reference}');void(updateMapMarker(document.theForm.grid_reference,false));{/if}">Vom vorigen Bild kopieren</a></div>
		{else}
		
		{/if}

		<h4><b>Koordinaten:</b> (erwünscht)</h4>
		<p><label for="grid_reference"><b style="color:#0018F8">Hauptmotiv</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{if $square->natspecified}{$grid_reference|escape:'html'}{/if}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Markiert Motiv" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Markiert Motiv" width="20" height="34" align="middle"/>{/if}
		<span style="font-size:0.8em"><br/><a href="javascript:void(mapMarkerToCenter(document.theForm.grid_reference));void(updateMapMarker(document.theForm.grid_reference,false));" style="font-size:0.8em">Marker zentrieren</a></span>
		</p>
	
		<p><label for="photographer_gridref"><b style="color:#002E73">Fotograf</b></label> <input id="photographer_gridref" type="text" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Markiert den Aufnahmestandort" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Markiert den Aufnahmestandort" width="20" height="34" align="middle"/>{/if}
		
		<span style="font-size:0.8em"><br/><a href="javascript:void(document.theForm.photographer_gridref.value = document.theForm.grid_reference.value);void(updateMapMarker(document.theForm.photographer_gridref,false));" style="font-size:0.8em">Motivposition</a></span>
		
		{if $rastermap->enabled}
			<br/><br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1"/> <label for="use6fig">Nur 6-ziffrige Koordinaten anzeigen ({newwin href="/help/map_precision" text="Erläuterung"})</label>
		{/if}
		</p>
	
		<p><label for="view_direction"><b>Blickrichtung</b></label> <small>(Fotograf schaut nach)</small><br/>
		<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
			{foreach from=$dirs key=key item=value}
				<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$view_direction} selected="selected"{/if}>{$value}</option>
			{/foreach}
		</select></p>
	</div>

	{if $rastermap->enabled}
		<div class="rastermap" style="width:45%;position:relative">
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		{if $rastermap->service == 'OLayers'}
			<a href="#" onclick="this.style.display='none';document.getElementById('map').style.width = '100%';document.getElementById('map').style.height = '400px';map.updateSize();return false">Größere Karte</a>
		{/if}{*FIXME move to FootNote?*}
		{if $rastermap->service == 'Google'}
			<a href="#" onclick="this.style.display='none';document.getElementById('map').style.width = '100%';document.getElementById('map').style.height = '400px';map.checkResize();return false">Größere Karte</a>
		{/if}{*FIXME move to FootNote?*}
		{if count($square->services) > 1}
		{*<form method="get" action="/gridref/{$gridref}">*}{*FIXME*}
		<p>Karte:
		<select name="sid">
		{html_options options=$square->services selected=$sid}
		</select>
		<input type="submit" name="newmap" value="Los"/></p>{*</form>*}
		{/if}
		
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					{/literal}{if $view_direction == -1}
						updateViewDirection();
					{/if}{literal}
				}
				AttachEvent(window,'load',updateMapMarkers,false);
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}

	<br/>
	<input type="submit" name="goback" value="&lt; Zurück"/> <input type="submit" name="upload" value="Weiter &gt;" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) {return autoDisable(this);} else {return false}{/literal}"/>
	<br style="clear:both"/>

	{if $totalimagecount gt 0}
	<br/>
	<div class="interestBox">
		<div><b>Die letzten {$shownimagecount} Bilder für dieses Quadrat...</b></div>

	{foreach from=$images item=image}

	  <div class="photo33" style="float:left;width:150px; height:170px; background-color:white">{newwin title="`$image->title` von `$image->realname` - Anklicken für Ansicht in voller Größe"|escape:'html' href="/photo/`$image->gridimage_id`" text=$image->getThumbnail(120,120,false,true)}
	  <div class="caption"><a title="Vollansicht" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a></div>
	  </div>

	{/foreach}
	<br style="clear:both"/>
	
	{if $imagecount gt 6 || $shownimagecount == 6}
		<div>{newwin href="/gridref/`$gridref`" text="`$imagecount` Bild(er) für `$gridref` ansehen"}<small> und versteckte Bilder</small></div>
	{/if}&nbsp;
	</div>
	{else}
		<br style="clear:both"/>
	{/if}
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{else}
	<input type="hidden" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}">
	<input type="hidden" name="view_direction" value="{$view_direction|escape:'html'}">
	<input type="hidden" name="use6fig" value="{$use6fig|escape:'html'}">

{/if}

{if $step eq 3}

<h2>Einreichen: Schritt 3 von 4: Foto überprüfen</h2>

{if $errormsg}
<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
{/if}

  {if $smallimage}
	<div style="background-color:red; color:white; border:1px solid pink; padding:10px;">Das Bild ist sehr klein. Der Einreichprozess kann zwar fortgesetzt werden,
	wir würden aber ein größeres Bild bevorzugen. Im Allgemeinen werden zu kleine Bilder von den Moderatoren zurückgewiesen, wenn sie nicht besonders "wertvoll" sind.</div>
  {/if}

<p>
Vorschaubild für Planquadrat {$gridref} in voller Größe:<br/><br/>

<img src="{$preview_url}" width="{$preview_width}" height="{$preview_height}"/>
<br/><br/>

<div style="position:relative; background-color:#dddddd; padding-left:10px;padding-top:1px;padding-bottom:1px;">
<h3><a name="geograph"></a>Ist das Bild ein &quot;Geobild&quot;?</h3>

<p>Der erste, der ein &quot;Geobild&quot; für {$gridref}
einreicht, bekommt einen Punkt in sein Profil eingetragen, d.h. Ruhm und Ehre
sind ihm sicher.</p>
<p>Was ist also nötig, damit ein Bild ein Geobild wird?</p>
<ul>
<li>Das Motiv muss in Planquadrat {$gridref} liegen, der Aufnahmestandort idealerweise auch.</li>
<li>Es muss ein geographisches Merkmal, das das Quadrat auszeichnet, aus der Nähe gezeigt werden.</li>
<li>Es sollte eine kurze Beschreibung angegeben werden, die das Bild und das Quadrat in Beziehung setzt.</li>
<li>Das Bild sollte ein natürliches Bild sein, wie es ein Mensch sehen würde. Digitale Veränderungen wie das Einfügen von Text, Zeitangaben, Fotomontagen u.ä. sollten vermieden werden. Natürlich spricht nichts dagegen, die Helligkeit/den Kontrast zu verbessern oder das Bild zuzuschneiden.</li>
</ul>

<p>Bilder guter Qualität, optisch ansprechende oder historisch relevante Bilder, Panoramaansichten, die viele Quadratkilometer
abdecken, ... können auch als Extrabilder für
{$gridref} akzeptiert werden, wenn sie genau lokalisiert sind, aber möglicherweise nicht als Geobilder.</p>

<ul>
<li>Wir freuen uns über viele Geobilder und Extrabilder in einem Quadrat, insbesondere, wenn sie verschiedene Motive, Blickwinkel, Jahreszeiten, anderes Wetter, ... zeigen.
Auch wenn es für sie keine Punkte gibt, sind sie dennoch ein wertvoller Beitrag zum Projekt.</li>
</ul>

</div>

<p>Es können weitere Informationen angegeben werden, die jederzeit geändert werden können.
</p>

<div class="interestBox" style="width:30em;z-index:0"><a href="/submit_popup.php?t={$reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Reopen Map in a popup</a><br/>
{newwin href="/gridref/`$gridref`" text="`$gridref`-Seite öffnen"}</div>

<h3>Titel und Beschreibung</h3>
<p>In Titel (zwingend) und Beschreibung (optional) können Informationen über das Motiv, den Ort und andere interessante geographische Informationen
angegeben werden. Die Angaben sind nur in einer Sprache erforderlich; englische Texte können von einem Moderator übersetzt werden.
<span id="styleguidelink">({newwin href="/help/style" text="Style Guide öffnen"})</span></p>

<p><label for="title"><b>Titel</b></label> {if $error.title}
	<br/><span class="formerror">{$error.title}</span>
	{/if}<br/>
<input size="50" maxlength="128" id="title" name="title" value="{$title|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);"/> <span class="formerror" style="display:none" id="titlestyle">Mögliches Stilproblem. Siehe Style Guide. <span id="titlestylet" style="font-size:0.9em"></span></span></p>
<p><label for="title2"><b>Englischer Titel</b> (optional)</label> {if $error.title2}
	<br/><span class="formerror">{$error.title2}</span>
	{/if}<br/>
<input size="50" maxlength="128" id="title2" name="title2" value="{$title2|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title2',true);" onkeyup="checkstyle(this,'title2',false);"/> <span class="formerror" style="display:none" id="title2style">Mögliches Stilproblem. Siehe Style Guide. <span id="title2stylet" style="font-size:0.9em"></span></span></p>
 {if $place.distance}
 <p style="font-size:0.7em">Folgende Ortsinformation wird erscheinen:<br/> <span style="color:silver;">{place place=$place}</span></p>
 {/if}

<p style="clear:both"><label for="comment"><b>Beschreibung/Kommentar</b></label> <span class="formerror" style="display:none" id="commentstyle">Mögliches Stilproblem. Siehe Style Guide. <span id="commentstylet"></span></span><br/>
<textarea id="comment" name="comment" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$comment|escape:'html'}</textarea></p>
<p style="clear:both"><label for="comment2"><b>Englische Beschreibung/Kommentar</b> (optional)</label> <span class="formerror" style="display:none" id="comment2style">Mögliches Stilproblem. Siehe Style Guide. <span id="comment2stylet"></span></span><br/>
<textarea id="comment2" name="comment2" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment2',true);" onkeyup="checkstyle(this,'comment2',false);">{$comment2|escape:'html'}</textarea></p>
<div style="font-size:0.7em">TIPP: Mit <span style="color:blue">[[TPT2769]]</span> oder <span style="color:blue">[[34]]</span> kann man
Planquadrate oder andere Bilder verlinken.<br/>Weblinks können direkt angegeben werden: <span style="color:blue">http://www.example.com</span></div>


<h3>Weitere Informationen</h3>

{literal}
<script type="text/javascript">
<!--
//rest loaded in geograph.js
function mouseOverImageClass() {
	if (!hasloaded) {
		setTimeout("prePopulateImageclass2()",100);
	}
	hasloaded = true;
}

function prePopulateImageclass2() {
	var sel=document.getElementById('imageclass');
	sel.disabled = false;
	var oldText = sel.options[0].text;
	sel.options[0].text = "bitte warten...";

	populateImageclass();

	hasloaded = true;
	sel.options[0].text = oldText;
	if (document.getElementById('imageclass_enable_button'))
		document.getElementById('imageclass_enable_button').disabled = true;
}
AttachEvent(window,'load',onChangeImageclass,false);
//-->
</script>
{/literal}

<p><label for="imageclass"><b>Geographische Kategorie</b></label> {if $error.imageclass}
	<br/><span class="formerror">{$error.imageclass}</span>
	{/if}<br />	
	<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onfocus="prePopulateImageclass()" onmouseover="mouseOverImageClass()" style="width:300px">
		<option value="">--bitte Kategorie wählen--</option>
		{if $imageclass}
			<option value="{$imageclass}" selected="selected">{$imageclass}</option>
		{/if}
		<option value="Other">Andere Kategorie...</option>
	</select>

	<span id="otherblock">
	<label for="imageclassother">Bitte Kategorie eingeben </label> 
	<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/>
	</span></p>
	
	
	
	
<p><label><b>Aufnahmedatum</b></label> {if $error.imagetaken}
	<br/><span class="formerror">{$error.imagetaken}</span>
	{/if}<br/>
	{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	{if $imagetakenmessage}
	    {$imagetakenmessage}
	{/if}
	
	[ 
	<input type="button" value="heute" onclick="setdate('imagetaken','{$today_imagetaken}',this.form);" class="accept"/>
	{if $last_imagetaken}
		<input type="button" value="zuletzt eingereicht" onclick="setdate('imagetaken','{$last_imagetaken}',this.form);" class="accept"/>
	{/if}
	{if $imagetaken != '--' && $imagetaken != '0000-00-00'}
		<input type="button" value="aktueller Wert" onclick="setdate('imagetaken','{$imagetaken}',this.form);" class="accept"/>
	{/if}
	]
	
	<br/><br/><span style="font-size:0.7em">(Bitte so detailliert wie möglich angeben. Wenn nur das Jahr oder der Monat bekannt ist, ist das auch in Ordnung.)</span></p>


<div style="position:relative; background-color:#dddddd; border: 1px solid red; padding-left:10px;padding-top:1px;padding-bottom:1px;">
<h3>Image Classification</h3>

<p><label for="user_status">Das Bild soll als "Extrabild" eingestuft werden:</label> <input type="checkbox" name="user_status" id="user_status" value="accepted" {if $user_status == "accepted"}checked="checked"{/if}/></p>

<p>Nur anklicken, wenn das Bild nicht als "Geobild" angesehen wird. Der Moderator benutzt dieses Kästchen nur als Vorschlag, so dass es im Zweifel einfach nicht angekreutzt werden sollte. Hinweis: Es kann mehrere Geobilder je Quadrat geben.</p>

<p>Es sei nochmals an <a href="#geograph">obige Kriterien</a> erinnert, was ein Geobild ausmacht. <span class="nowrap">Details dazu sind in der {newwin href="/faq.php#goodgeograph" text="FAQ"} oder im {newwin href="http://www.geograph.org.uk/article/Geograph-or-supplemental" text="Artikel über die Moderation (englisch)"} zu finden.</span></p>
</div>

<p>
<input type="hidden" name="upload_id" value="{$upload_id}"/>
<input type="hidden" name="savedata" value="1"/>
<input type="submit" name="goback" value="&lt; Zurück"/>
<input type="submit" name="next" value="Weiter &gt;"/></p>

<script type="text/javascript" src="/categories.js.php"></script>
<script type="text/javascript" src="/categories.js.php?full=1&amp;u={$user->user_id}"></script>

{else}
	<input type="hidden" name="title" value="{$title|escape:'html'}"/>
	<input type="hidden" name="title2" value="{$title2|escape:'html'}"/>
	<input type="hidden" name="comment" value="{$comment|escape:'html'}"/>
	<input type="hidden" name="comment2" value="{$comment2|escape:'html'}"/>
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
	<input type="hidden" name="user_status" value="{$user_status|escape:'html'}"/>
{/if}

{if $step eq 4}
	<input type="hidden" name="upload_id" value="{$upload_id}"/>
	<input type="hidden" name="title" value="{$title|escape:'html'}"/>
	<input type="hidden" name="title2" value="{$title2|escape:'html'}"/>
	<input type="hidden" name="comment" value="{$comment|escape:'html'}"/>
	<input type="hidden" name="comment2" value="{$comment2|escape:'html'}"/>
	<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
	<input type="hidden" name="imagetaken" value="{$imagetaken|escape:'html'}"/>
	<input type="hidden" name="user_status" value="{$user_status|escape:'html'}"/>
	
	{if $original_width && $largeimages}
	
		<h2>Einreichen: Schritt 4 von 4: Größe und Rechte bestätigen</h2>
		
		{include file="_submit_sizes.tpl"}
		
		<hr/>
	{else}
		<h2>Einreichen: Schritt 4 von 4: Rechte bestätigen</h2>
	{/if}

	{if $canclearexif}
		<input type="checkbox" name="clearexif" id="clearexif" {if $wantclearexif}checked{/if} value="1"/> <label for="clearexif">Alle EXIF-Daten (z.B. Aufnahmezeitpunkt und Kameratyp) aus dem Bild entfernen.</label><!--br/-->
		<hr/>
	{/if}

	{if $user->stats.images && $user->stats.images > 100 && $last_imagetaken}

	<div style="border:1px solid gray; padding:10px">Ich habe das schon gelesen, <input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="ICH BIN EINVERSTANDEN &gt;" onclick="autoDisable(this);autoDisable(this.form.finalise[1]);"/><br/> (erspart das Herunterscrollen)</div>
	{/if}
	
	<p>
	Weil wir ein offenes Projekt sind, wollen wir sicherstellen, dass die Inhalte unter
	einer möglichst offenen Lizenz stehen. Daher möchten wir, dass alle Bilder unter einer {external title="Mehr über Creative Commons" href="http://creativecommons.org" text="Creative-Commons-Lizenz" target="_blank"}
	veröffentlicht werden, einschließlich der dazugehörigen Metadaten.</p>
	
	<p>Mit einer Creative-Commons-Lizenz behält der Fotograf die Rechte an seinem Werk, erlaubt aber auch,
	dass die Fotos kopiert, bearbeitet und weiterverbreitet werden, solange der Fotograf genannt und die Lizenz beibehalten wird.</p>
	
	<p>Daher bitten wir um Erlaubnis,</p>
	
	<ul>
	<li>das Werk zu verändern und abgeleitete Werke zu erstellen und</li>
	<li>das Werk und abgeleitete Werke zu verbreiten.</li>
	</ul>
	
	<p>{external title="Lizenz ansehen" href="http://creativecommons.org/licenses/by-sa/2.0/deed.de" text="Hier ist die detailliertere Zusammenfassung der Creative-Commons-Lizenzbedingungen" target="_blank"}</p>
	
	{assign var="credit" value=$user->credit_realname}
	{assign var="credit_default" value=0}
	{include file="_submit_licence.tpl"}
	
	<p>Sollten diese Bedingungen nicht akzeptabel sein,
	kann die Einreichung durch einen Klick auf "ICH BIN NICHT EINVERSTANDEN"
	rückgängig gemacht werden.<br />
	<input style="background-color:pink; width:200px" type="submit" name="abandon" value="ICH BIN NICHT EINVERSTANDEN" onclick="return confirm('Sind Sie sicher? Der aktuelle Upload wird verworfen!');"/>
	
	</p>


	<p>Sind die Bedingungen dagegen akzeptabel, wird das Bild nach einem Klick auf "ICH BIN EINVERSTANDEN"
	für Planquadrat {$gridref} gespeichert.<br />
	<input type="submit" name="goback3" value="&lt; Zurück"/>
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="ICH BIN EINVERSTANDEN &gt;" onclick="autoDisable(this);{if $user->stats.images && $user->stats.images > 100 && $last_imagetaken}autoDisable(this.form.finalise[0]);{/if}"/>
	</p>
	


{/if}

{if $step eq 5}
<h2>Einreichung abgeschlossen!</h2>
<p>Vielen Dank &ndash; das Foto wurde dem Planquadrat 
<a title="Planquadrat {$gridref}" href="/gridref/{$gridref}">{$gridref}</a> zugeordnet.</p>
<p>Das Bild hat die Identifikationsnummer [<a href="/photo/{$gridimage_id}">{$gridimage_id}</a>]</p>


<p><a title="weiteres Foto einreichen" href="/submit.php">Hier klicken, um ein weiteres Foto einzureichen...</a></p>
{/if}

{if $step eq 6}
<h2>Einreichung abgebrochen</h2>
<p>Der Upload wurde abgebrochen. Bei Bedenken und Fragen
bezüglich der Lizenzbedingungen
bitten wir um <a title="Kontaktformular" href="/contact.php">Rückmeldung</a>.</p>
{/if}


{if $step eq 7}
<h2>Problem beim Einreichprozess</h2>
<p>{$errormsg}</p>
<p>Wir bitten um einen <a title="submit a photo" href="/submit.php">erneuten Versuch</a> bzw. um
<a title="Kontaktformular" href="/contact.php">Rückmeldung</a>, wenn die Probleme
fortbestehen.
</p>
{/if}


	</form> 

{if $step eq 3}

	<script type="text/javascript">{literal}
	function previewImage() {
		window.open('','_preview');//forces a new window rather than tab?
		var f1 = document.forms['theForm'];
		var f2 = document.forms['previewForm'];
		for (q=0;q<f2.elements.length;q++) {
			if (f2.elements[q].name && f1.elements[f2.elements[q].name]) {
				f2.elements[q].value = f1.elements[f2.elements[q].name].value;
			}
		}
		return true;
	}
	{/literal}</script>
	<form action="/preview.php" method="post" name="previewForm" target="_preview" style="background-color:lightgreen; padding:10px; text-align:center">
	<input type="hidden" name="grid_reference"/>
	<input type="hidden" name="photographer_gridref"/>
	<input type="hidden" name="view_direction"/>
	<input type="hidden" name="use6fig"/>
	<input type="hidden" name="title"/>
	<input type="hidden" name="title2"/>
	<textarea name="comment" style="display:none"/></textarea>
	<textarea name="comment2" style="display:none"/></textarea>
	<input type="hidden" name="imageclass"/>
	<input type="hidden" name="imageclassother"/>
	<input type="hidden" name="imagetakenDay"/>
	<input type="hidden" name="imagetakenMonth"/>
	<input type="hidden" name="imagetakenYear"/>
	<input type="hidden" name="upload_id"/>
	<input type="submit" value="Vorschau in neuem Fenster" onclick="previewImage()"/> 
	
	<input type="checkbox" name="spelling"/>Rechtschreibprüfung
	<sup style="color:red">Experimentell!</sup>
	</form>
{/if}

{if $preview_url}
{if !$enable_forums}
	<div style="position:fixed;right:10px;bottom:10px;display:none;background-color:silver;padding:2px;font-size:0.8em;width:148px" id="hidePreview">
{else}
	<div style="position:fixed;left:10px;bottom:10px;display:none;background-color:silver;padding:2px;font-size:0.8em;width:148px" id="hidePreview">
{/if}
	<div id="previewInner"></div></div>

<script type="text/javascript">
{literal}
function showPreview(url,width,height,filename) {
	height2=Math.round((148 * height)/width);
	document.getElementById('previewInner').innerHTML = '<img src="'+url+'" width="148" height="'+height2+'" id="imgPreview" onmouseover="this.height='+height+';this.width='+width+'" onmouseout="this.height='+height2+';this.width=148" /><br/>'+filename;
	document.getElementById('hidePreview').style.display='';
}
 AttachEvent(window,'load',function () {showPreview({/literal}'{$preview_url}',{$preview_width},{$preview_height},'{$filename|escape:'javascript'}'{literal}) },false);

{/literal}
</script>

{/if}


{/dynamic}
{include file="_std_end.tpl"}
