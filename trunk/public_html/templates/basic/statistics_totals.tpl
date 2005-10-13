{assign var="page_title" value="Geograph Database Statistics"}
{include file="_std_begin.tpl"}

<h2>Geograph Database Statistics</h2>

<p style="color:red">Note: that stats on this page are only approximate,<br/>  as they represent total entries in each database table,<br/> but some/many entries might not be actully usable on the site!</p>

<p>This website has:</p>

<ul>
<li><b>{$count.gridprefix}</b> Known 100x100km Grid Squares (<b>{$count.gridprefix__land}</b> on land)</li>
<li><b>{$count.gridsquare}</b> Known 1x1km Grid Squares (<b>{$count.gridsquare__land}</b> on land)</li>
<li><b>{$count.geobb_users}</b> Registered Users (<b>{$count.autologin__30dayusers}</b> active in last 30 days)</li>
</ul>

<p>Users of the site have contributed:</p>

<ul>
<li><b>{$count.gridimage}</b> Photographs (<b>{$count.gridimage_search}</b> available)<ul>
	<li>by <b>{$count.gridimage__users}</b> different users</li>
</ul></li>
<li><b>{$count.geobb_posts}</b> Forum Posts (in <b>{$count.geobb_topics}</b> topics, of which <b>{$count.gridsquare_topic}</b> are grid square discussions)<ul>
	<li>by <b>{$count.geobb_posts__users}</b> different users, with <b>{$count.geobb_topics__views}</b> total topic page views</li>
</ul></li>
<li><b>{$count.gridimage_ticket}</b> Change Requests (<b>{$count.gridimage_ticket_item}</b> individual changes)<ul>
	<li>by <b>{$count.gridimage_ticket__users}</b> different users</li>
</ul></li>
<li><b>{$count.wordnet}</b> different Title words and phrases</li>
</ul>

<p>Additionally the site knows about:</p>

<ul>
<li><b>{$count.queries}</b> searches preformed<ul>
	<li>by <b>{$count.queries__users}</b> different users</li>
</ul></li>
<li><b>{$count.sessions}</b> visitors in the last 24 minutes</li>
<li><b>{$count.apikeys}</b> sites using the geograph API</li>
<li><b>{$count.loc_counties}</b> counties</li>
<li><b>{$count.loc_postcodes}</b> sector level postcodes</li>
<li><b>{$count.loc_placenames}</b> gazetteer features (<b>{$count.loc_dsg}</b> types)<ul>
	<li>of which <b>{$count.loc_placenames__ppl}</b> are placenames</li>
</ul></li>
<li><b>{$count.loc_wikipedia}</b> wikipedia placenames for map plotting</li>
<li><b>{$count.loc_towns}</b> important towns for map plotting</li>
</ul>

<p>Files Genetated:</p>

<ul>
<li><b>{$count.mapcache}</b> rendered map tiles</li>
<li><b>{$files.rss}</b> syndicated feeds</li>
<li><b>{$files.memorymap}</b> memorymap feeds</li>
<li><b>{$files.tpcompiled}</b> templates (made of <b>{$files.tpraw}</b> components)</li>
<li><b>{$files.tpcache}</b> rendered pages</li>
</ul>

<br style="clear:both"/>
<p style="text-align:center">Last generated at {$generation_time|date_format:"%H:%M"}</p>


{include file="_std_end.tpl"}
