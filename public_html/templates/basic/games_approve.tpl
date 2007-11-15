{assign var="page_title" value="Games Approve"}

{include file="_std_begin.tpl"}


<h2><a href="/games/">Geograph Games</a> </h2>
	
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

 		
{include file="_std_end.tpl"}
