{assign var="page_title" value="xmas $year"}
{include file="_std_begin.tpl"}

<h2>Christmas {$year}</h2>

{if $i}
<p>Open these <a href="/search.php?i={$i}">images in the search</a></p>
{/if}

<p align="right" style="font-size:0.7em">Map last updated {$imageupdate|date_format:"%A, %B %e, %Y at %T"}</p>

<img src="/imagemap.php?year={$year}" width="600" height="850" border="0" usemap="#imagemap"/>

{$imagemap}

{include file="_std_end.tpl"}
