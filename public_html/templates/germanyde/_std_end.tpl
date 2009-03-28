</div>
</div>
<div id="nav_block" class="no_print">
 <div class="nav">
  <ul>
    <li style="font-size:1.42em"><a accesskey="1" title="Startseite" href="/">Startseite</a></li>
    <li>Ansicht<ul>
     <li><a title="Bildersuche" href="/search.php">Suche</a></li>
     <li><a title="Karte aller Bilder anzeigen" href="/mapbrowse.php">Karte</a> <small style="color:red">Aktualisiert!</small></li>
     <li><a title="Verschieden Zusammenstellungen von Bildern" href="/explore/">Stöbern</a></li>
     <li><a title="Nutzerbeiträge" href="/content/">Beiträge</a></li>
    </ul></li>
    <li>Interaktion<ul>
     <li><a title="Forum" href="/discuss/">Forum</a></li>
    </ul></li>
    <li>Teilnehmer<ul>
     <li><a title="Bild einreichen" href="/submit.php">Einreichen</a></li>
     <li><a title="Statistiken" href="/numbers.php">Statistik</a></li>
     <li><a title="Rangliste" href="/statistics/moversboard.php">Rangliste</a></li>
    </ul></li>
    <li>Informationen<ul>
     <li><a title="FAQ" href="/faq.php">FAQ</a></li>
     <li><a title="Projektbezogene Informationen" href="/content/?docs&amp;order=title">Dokumente</a></li>
     <li><a title="View All Pages" href="/help/sitemap">Sitemap</a></li>
     <li><a accesskey="9" title="Kontaktformular" href="/contact.php">Kontakt</a></li>
     <li><a title="Impressum" href="http://www.hlipp.de/contact.html">Impressum</a></li>
    </ul></li>
  {dynamic}
  {if $is_mod || $is_admin || $is_tickmod}
    <li>Admin<ul>
     <li><a title="Admin Tools" href="/admin/">Admin Index</a></li>
     {if $is_mod}
     	<li><a title="Moderation new photo submissions" href="/admin/moderation.php">Moderation</a></li>
     {/if}
     {if $is_tickmod}
     	<li><a title="Trouble Tickets" href="/admin/tickets.php">Tickets</a> (<a href="/admin/tickets.php?sidebar=1" target="_search" title="Open in Sidebar, IE and Firefox Only">S</a>)</li>
     {/if}
     <li><a title="Finish Moderation for this session" href="/admin/moderation.php?abandon=1">Finish</a></li>
    </ul></li>
  {/if}
  {/dynamic}
  </ul> 
{if $discuss}
{foreach from=$discuss item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>
{/foreach}
{/if}
{if $news}
{foreach from=$news item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>
{/foreach}
{if $rss_url}
<div style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">
<a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a>
</div>
{/if}
{/if}
  </div>
</div>
<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield"><label for="searchterm">Suche</label> 
    <input type="hidden" name="form" value="simple"/>
    {dynamic}<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10" title="Suche Bilder nach Text oder Koordinaten"/>{/dynamic}
    <input id="searchbutton" type="submit" name="go" value="los"/></div>
    </form>
    </div>
  </div>
  <div id="login">
  {dynamic}
  {if $user->registered}
  	  Angemeldet als {$user->realname|escape:'html'}
  	  <span class="sep">|</span>
  	  <a title="Profil" href="/profile/{$user->user_id}">Profil</a>
  	  <span class="sep">|</span>
  	  <a title="Ausloggen" href="/logout.php">Abmelden</a>
  {else}
	  Nicht angemeldet:
	  <a title="Schon registriert? Hier einloggen." href="/login.php">Anmelden</a>
		<span class="sep">|</span>
	  <a title="Registrieren um Fotos hochzuladen" href="/register.php">Registrieren</a>
  {/if}
  {/dynamic}
  </div>
</div>
{if $right_block}
	{include file=$right_block}
	<div class="content3" id="footer_block">
{else}
	<div class="content2" id="footer_block">
{/if}
  <div id="footer" class="no_print">
     <p style="color:#AAAABB;float:left">Letzte Änderung: {$smarty.now|date_format:"%H:%M"}</p>
   <p><a href="/help/sitemap" title="Listing of site pages">Sitemap</a>
       <span class="sep">|</span>
       <a href="/help/credits" title="Who built this and how?">Credits</a>
       <span class="sep">|</span>
       <a href="/help/terms" title="Terms and Conditions">Terms of use</a>
       <span class="sep">|</span>
       <a href="http://validator.w3.org/check/referer" title="check our xhtml standards compliance">XHTML</a>
       <span class="sep">|</span>
       <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://{$static_host}/templates/germanyde/css/basic.css" title="check our css standards compliance">CSS</a>
    </p>
  </div>
</div>
</body>
</html>
