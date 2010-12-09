{assign var="page_title" value="Geograph Database Statistics"}
{include file="_std_begin.tpl"}

<h2>Geograph Database Statistics</h2>

<p style="color:red">Note: stats on this page are only approximate,<br/> as they represent total entries in each database table,<br/> but some/many entries might not be actually usable on the site!</p>

<p>This website has:</p>

<ul>
<li><b>{$count.gridsquare|thousends}</b> known 1x1km grid squares (<b>{$count.gridsquare__land|thousends}</b> on land)</li>
<li><b>{$count.geobb_users|thousends}</b> registered users (<b>{$count.autologin__30dayusers|thousends}</b> active in last 30 days)</li>
</ul>

<p>Users of the site have contributed:</p>

<ul>
<li><b>{$count.gridimage_search|thousends}</b> photographs (<b>{$count.gridimage|thousends}</b> including ones not available)<ul>
	<li>by <b>{$count.gridimage__users|thousends}</b> different users</li>
	<li>making <b>{$count.hectad_complete|thousends}</b> completed hectads</li>
</ul></li>
<li><b>{$count.geobb_posts|thousends}</b> forum posts (in <b>{$count.geobb_topics|thousends}</b> topics, of which <b>{$count.gridsquare_topic|thousends}</b> are grid square discussions)<ul>
	<li>by <b>{$count.geobb_posts__users|thousends}</b> different users</li>
	<li>with <b>{$count.geobb_topics__views|thousends}</b> total topic page views</li>
	<li>featuring <b>{$count.gridimage_post|thousends}</b> thumbnails</li>
</ul></li>
<li><b>{$count.gridimage_ticket|thousends}</b> change requests (<b>{$count.gridimage_ticket_item|thousends}</b> individual changes)<ul>
	<li>by <b>{$count.gridimage_ticket__users|thousends}</b> different users (<b>{$count.gridimage_ticket__users_others|thousends}</b> excluding suggestions on own images)</li>
</ul></li>
<li><b>{$count.article|thousends}</b> articles</li>
</ul>

<p>Additionally the site knows about:</p>

<ul>
<li><b>{$count.gridprefix}</b> known 100x100km grid squares (<b>{$count.gridprefix__land}</b> on land)</li>
<li><b>{$count.queries|thousends}</b> searches preformed<ul>
	<li>by <b>{$count.queries__users|thousends}</b> different users</li>
</ul></li>
<li><b>{$count.sessions|thousends}</b> visitors in the last 24 minutes</li>
<li><b>{$count.apikeys}</b> sites using the Geograph API</li>
<li><b>{$count.loc_counties_pre74}</b> historic counties</li>
<li><b>{$count.loc_counties}</b> ceremonial counties</li>
<li><b>{$count.os_gaz_county|thousends}</b> modern administrative areas</li>
<li><b>{$count.loc_postcodes|thousends}</b> sector level postcodes</li>
<li><b>{$count.loc_placenames|thousends}</b> gazetteer features (<b>{$count.loc_dsg}</b> types)<ul>
	<li>of which <b>{$count.loc_placenames__ppl|thousends}</b> are placenames</li>
</ul></li>
<li><b>{$count.loc_ppl|thousends}</b> v2 gazetteer placenames (unused!)</li>
<li><b>{$count.loc_wikipedia|thousends}</b> Wikipedia placenames for map plotting</li>
<li><b>{$count.loc_towns|thousends}</b> important towns for map plotting</li>
<li><b>{$count.os_gaz|thousends}</b> GB gazetteer features (<b>{$count.os_gaz_code}</b> types)</li>
</ul>

<p>Files generated: (each accessed many times)</p>

<ul>
<li><b>{$count.mapcache|thousends}</b> rendered map tiles</li>
<li><b>{$count.kmlcache|thousends}</b> rendered superlayer tiles</li>
<li><b>{$files.rss|thousends}</b> different syndicated feeds</li>
<li><b>{$files.memorymap|thousends}</b> different Memory-Map feeds</li>
<li><b>{$files.tpcompiled|thousends}</b> page templates (made of <b>{$files.tpraw}</b> components)</li>
<li><b>{$count.kmlcache|thousends}</b> static sitemap files</li>
</ul>

<br style="clear:both"/>

{include file="_std_end.tpl"}
