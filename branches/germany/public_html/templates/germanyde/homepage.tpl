{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}


<h2>Willkommen bei Geograph Deutschland</h2>

<div style="position:relative;background-color:white;">

<div style="background-color:#eeeeee;padding:2px; text-align:justify">
<p>
Das Geograph-Projekt hat das Ziel, geographisch repr�sentative Photos f�r jeden Quadratkilometer Deutschlands zu sammeln.
Dabei werden f�r die Planquadrate  <a href="http://de.wikipedia.org/wiki/MGRS">MGRS-Koordinaten</a> f�r die UTM-Zonen 31, 32 und 33 verwendet.
</p>
<p>
Das Projekt nutzt den Programmcode, der gro�z�gigerweise von den Betreibern des <a href="http://www.geograph.org.uk">britischen Geograph-Projekts</a>
zur Verf�gung gestellt wird. Mehr Informationen hierzu sind im <a href="/howto/">HOWTO</a> und im <a href="/code/">Code-Bereich</a> zu finden. Da sich
das Projekt noch in einem sehr fr�hen Stadium befindet, sind allerdings viele Unterseiten noch nicht �bersetzt und andere Bereiche noch gar nicht verf�gbar.
</p>
<p>
Trotz der noch unvollst�ndigen Umsetzung ist Teilnahme am Projekt sehr erw�nscht; R�ckmeldung ist �ber das <a href="/contact.php">Kontaktformular</a>
oder per <a href="mailto:geo@hlipp.de">Mail</a> m�glich.
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
		<a href="/mapbrowse.php?o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1&amp;m={$m}"><img 
		alt="Klickbare Karte" ismap="ismap" title="zum Vergr��ern anklicken" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}

	{if $marker}
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Bild des Tages von hier"><img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
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

<div style="width:370px;float:left;padding-right:5px;position:relative;text-align:center;">

	<div style="padding:2px;margin-top:8px;position:relative; text-align:center">

	<h3 style="margin-bottom:2px;margin-top:2px;">Bild des Tages</h3>
	<a href="/photo/{$pictureoftheday.gridimage_id}" 
	title="zum Vergr��ern anklicken">{$pictureoftheday.image->getFixedThumbnail(360,263)}</a><br/>


	<a href="/photo/{$pictureoftheday.gridimage_id}"><b>{$pictureoftheday.image->title|escape:'html'}</b></a>

	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
	&nbsp;&nbsp;
	von <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname|escape:'html'}</a> f�r Planquadrat <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></div>

	</div>

</div>



<br style="clear:both"/>

<div style="margin-top:10px;padding:5px;position:relative;text-align:center">
	<h3 style="margin-top:0;margin-bottom:4px;text-align:center">What is Geographing?</h3>
	
	&middot; It's a game - how many grid squares will you contribute? &middot;<br/>
	&middot; It's a geography project for the people &middot;<br/>
	&middot; It's a national photography project &middot;<br/>
	&middot; It's a good excuse to get out more! &middot;<br/>
	&middot; It's a free and <a href="/faq.php#opensource">open online community</a> project for all &middot;<br/>
	
</div>
<br style="clear:both"/>
<div style="font-size:0.8em; text-align:center; border: 1px solid silver; padding:5px"><b class="nowrap">{$stats.users|thousends} Benutzer</b> haben <b class="nowrap">{$stats.images|thousends} Bilder</b> eingereicht, die <span  class="nowrap"><b class="nowrap">{$stats.squares|thousends} Planquadrate</b>, d.h. <b class="nowrap">{$stats.percentage}%</b> aller Quadrate, abdecken</span>.<br/>

{if count($hectads)}
Recently completed hectads: 
{foreach from=$hectads key=id item=obj}
<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">more...</a><br/>
{/if}

<b class="nowrap">{$stats.fewphotos|thousends} fotografierte Quadrate</b> haben <b class="nowrap">weniger als 4 Fotos</b>, wir bitten um Einreichungen!

</div><br style="clear:both"/>


<div style="width:350px;float:left;position:relative">

<h3>Quick Links...</h3>
<ul>
	<li><a title="Browse by Map" href="/mapbrowse.php">browse images on a <b>map</b></a></li>
	<li><a title="Submit a photograph" href="/submit.php"><b>upload</b> your own <b>pictures</b></a></li>
	<li><a title="Find photographs" href="/search.php"><b>search images</b> taken by other members</a></li>
	<li><a title="Discussion forums" href="/discuss/"><b>discuss the site</b> on our forums</a></li>
</ul>

<h3>Exploring in more depth...</h3>
<ul>
	<li><a title="Statistical Breakdown" href="/statistics.php"><b>view statistics</b> of images submitted</a></li>
	<li><a title="Explore Images" href="/explore/"><b>explore</b> geograph images</a></li>
	<li><a title="Submitted Content" href="/content/">view <b>content and articles</b></a> <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></li>
	<li><a title="List of all pages" href="/help/sitemap">view the <b>full list</b> of pages</a></li>
	<li><a title="Features Searches" href="/explore/searches.php">browse <b>featured collections</b></a> <a title="RSS Feed Featured Searches" href="/explore/searches.rss.php" class="xml-rss">RSS</a></li>
</ul>

</div>

<div style="width:300px;float:left;position:relative">

<p><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how 
many grid squares you submit! 

Read the <a title="Frequently Asked Questions" href="/faq.php">FAQ</a>, then get submitting -
we hope you'll enjoy being a part of this great project
</p>


<h3>View Images elsewhere...</h3>

<a title="Recent Images in Google Earth" href="/feed/recent.kml" class="xml-kml">KML</a> (for <a title="Google Earth Export" href="/kml.php"><b>Google Earth</b> and <b>Maps</b></a>)<br/>

<a title="RSS Feed of Recent Images" href="/feed/recent.rss" rel="RSS" class="xml-rss">RSS</a> (<a title="RSS Feeds" href="/faq.php#rss">more <b>RSS feeds</b> of images</a>)<br/>

<a title="GPX File of Recent Images" href="/feed/recent.gpx" rel="RSS" class="xml-gpx">GPX</a> (<a title="GPX File Export" href="/gpx.php">more GPX options</a>)<br/>

or <a title="Memory Map Export" href="/memorymap.php">in <b>Memory Map</b></a><br/><br/>

and <a href="/article/Ways-to-view-Geograph-Images">even more ways to view images</a>
</div>

<br style="clear:both"/>
&nbsp;

</div>
{include file="_std_end.tpl"}
