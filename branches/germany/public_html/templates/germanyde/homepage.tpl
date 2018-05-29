{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}


<h2>Willkommen bei Geograph Deutschland</h2>

<div style="position:relative;background-color:white;">

<div style="background-color:#eeeeee;padding:2px; text-align:justify">
<p>
Das Geograph-Projekt hat das Ziel, geographisch repr�sentative Photos f�r jeden Quadratkilometer Deutschlands zu sammeln.
Dabei werden f�r die Planquadrate  <a href="http://de.wikipedia.org/wiki/MGRS">MGRS-Koordinaten</a> f�r die UTM-Zonen 31, 32 und 33 verwendet.
</p>
</div>

<div style="width:35%;float:left;position:relative;margin-right:10px">
{if $overview2}

	<h3 style="margin-bottom:4px;margin-top:8px;text-align:center">Abdeckung</h3>
	
	<div class="map" style="margin-left:auto;margin-right:auto;border:2px solid black; height:{$overview2_height}px;width:{$overview2_width}px">

	<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview2_width}px;height:{$overview2_height}px;">

	{foreach from=$overview2 key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		{if $overview2_clip}
		<div style="float:left;position:relative;height:{$mapcell->image_h-$mapcell->cliptop-$mapcell->clipbottom}px;width:{$mapcell->image_w-$mapcell->clipleft-$mapcell->clipright}px;overflow:hidden">
		<div style="position:absolute;clip:rect({$mapcell->cliptop}px,{$mapcell->image_w-$mapcell->clipright}px,{$mapcell->image_h-$mapcell->clipbottom}px,{$mapcell->clipleft}px);top:-{$mapcell->cliptop}px;left:-{$mapcell->clipleft}px">
		{/if}
		<a href="/mapbrowse{if $overview2_clip}2{/if}.php?o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1&amp;m={$m}"><img 
		alt="Klickbare Karte" ismap="ismap" title="zum Vergr��ern anklicken" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{if $overview2_clip}</div></div>{/if}
		{/foreach}
		</div>
	{/foreach}

	{if $marker}
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Bild des Tages von hier"><img src="//{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
	{/if}

	</div>
	</div>
	<div style="font-size:0.9em;text-align:center;position:relative">
            {if $m == 0}Zum Vergr��ern anklicken.
            {elseif $m == 1}Anklicken f�r Details.
            {elseif $m == 2}Anklicken um hereinzuzoomen.
            {elseif $m == 3}
            {elseif $m == 4}Anklicken um gr��ere Karte zu sehen.
            {elseif $m == 5}
            {elseif $m == 6}
            {/if}
        </div>

{/if}


</div>

<div style="width:{$potd_width}px;float:left;padding-right:5px;position:relative;text-align:center;">

	<div style="padding:2px;margin-top:8px;position:relative; text-align:center">
	{if $pictureoftheday.search||$searchpopular}
	<div style="float:right"><small>[{if $pictureoftheday.search}<a href="/results/{$pictureoftheday.search}">mehr</a>{if $searchpopular}|{/if}{/if}{if $searchpopular}<a href="/results/{$searchpopular}">beliebt</a>{/if}]</small>
	</div>
	{/if}

	<h3 style="margin-bottom:2px;margin-top:2px;">Bild des Tages</h3>
	<a href="/photo/{$pictureoftheday.gridimage_id}" 
	title="zum Vergr��ern anklicken">{$pictureoftheday.image->getFixedThumbnail($potd_width,$potd_height,3)}</a><br/>


	<a href="/photo/{$pictureoftheday.gridimage_id}"><b>{$pictureoftheday.image->title|escape:'html'}</b></a>

	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="/img/80x15.png" /></a>
	&nbsp;&nbsp;
	von <a title="Profil" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname|escape:'html'}</a> f�r Planquadrat <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></div>

	</div>

</div>



<br style="clear:both"/>

<div style="margin-top:10px;padding:5px;position:relative;text-align:center">
	<h3 style="margin-top:0;margin-bottom:4px;text-align:center">Was ist Geograph?</h3>
	
	&middot; Es ist ein Spiel: Wie viele Quadrate kann ich beitragen? &middot;<br/>
	&middot; Es ist ein Geographie-Projekt f�r alle &middot;<br/>
	&middot; Es ist ein Fotografie-Projekt &middot;<br/>
	&middot; Es ist eine gute Gelegenheit, mal rauszugehen &middot;<br/>
	&middot; Es ist ein freies <a href="/faq.php#opensource">Opensource</a>-Project &middot;<br/>
	
</div>
<br style="clear:both"/>
<div style="font-size:0.8em; text-align:center; border: 1px solid silver; padding:5px"><b class="nowrap">{$stats.users|thousends} Benutzer</b> haben <b class="nowrap">{$stats.images|thousends} Bilder</b> eingereicht, die <span  class="nowrap"><b class="nowrap">{$stats.squares|thousends} Planquadrate</b>, d.h. <b class="nowrap">{$stats.percentage|floatformat:"%.2f"}%</b> aller Quadrate, abdecken</span>.<br/>

{if count($hectads)}
Zuletzt vervollst�ndigte 10km&thinsp;x&thinsp;10&thinsp;km-Quadrate:
{foreach from=$hectads key=id item=obj}
<a title="Mosaik f�r {$obj.hectad_ref} (Seit {$obj.completed})" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php" title="Vollst�ndige 10km&thinsp;x&thinsp;10&thinsp;km-Quadrate">weitere...</a><br/>
{/if}

<b class="nowrap">{$stats.fewphotos|thousends} fotografierte Quadrate</b> haben <b class="nowrap">weniger als 4 Fotos</b>, wir bitten um Einreichungen!

</div><br style="clear:both"/>


<div style="width:350px;float:left;position:relative">

<h3>Wichtige Links...</h3>
<ul>
	<li><a title="Landkarte" href="/mapbrowse.php">Die <b>Landkarte</b> betrachten</a></li>
	<li><a title="Bild einreichen" href="/submit.php">Eigene <b>Bilder hochladen</b></a></li>
	<li><a title="Bildersuche" href="/search.php"><b>Bilder suchen</b></a></li>
	<li><a title="Diskussionsforum" href="/discuss/">Im <b>Forum</b> diskutieren</a></li>
</ul>

<h3>Im Detail...</h3>
<ul>
	<li><a title="Statistik" href="/statistics.php"><b>Statistiken</b> zu den eingereichten Bildern</a></li>
	<li><a title="Zusammenstellungen von Bildern" href="/explore/">In den Bildern <b>st�bern</b></a></li>
	<!--li><a title="Submitted Content" href="/content/">view <b>content and articles</b></a> <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></li-->
	<li><a title="Liste aller Seiten" href="/help/sitemap">Sitemap: <b>Liste der Seiten</b></a></li>
	<!--li><a title="Features Searches" href="/explore/searches.php">browse <b>featured collections</b></a> <a title="RSS Feed Featured Searches" href="/explore/searches.rss.php" class="xml-rss">RSS</a></li-->
</ul>

</div>

<div style="width:300px;float:left;position:relative">

<p>Die <a title="register now" href="https://{$http_host}/register.php">Registrierung</a> ist kostenlos, wir freuen uns �ber jeden Gast und noch mehr �ber alle, die zum Gelingen des Projekts beitragen wollen! Die wichtigsten Informationen dazu k�nnen in der <a title="Fragen und Antworten" href="/faq.php">FAQ</a> gefunden werden.
</p>


<h3>Die Bilder woanders betrachten...</h3>

<a title="Aktuelle Bilder in Google Earth" href="/feed/recent.kml" class="xml-kml">KML</a> (f�r <a title="Google Earth Export" href="/kml.php"><b>Google Earth</b> und <b>Maps</b></a>)<br/>

<a title="RSS Feed aktueller Bilder" href="/feed/recent.rss" rel="RSS" class="xml-rss">RSS</a> (<a title="RSS Feeds" href="/faq.php#rss">mehr <b>RSS feeds</b></a>)<br/>

<a title="GPX-Datei aktueller Bilder" href="/feed/recent.gpx" rel="RSS" class="xml-gpx">GPX</a> (<a title="GPX File Export" href="/gpx.php">mehr GPX-Optionen</a>)<br/>

oder <a title="Memory Map Export" href="/memorymap.php">in <b>Memory Map</b></a><br/><br/>

und <a href="http://www.geograph.org.uk/article/Ways-to-view-Geograph-Images">andere Methoden, Bilder zu betrachten</a>
</div>

<br style="clear:both"/>
&nbsp;

</div>
<div style="background-color:#eeeeee;padding:2px; text-align:justify">
<p>
Das Projekt nutzt den Programmcode, der gro�z�gigerweise von den Betreibern des <a href="http://www.geograph.org.uk">britischen Geograph-Projekts</a>
zur Verf�gung gestellt wird. Mehr Informationen hierzu sind im <a href="/howto/">HOWTO</a> und im <a href="/code/">Code-Bereich</a> zu finden. Da sich
das Projekt noch in einem sehr fr�hen Stadium befindet, sind allerdings viele Unterseiten noch nicht �bersetzt und andere Bereiche noch gar nicht verf�gbar.
</p>
<p>
Trotz der noch unvollst�ndigen Umsetzung ist Teilnahme am Projekt sehr erw�nscht; R�ckmeldung ist �ber das <a href="https://{$http_host}/contact.php">Kontaktformular</a>
oder per <a href="mailto:geo@hlipp.de">Mail</a> m�glich.
</p>
</div>

{include file="_std_end.tpl"}
