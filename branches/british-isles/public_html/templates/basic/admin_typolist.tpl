{assign var="page_title" value="Typo List"}
{include file="_std_begin.tpl"}


<h3>Typo Check List v0.1</h3>

{if $data}

<script src="{"/sorttable.js"|revision}"></script>


<p>This page lists recently run typo searches, with the idea that over time a useful list of words can be built up. The searches are run manually at the moment, but with the idea that popular checkes could be run automatically and periodically</p>

<table class="report sortable" id="opentickets" style="font-size:0.9em">
<thead><tr>
	<td>Include</td>
	<td>Exclude</td>
	<td>Last Run</td>
	<td>Found</td>
	<td>...</td>
</tr></thead>
<tbody>

{foreach from=$data item=item}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><b><a href="/admin/typohunter.php?include={$item.include|escape:'url'}&amp;exclude={$item.exclude|escape:'url'}&amp;title={$item.title|escape:'url'}">{$item.include|escape:'html'}</a></b></td>
<td>{$item.exclude|escape:'html'}</td>
<td>{$item.last_time}</td>
<td align="right"><b>{$item.last_results|thousends}</b><span style="color:gray">/{$item.last_size|thousends}</span></td>
<td><a href="?hide={$item.typo_id}">Hide</a> <a href="?delete={$item.typo_id}">Delete</a></td>
</tr>
{/foreach}
</tbody>
</table>

<ul>
	<li><b>Hide</b> : Hides the search from the list for a few days (useful if you have checked the list of results)</li>
	<li><b>Delete</b> : Deletes the search (only use this if its not a useful typo search)</li>
</ul>

{else}
  <p>Nothing to see here.</p>
{/if}

<br/><hr/><br/>

<p>To add another to the list, just run a <a href="/admin/typohunter.php">search</a>: </p>
<div class="interestBox">
	<form action="/admin/typohunter.php" method="get">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" />
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude" />
		<input type="submit" value="Find" /><br/>
		<input type="checkbox" name="title" {if $title} checked="checked"{/if} id="title" /> <label for="title">Search <b>title</b> as well as description (please ONLY use it if need too)</label> 
		
		
	</form>
</div>
	
	<br/><br/>

    
{include file="_std_end.tpl"}
