{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent li { padding-bottom:10px;}
</style>{/literal}

<h2>Administrative Tools</h2>
<ul>
<li><a href="/admin/moderation.php">Moderate</a> new photo submissions<br/> [{$images_pending} Pending]</li>


{if $is_tickmod} 
<li><a title="Trouble Tickets" href="/admin/tickets.php">Trouble Tickets</a> (<a title="Trouble Tickets" href="/admin/tickets.php?sidebar=1" target="_search">Open in Sidebar</a>) - 
   Deal with image problems<br/> [{$tickets_new} New, {$tickets_yours} Open by You]</li>
{/if}


{dynamic}
<li{if $gridsquares_sea_test > 0} style="color:lightgrey">
<b>Map-fixing in Progress</b> - please come back later.<br/>
{else}>{/if}
{/dynamic}
<a title="Map Fixer" href="/admin/mapfixer.php">Map Fixer</a> allows the land percentage
for each 1km grid squares to be updated, which allows "square is all at sea" to be 
corrected<br/> [GB:{$gridsquares_sea.1},I:{$gridsquares_sea.2} in Queue] - <a href="/mapfixer.php">add to queue</a><br/>
</li>

<li><a title="Recreate Maps" href="/recreatemaps.php">Recreate Maps</a> - 
   request update for map tiles</li>
      
</ul>

{if $is_admin} 
<h2>Load Average</h2>
<ul>
{dynamic}
<li>{$uptime}</li>
{/dynamic}
</ul>
<img src="http://www.geograph.org.uk/img/cpuday.png" width="480" height="161"/>
{/if}

<h2>Total Submissions</h2>
<img src="http://www.geograph.org.uk/img/submission_graph.png" width="480" height="161"/>

<h2>Daily Submission Rate</h2>
<img src="http://www.geograph.org.uk/img/rate.png" width="480" height="161"/>

{if $is_admin}
<br/><br/>
<h2>Admin Tools - use with care</h2>
<ul>

<li><a title="Moderators" href="/admin/moderator_admin.php">Moderator Admin</a> - 
   grant/revoke moderator rights to users</li>

<li><a title="API Keys" href="/admin/apikeys.php">API Keys</a> - 
   setup who has access to the API</li>

<li><a title="Category Consolidation" href="/admin/categories.php">Category Consolidation</a> - 
   Organise the user submitted categories</li>

</ul>
<h3>Statistics</h3>
<ul>  

<li><a title="Web Stats" href="/statistics/pulse.php">Geograph Pulse</a> - 
   upto the minute general site status</li>

<li><a title="Web Stats" href="http://www.geograph.org.uk/logs/">Web Stats</a> - 
   check the apache activity logs (outdated)</li>

<li><a title="Forum Stats" href="/discuss/?action=stats">Forum Stats</a> - 
   view forum activity stats</li>


   
<li><a title="Search Stats" href="/admin/viewsearches.php">Search Statistics</a> - See the recent Search Activity (very slow)</li>

<li><a title="Events" href="events.php">Event Diagnostics</a> - see what the event system is doing</li>

<li><a title="Server Stats" href="/admin/server.php">Server Stats</a> - 
   check server status (very slow)</li>


</ul>
<h3>Database Update/Repair</h3>
<ul>

<li><a title="Recreate Maps" href="/admin/recreatemaps.php">Recreate Maps</a> - 
   force recreation of the most urgent maps</li>

<li><a title="DB Check" href="/admin/dbcheck.php">Database Check</a> analyse database for
database or application problems</li>

<li>Rebuild <a title="Rebuild wordnet" href="/admin/buildwordnet.php">WordNet</a>/<a 
title="Rebuild gridimage_search" href="/admin/buildgridimage_search.php">Search Cache</a> - use if
tables become corrupted</li>

</ul>
<h3>Developer Tools</h3>
<ul>

<li><a title="Custom Search" href="/search.php?form=advanced&Special=1">Create Custom Search</a> - create a one off special search (sql required)</li>

<li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a> is a simple tool for checking
the internal land/sea map</li>

</ul>
{/if}
    
{include file="_std_end.tpl"}
