{assign var="page_title" value="Server Status"}
{include file="_std_begin.tpl"}


<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Server Status</h2>

{dynamic}
<h3>Uptime</h3>
<ul>
<li>{$uptime}</li>
</ul>
{/dynamic}

<h3>Photo Storage</h3>
<ul>
<li>Total size of photo storage, including all generated thumbnails</li>
<li>{$photodir}</li>
</ul>

<h3>Maps</h3>
<ul>
<li>Cached map browsing graphics</li>
<li>{$mapdir}</li>
</ul>




{include file="_std_end.tpl"}
