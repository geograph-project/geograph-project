{assign var="page_title" value="Hochaufgel�stes Bild hochladen"}
{include file="_std_begin.tpl"}

{dynamic}

{if $image}
	<div style="position:relative; float:right; width:220px; background-color:#eeeeee; padding: 10px; text-align:center">
		<b>Gew�hltes Bild</b>
		<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
			 <div style="font-size:0.7em">
				  <a title="zum Vergr��ern anklicken" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
				  von <a title="Profil" href="{$image->profile_link}">{$image->realname}</a>
				  f�r Planquadrat <a title="Seite f�r {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
			</div>
		</div>
	</div>
{/if}

<h2>Hochaufgel�stes Bild f�r <a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> hochladen</h2>


{if $error}
<h2><span class="formerror">Bitte die unten aufgef�hrten Fehler korrigeren...<br/>{$error}</span></h2>
{/if}

{if $step eq -1}
<h2>Upload verworfen</h2>
<p>Der Vorgang wurde abgebrochen. F�r Nachfragen
sind wir �ber das <a title="Kontakt" href="/contact.php">Kontaktformular</a> erreichbar.</p>

{elseif $step eq 4}
	<h3>Danke</h3>
	
	<p>Sobald das Bild von den Moderatoren freigeschaltet ist, wird es �ber "Andere Gr��en" auf der Fotoseite verf�gbar sein.</p>
	
	<p>Zur <a href="/photo/{$image->gridimage_id}">Fotoseite</a> zur�ckkehren.</p>

{elseif $step eq 2}



<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="upload_id" value="{$upload_id}"/>




<h3>Schritt 2: Bildgr��e einstellen</h3>

		{if $original_width || $altimg}
			{if !$altimg}{assign var="hide640" value=1}{/if}
			{include file="_submit_sizes.tpl"}

	{if $canclearexif}
		<hr/>
		<input type="checkbox" name="clearexif" id="clearexif" {if $wantclearexif}checked{/if} value="1"/> <label for="clearexif">Alle EXIF-Daten (z.B. Aufnahmezeitpunkt und Kameratyp) aus dem Bild entfernen.</label><!--br/-->
	{/if}

	<input type="hidden" name="altimg" value="{if $altimg}1{else}0{/if}" />

<script type="text/javascript">{literal}

function hideStep3() {
	document.getElementById("step3").style.display = 'none';
}
{/literal}
{if !$altimg && (!$user->upload_size || $user->upload_size == 640)}
 AttachEvent(window,'load',hideStep3,false);
{/if}
</script>

<div id="step3">
<h3>Schritt 3: Bildrechte best�tigen</h3>

	<p>
	Weil wir ein offenes Projekt sind, wollen wir sicherstellen, dass die Inhalte unter
	einer m�glichst offenen Lizenz stehen. Daher m�chten wir, dass alle Bilder unter einer {external title="Mehr �ber Creative Commons" href="http://creativecommons.org" text="Creative-Commons-Lizenz" target="_blank"}
	ver�ffentlicht werden, einschlie�lich der dazugeh�rigen Metadaten.</p>
	
	<p>Mit einer Creative-Commons-Lizenz beh�lt der Fotograf die Rechte an seinem Werk, erlaubt aber auch,
	dass die Fotos kopiert, bearbeitet und weiterverbreitet werden, solange der Fotograf genannt und die Lizenz beibehalten wird.</p>

	<p>Desweiteren m�ssen wir sicherstellen, dass der Einreicher das Bild ver�ffentlichen darf. Dies betrifft insbesondere
	Fotos, auf denen Personen abgebildet sind (Pers�nlichkeitsrecht, Datenschutzrecht, ...), sowie Fotos, die urheberrechtlich gesch�tzte
	Werke (z.B. Architektur) zeigen und nicht von �ffentlich zug�nglichen Orten aufgenommen sind.</p>
	
	<p>Daher bitten wir um Erlaubnis,</p>
	
	<ul>
	<li>das Werk zu ver�ndern und abgeleitete Werke zu erstellen und</li>
	<li>das Werk und abgeleitete Werke zu verbreiten.</li>
	</ul>

	<p>Au�erdem bitten wir um Best�tigung, dass &ndash; soweit erforderlich &ndash;</p>
	<ul>
	<li>die Einwilligung abgebildeter Personen vorliegt</li>
	<li>die Erlaubnis zur Ver�ffentlichung urheberrechtlich gesch�tzter Werke vorliegt</li>
	</ul>
	
	<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
		
	<p>Sollten diese Bedingungen nicht akzeptabel sein,
	kann der Upload durch einen Klick auf "ICH BIN NICHT EINVERSTANDEN"
	r�ckg�ngig gemacht werden.<br />
	<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
	
	<p>Sind die Bedingungen dagegen akzeptabel, wird der Upload nach einem Klick auf "ICH BIN EINVERSTANDEN" fortgesetzt.<br />
	
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);"/> 
	
	</p>
</div>	
		{else}
			<h3>Fehler: Datei nicht gro� genug, bitte Anklicken:
			<input style="background-color:pink; width:200px" type="submit" name="abandon" value="Upload verwerfen"/>
		{/if}


	</form>
	

{else if $step eq 1}


<ul>
	<li>Auf dieser Seite kann ein h�chaufgel�stes Bild zu obigem Beitrag hochgeladen werden.</li>
	<li>Es sollte eine gr��ere Version <b>genau</b> des obigen Bildes hochgeladen werden. Nur kleine Ver�nderungen wie Verbesserungen des Kontrasts sind erlaubt.</li>
	<li>Es wird nur eine zus�tzliche gr��ere Version bereitgestellt, das Bild auf der <a href="/photo/{$image->gridimage_id}">Foto-Seite</a> bleibt unver�ndert.</li>
</ul>

{if $exif}
	<p>EXIF-Daten, die bei der Suche nach dem Original helfen k�nnten:</p>
	<ul>
		{if $exif.filename}
			<li>Dateiname: <b>{$exif.filename|escape:'html'}</b></li>
		{/if}
		{if $exif.datetime}
			<li>Datum: <b>{$exif.datetime|escape:'html'}</b></li>
		{/if}
		{if $exif.model}
			<li>Kamera-Modell: <b>{$exif.model|escape:'html'}</b></li>
		{/if}
		{if $exif.width}
			<li>Breite: <b>{$exif.width|thousends} pixels</b></li>
		{/if}
		{if $exif.height}
			<li>H�he: <b>{$exif.height|thousends} pixels</b></li>
		{/if}
		{if $exif.filesize}
			<li>Dateigr��e: <b>{$exif.filesize|thousends} bytes</b></li>
		{/if}
	</ul>
	
{/if}

<br style="clear:both"/>
<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

<h3>Schritt 1: Datei ausw�hlen</h3>

<input type="hidden" name="MAX_FILE_SIZE" value="15728640" />
<label for="jpeg"><b>JPEG-Datei</b></label>
<input id="jpeg" name="jpeg" type="file" /><br/>
<input type="checkbox" name="altimg" id="altimg" value="1"/> <label for="altimg">Dieses Bild ist ein Alternativbild, das Beschriftungen o.�. enth�lt</label><br />

<p>(Die Aufl�sung ist nicht begrenzt, aber die Datei muss kleiner als 15 Megabytes sein.)</p>


<input type="submit" name="next" value="weiter &gt;" onclick="autoDisable(this);"/>

</form>

{/if}
{/dynamic}

{include file="_std_end.tpl"}
