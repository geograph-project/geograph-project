{assign var="page_title" value="Games Approve"}
{assign var="rss_url" value="/games/approve.rss.php"}
{include file="_std_begin.tpl"}

<div style="float:right"><a href="/games/approve.rss.php" class="xml-rss">RSS</a></div>
<h2><a href="/games/">Geograph Games</a> </h2>

<p>This is a quick check to ensure inappropriate names don't make it to the scoreboard. Because the name doesn't show on the board until approved here it's an idea to aim for a quick turn-around. Note, this is probably only a temporary measure until a more robust system is devised</p>
	
<form action="{$script_name}" method="post">
<table class="report sortable" id="namelist"> 
<thead><tr><td sorted="asc">Name</td><td>Approve?</td></tr></thead>
<tbody>
{dynamic}

{foreach from=$names key=id item=username}
<tr>
<td>{$username|escape:"html"}</td>
<td><label for="y{$id}">Yes</label><input type="radio" name="a[{$id}]" value="1" id="y{$id}" checked/> /
<input type="radio" name="a[{$id}]" value="0" id="no{$id}"/><label for="n{$id}">No</label> </td>
</tr>
{/foreach}
{/dynamic}

</tbody>
</table>

<p><input type="submit" name="submit" value="Action this &gt;&gt;"/></p>
</form>

When done: <a href="/games/moversboard.php?g=1&more">refresh the scoreboard</a>
 		
{include file="_std_end.tpl"}
