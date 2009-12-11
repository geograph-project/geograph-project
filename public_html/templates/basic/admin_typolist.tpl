{assign var="page_title" value="Typo List"}
{include file="_std_begin.tpl"}

<h2>Typo Check List v0.2</h2>

{if $data}

<script src="{"/sorttable.js"|revision}"></script>


<p>This page lists recently run typo searches, with the idea that over time a useful list of words can be built up. The searches are run manually at the moment, but with the idea that popular checkes could be run automatically and periodically</p>

<table class="report sortable" id="opentickets" style="font-size:0.9em">
<thead><tr>
	<td>Include</td>
	<td>Exclude</td>
	<td>Last Run</td>
	<td align="right">Found</td>
	<td align="right">Total</td>
	<td>...</td>
</tr></thead>
<tbody>

{foreach from=$data item=item}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><b><a href="/admin/typohunter.php?include={$item.include|escape:'url'}&amp;exclude={$item.exclude|escape:'url'}&amp;last_size={$item.last_size|escape:'url'}{if $item.title}&amp;title={$item.title|escape:'url'}{/if}">{$item.include|escape:'html'}</a></b></td>
<td>{$item.exclude|escape:'html'}</td>
<td>{$item.last_time}</td>
<td align="right"><b>{$item.last_results|thousends}</b></td>
<td align="right" style="color:gray">{$item.last_size|thousends}</td>
<td sortvalue="{$item.updated}"><a href="/admin/typohunter.php?include={$item.include|escape:'url'}&amp;exclude={$item.exclude|escape:'url'}&amp;last_size={$item.last_size|escape:'url'}{if $item.title}&amp;title={$item.title|escape:'url'}{/if}">Run now</a> | <a href="?hide={$item.typo_id}">Hide</a> | <a href="?delete={$item.typo_id}">Delete</a></td>
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

<p>To add another to the list, just run a <a href="/admin/typohunter.php">search</a>: <small>(if results are found it will be added)</small></p>
<div class="interestBox">
	<form action="/admin/typohunter.php" method="get">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" />
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude" />
		<input type="submit" value="Find" /><br/>
		<input type="checkbox" name="title" {if $title} checked="checked"{/if} id="title" /> <label for="title">Search <b>title</b> as well as description (please ONLY use it if need too)</label> 
	</form>
</div>
	
<p>or bulk add: <small>(one 'include' per line, or a line in 'legacy' search text syntax*)</small></p>
<div class="interestBox">
	<form action="/admin/typolist.php" method="post">
		<textarea rows="10" cols="40" name="rows"></textarea>
		<input type="submit" value="Add All" /><br/>
		<input type="checkbox" name="title" {if $title} checked="checked"{/if} id="title" /> <label for="title">Search <b>title</b> as well as description</label> 
	</form>
</div>
<p>* only the words are extracted, special chars like ^ and + are ignored, as are negations.</p>
	
	<br/><br/>

    
{include file="_std_end.tpl"}
