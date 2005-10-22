{include file="_std_begin.tpl"}


<h2>Administrative Tools</h2>
<ul>
<li><a href="/admin/moderation.php">Moderate</a> new photo submissions</li>

<li><a title="Trouble Tickets" href="/admin/tickets.php">Trouble Tickets</a> - 
   Deal with image problems</li>

<li><a title="Map Fixer" href="/admin/mapfixer.php">Map Fixer</a> allows the land percentage
for each 1km grid squares to be updated, which allows "that square is all at sea' to be 
corrected</li>
      
<li><a title="Category Consolidation" href="/admin/categories.php">Category Consolidation</a> - 
   Organise the user submitted categories</li>
 
<li><a title="API Keys" href="/admin/apikeys.php">API Keys</a> - 
   setup who has access to the API</li>

</ul>

<h2>Load Average</h2>

{dynamic}
<ul>
<li>{$uptime}</li>
</ul>
{/dynamic}

<img src="http://www.geograph.org.uk/img/cpuday.png" width="480" height="161"/>

<h2>Total Submissions</h2>
<img src="http://www.geograph.org.uk/img/submission_graph.png" width="480" height="161"/>

<h2>Daily Submission Rate</h2>
<img src="http://www.geograph.org.uk/img/rate.png" width="480" height="161"/>

<h2>Developer / Sysadmin Tools</h2>
<ul>

<li><a title="Recreate Maps" href="/admin/recreatemaps.php">Recreate Maps</a> - 
   force recreation of the most urgent maps</li>
   
<li><a title="Web Stats" href="http://www.geograph.org.uk/logs/">Web Stats</a> - 
   check the apache activity logs</li>

<li><a title="Server Stats" href="/admin/server.php">Server Stats</a> - 
   check server status</li>
   
<li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a> is a simple tool for checking
the internal land/sea map</li>

<li><a title="DB Check" href="/admin/dbcheck.php">Database Check</a> analyse database for
database or application problems</li>

<li>Rebuild <a title="Rebuild wordnet" href="/admin/buildwordnet.php">WordNet</a>/<a 
title="Rebuild gridimage_search" href="/admin/buildgridimage_search.php">Search Cache</a> - use if
tables become corrupted</li>

<li><a title="Search Stats" href="/admin/viewsearches.php">Search Statistics</a> - See the recent Search Activity</li>
<li><a title="Custom Search" href="/search.php?form=advanced&Special=1">Create Custom Search</a> - create a one off special search (sql required)</li>
<li><a title="Events" href="events.php">Event Diagnostics</a> - see what the event system is doing</li>


</ul>

    
{include file="_std_end.tpl"}
