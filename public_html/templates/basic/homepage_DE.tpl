{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin_DE.tpl"}


<h2>Willkommen bei Geograph-CH</h2>

<div style="position:relative;background-color:white;">

<div style="background-color:#eeeeee;padding:2px; text-align:center">
Das Ziel von Geograph-CH ist es, für jeden Quadratkilometer in der Schweiz und Liechtenstein Bilder zu sammeln – und Du kannst mitmachen.</div>

<div style="width:370px;float:left;padding-right:5px;position:relative;text-align:center;">

	<div style="padding:2px;margin-top:8px;position:relative; text-align:center">

	<h3 style="margin-bottom:2px;margin-top:2px;">Bild des Tages {if $pictureoftheday.search} <small>[<a href="/results/{$pictureoftheday.search}">mehr...</a>]</small>{/if}</h3>
	<a href="/photo/{$pictureoftheday.gridimage_id}" 
	title="Klicken für grösseres Bild">{$pictureoftheday.image->getFixedThumbnail(360,263)}</a><br/>


	<a href="/photo/{$pictureoftheday.gridimage_id}"><b>{$pictureoftheday.image->title|escape:'html'}</b></a>

	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons-Lizenz" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/80x15.png" /></a>
	&nbsp;&nbsp;
	by <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname|escape:'html'}</a> for <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></div>

	</div>

</div>



<br style="clear:both"/>

<div style="margin-top:10px;padding:5px;position:relative;text-align:center">
	<h3 style="margin-top:0;margin-bottom:4px;text-align:center">Was ist Geograph-CH?</h3>
	
	&middot; Es ist ein Spiel - welchen Anteil der Schweiz wirst Du fotografieren? &middot;<br/>
	&middot; Es ist ein Geographie-Projekt für alle &middot;<br/>
	&middot; Es ist ein nationales Fotografie-Projekt &middot;<br/>
	&middot; Es ist ein guter Anlass um mehr nach draussen zu gehen! &middot;<br/>
	&middot; Es ist eine <a href="/faq.php#opensource">freie und offene Online-Community</a> für alle &middot;<br/>
	
</div>
<br style="clear:both"/>
<div style="font-size:0.8em; text-align:center; border: 1px solid silver; padding:5px"><b class="nowrap">{$stats.users|thousends} users</b> haben <b class="nowrap">{$stats.images|thousends} Bilder</b> <span  class="nowrap"> auf <b class="nowrap">{$stats.squares|thousends} Quadratkilometern</b> beigetragen: das sind <b class="nowrap">{$stats.percentage|number_format:2}%</b> der Fläche der Schweiz</span>.<br/>

</div><br style="clear:both"/>

<div style="width:300px;float:left;position:relative">

<p><a title="Registriere Dich!" href="/register.php">Mitmachen</a> ist gratis! 

Lese <a title="Häufige Fragen" href="/faq.php">die häufigsten Fragen</a> und mach mit -
wir wünschen Dir viel Spass bei Geograph-CH!</p>

</div>

<br style="clear:both"/>
&nbsp;

</div>
{include file="_std_end.tpl"}
