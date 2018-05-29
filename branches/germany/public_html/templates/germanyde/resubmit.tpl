{assign var="page_title" value="Hochaufgelöstes Bild hochladen"}
{include file="_std_begin.tpl"}

{dynamic}

{if $image}
	<div style="position:relative; float:right; width:220px; background-color:#eeeeee; padding: 10px; text-align:center">
		<b>Gewähltes Bild</b>
		<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
			 <div style="font-size:0.7em">
				  <a title="zum Vergrößern anklicken" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
				  von <a title="Profil" href="{$image->profile_link}">{$image->realname}</a>
				  für Planquadrat <a title="Seite für {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
			</div>
		</div>
	</div>
{/if}

<h2>Hochaufgelöstes Bild für <a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> hochladen</h2>


{if $error}
<h2><span class="formerror">Bitte die unten aufgeführten Fehler korrigeren...<br/>{$error}</span></h2>
{/if}

{if $step eq -1}
<h2>Upload verworfen</h2>
<p>Der Vorgang wurde abgebrochen. Für Nachfragen
sind wir über das <a title="Kontakt" href="/contact.php">Kontaktformular</a> erreichbar.</p>

{elseif $step eq 4}
	<h3>Danke</h3>
	
	<p>Sobald das Bild von den Moderatoren freigeschaltet ist, wird es über "Andere Größen" auf der Fotoseite verfügbar sein.</p>
	
	<p>Zur <a href="/photo/{$image->gridimage_id}">Fotoseite</a> zurückkehren.</p>

{elseif $step eq 2}



<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="upload_id" value="{$upload_id}"/>




<h3>Schritt 2: Bildgröße einstellen</h3>

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
<h3>Schritt 3: Bildrechte bestätigen</h3>

	<p>
	Weil wir ein offenes Projekt sind, wollen wir sicherstellen, dass die Inhalte unter
	einer möglichst offenen Lizenz stehen. Daher möchten wir, dass alle Bilder unter einer {external title="Mehr über Creative Commons" href="http://creativecommons.org" text="Creative-Commons-Lizenz" target="_blank"}
	veröffentlicht werden, einschließlich der dazugehörigen Metadaten.</p>
	
	<p>Mit einer Creative-Commons-Lizenz behält der Fotograf die Rechte an seinem Werk, erlaubt aber auch,
	dass die Fotos kopiert, bearbeitet und weiterverbreitet werden, solange der Fotograf genannt und die Lizenz beibehalten wird.</p>

	<p>Desweiteren müssen wir sicherstellen, dass der Einreicher das Bild veröffentlichen darf. Dies betrifft insbesondere
	Fotos, auf denen Personen abgebildet sind (Persönlichkeitsrecht, Datenschutzrecht, ...), sowie Fotos, die urheberrechtlich geschützte
	Werke (z.B. Architektur) zeigen und nicht von öffentlich zugänglichen Orten aufgenommen sind.</p>
	
	<p>Daher bitten wir um Erlaubnis,</p>
	
	<ul>
	<li>das Werk zu verändern und abgeleitete Werke zu erstellen und</li>
	<li>das Werk und abgeleitete Werke zu verbreiten.</li>
	</ul>

	<p>Außerdem bitten wir um Bestätigung, dass &ndash; soweit erforderlich &ndash;</p>
	<ul>
	<li>die Einwilligung abgebildeter Personen vorliegt</li>
	<li>die Erlaubnis zur Veröffentlichung urheberrechtlich geschützter Werke vorliegt</li>
	</ul>
	
	<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
		
	<p>Sollten diese Bedingungen nicht akzeptabel sein,
	kann der Upload durch einen Klick auf "ICH BIN NICHT EINVERSTANDEN"
	rückgängig gemacht werden.<br />
	<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
	
	<p>Sind die Bedingungen dagegen akzeptabel, wird der Upload nach einem Klick auf "ICH BIN EINVERSTANDEN" fortgesetzt.<br />
	
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);"/> 
	
	</p>
</div>	
		{else}
			<h3>Fehler: Datei nicht groß genug, bitte Anklicken:
			<input style="background-color:pink; width:200px" type="submit" name="abandon" value="Upload verwerfen"/>
		{/if}


	</form>
	

{else if $step eq 1}


<ul>
	<li>Auf dieser Seite kann ein höchaufgelöstes Bild zu obigem Beitrag hochgeladen werden.</li>
	<li>Es sollte eine größere Version <b>genau</b> des obigen Bildes hochgeladen werden. Nur kleine Veränderungen wie Verbesserungen des Kontrasts sind erlaubt.</li>
	<li>Es wird nur eine zusätzliche größere Version bereitgestellt, das Bild auf der <a href="/photo/{$image->gridimage_id}">Foto-Seite</a> bleibt unverändert.</li>
</ul>

{if $exif}
	<p>EXIF-Daten, die bei der Suche nach dem Original helfen könnten:</p>
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
			<li>Höhe: <b>{$exif.height|thousends} pixels</b></li>
		{/if}
		{if $exif.filesize}
			<li>Dateigröße: <b>{$exif.filesize|thousends} bytes</b></li>
		{/if}
	</ul>
	
{/if}

<br style="clear:both"/>
<form enctype="multipart/form-data" action="{$script_name}?id={$image->gridimage_id}" method="post" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

<h3>Schritt 1: Datei auswählen</h3>

<input type="hidden" name="MAX_FILE_SIZE" value="15728640" />
<label for="jpeg"><b>JPEG-Datei</b></label>
<input id="jpeg" name="jpeg" type="file" /><br/>
<input type="checkbox" name="altimg" id="altimg" value="1"/> <label for="altimg">Dieses Bild ist ein Alternativbild, das Beschriftungen o.ä. enthält</label><br />

<p>(Die Auflösung ist nicht begrenzt, aber die Datei muss kleiner als 15 Megabytes sein.)</p>


<input type="submit" name="next" value="weiter &gt;" onclick="autoDisable(this);"/>

</form>

{/if}
{/dynamic}

{include file="_std_end.tpl"}
