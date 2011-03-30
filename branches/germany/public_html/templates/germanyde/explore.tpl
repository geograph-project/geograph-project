{assign var="page_title" value="In Geograph-Zusammenstellungen stöbern"}
{assign var="meta_description" value="Viele Möglichkeiten, Bilder zu suchen und zu betrachten..."}
{include file="_std_begin.tpl"}

	<div style="padding:5px;background:#dddddd;position:relative; float:right;"><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref1">Springe</label> zu <b>Koordinate</b>: <br/>
	<input id="gridref1" type="text" name="gridref" value="TPT2769" size="15" style="color:gray" onfocus="{literal}if (this.value=='TPT2769') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo1" type="submit" value="Los"/>
	</form></div>

<h2>Die Bilder durchforsten</h2>


<h3>Besondere Leistungen...</h3>
<ul class="explore">

	<li><a href="/statistics/fully_geographed.php">Liste <b>vollständiger Hectads</b></a> <small>(10km&times;10km-Quadrate)</small> - mit Bild-Mosaik</li>

</ul>
<h3>Zusammenstellungen...</h3>
<ul class="explore">

	<li><a href="/explore/searches.php"><b>Ausgewählte Suchanfragen</b></a></li>

	<li><a name="counties"></a>Fotos um <b>zentrale Punkte</b> betrachten: <ul>
		<li><a href="/explore/cities.php">Städte</a></li>
	</ul></li>

	<li><a href="/gallery/"><b>Galerien</b></a> - ausgewählte Bilder zu verschiedenen Themen</li>

	<!--li><a href="/explore/places/" title="Explore by Place">Explore the <b>placename gazetteer</b></a> <sup style="color:red">New!</sup></li-->

	{if $histsearch}<li>Nach <a href="/results/{$histsearch}"><b>historischen Bildern</b> suchen</a> (<a href="/statistics/leaderboard.php?type=images&amp;when=1989&amp;timerel=dbefore&amp;date=taken"><b>Rangliste</b></a> für historische Bilder)</li>{/if}
	{if $hasregions}
	<li><a href="/statistics/regions.php">Regionale Statistik</a>{if $regionlistlevel > -1} (<a href="/statistics/regions.php?level={$regionlistlevel}">lange Liste</a>){/if}</li>
	{/if}
</ul>
<h3>Aufgliederungen...</h3>
<ul class="explore">

	<li><a href="/explore/calendar.php">Geograph-<b>Kalender</b></a>: Bilder nach Aufnahmedatum betrachten</li>

	<li><a href="/statistics/breakdown.php?by=class">Nach <b>Kategorie</b> aufschlüsseln</a></li>

	<li><a href="/help/sitemap#users"><b>Teilnehmer</b>-Listen</a></li>

</ul>
<h3>Querbeet...</h3>
<ul class="explore">

	<li><form method="get" action="/browse.php" style="display:inline">
	<label for="gridref">Springe</label> zu <b>Koordinate</b>: 
	<input id="gridref" type="text" name="gridref" value="TPT2769" size="15" style="color:gray" onfocus="{literal}if (this.value=='TPT2769') { this.value='';this.style.color='';}{/literal}"/>
	<input id="searchgo" type="submit" value="Los"/>
	</form></li>

	<li><a href="/stuff/browse-random.php">Zu <b>zufälligem</b> Planquadrat springen</a></li>

	<li><a href="/mapbrowse.php"><b>Landkarte</b> betrachten</a></li>

	<li><a href="/gmmap.php"><b>Verschiebbare Karte</b> ohne Zonen betrachten</a></li>

	<li><a href="/hectadmap.php">Karte der <b>Hectad-Abdeckung</b></a> (10km&times;10km-Quadrate)</li>

	<li><form method="get" action="/search.php" style="display:inline">
	<label for="searchterm">Suchbegriff, Ort oder Einreicher <b>finden</b></label>: <br/>
	<input id="searchq" type="text" name="q" value="{$searchq|escape:'html'}" size="30"/>
	<input id="searchgo" type="submit" name="go" value="Suchen"/> [<a href="/help/search">Hilfe</a>, <a href="/search.php?form=advanced">erweiterte Suche</a>]
	</form></li>

{if $countylist}
	<li><form method="get" action="/search.php" style="display:inline">
	<label for="county_id">Search by centre of <b>Ceremonial County</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="county_id" id="county_id" size="1" class="searchinput" onchange="onlyone(this)" onblur="onlyone(this)"/> 
	  <option value=""> </option> 
		{html_options options=$countylist selected=$county_id}
	</select> <input id="searchgo" type="submit" name="go" value="Find"/>
	</form></li>
{/if}

{if $enable_forums}
	<li><form method="get" action="/search.php" style="display:inline">
	<label for="topic_id">Suchen nach <b>Diskussions-Thema</b></label>: 
	<input type="hidden" name="do" value="1"/>
	<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
	  <option value=""> </option> 
		{html_options options=$topiclist selected=$topic_id}
	</select> <input id="searchgo" type="submit" name="go" value="Suchen"/>
	</form></li>
{/if}

</ul>

<p style="background-color:#cccccc;padding:10px;">Bilder mit <a href="/help/sitemap#software">externer <b>Software</b></a> betrachten</p>

{include file="_std_end.tpl"}
