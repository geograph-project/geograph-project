{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin_DE.tpl"}


<h2>Willkommen bei geograph-ch!</h2>

<div style="position:relative;background-color:white;">

Das Ziel von geograph-ch ist es, für jeden Quadratkilometer in der Schweiz und Liechtenstein Bilder zu sammeln &ndash; und Du kannst mitmachen.

<div style="margin-top:10px;padding:10px;position:relative;text-align:center;border: 1px solid silver;">

	<div style="position:relative; text-align:center">

		<h3 style="margin-bottom:2px;margin-top:2px;">Bild des Tages {if $pictureoftheday.search} <small>[<a href="/results/{$pictureoftheday.search}">mehr...</a>]</small>{/if}</h3>
		<a href="/photo/{$pictureoftheday.gridimage_id}" title="Klicken für gr&ouml;sseres Bild">{$pictureoftheday.image->getFixedThumbnail(360,263)}</a><br/>
		<a href="/photo/{$pictureoftheday.gridimage_id}"><b>{$pictureoftheday.image->title|escape:'html'}</b></a>

		<div class="ccmessage">
			Bild von <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname|escape:'html'}</a> im Quadrat
			<a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a> (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons-Lizenz</a>)
		</div>
		
		<b class="nowrap">{$stats.users|thousends} Mitglieder</b> haben <b class="nowrap">{$stats.images|thousends} Bilder</b> <span  class="nowrap"> auf <b class="nowrap">{$stats.squares|thousends} Quadratkilometern</b> beigetragen: das sind <b class="nowrap">{$stats.percentage|number_format:2}%</b> der Fläche der Schweiz.

	</div>
</div>
	
<div style="margin-top:10px;padding:5px;position:relative;">
	<h1>Was ist geograph-ch?</h1>
	<ul>
		<li> Es ist ein Spiel: Welchen Anteil der Schweiz wirst Du fotografieren k&ouml;nnen?</li>
		<li> Es ist ein Geographie-Projekt f&uuml;r alle.</li>
		<li> Es ist wohl das gr&ouml;sste nationale Fotografie-Projekt.</li>
		<li> Es ist ein guter Anlass, um mehr nach draussen zu gehen!</li>
		<li> Es ist eine freie und offene Online-Community.</li>
	</ul>

<p><a title="Registriere Dich!" href="/register.php">Registrieren und mitmachen</a> ist gratis! 

Lese <a title="Häufige Fragen" href="/faq.php">die häufigsten Fragen</a> und steige ein. Wir w&uuml;nschen Dir viel Spass bei geograph-ch!</p>

</div>

<br style="clear:both"/>
&nbsp;

</div>
{include file="_std_end_DE.tpl"}
