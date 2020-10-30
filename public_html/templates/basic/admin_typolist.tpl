{assign var="page_title" value="Typo List"}
{include file="_std_begin.tpl"}

<h2>Typo Check List v0.8</h2>

<a href="/admin/watchlist.php">View images on the watchlist</a>

{if $data}

<script src="{"/sorttable.js"|revision}"></script>


<p>This page lists recently run typo searches, with the idea that over time a useful list of words can be built up. The searches are run manually at the moment, but with the idea that popular checkes could be run automatically and periodically</p>


<div class="interestBox" style="margin-left:10px">
	<h3 style=margin-top:0>Try a new search</h3>
	<form action="/admin/typohunter.php" method="get">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" />
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude" />
		<input type="submit" value="Find" /><br/>
		Profile/Engine: <select name="profile">
			<option value="phrase">phrase - legacy style 'substring' matching</option>
			<option value="keywords">keywords - new style whole word keyword matching</option>
			<option value="expression">expression - case sensitive regular-expression engine</option>
		</select>
	</form>
</div>


<p>NOTE: Only rows with a Profile set will be used by the watchlist.</p>

<table class="report sortable" id="opentickets" style="font-size:0.9em">
<thead><tr>
	<td>Include</td>
	<td>S</td>
	<td>Exclude</td>
	<td>Profile *</td>
	<td>Last Run</td>
	<td align="right">Found</td>
	<td align="right">Checked</td>
	<td>...</td>
	<td>Updated</td>
</tr></thead>
<tbody>

{foreach from=$data item=item}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><b><a href="/admin/typohunter.php?include={$item.include|escape:'url'}&amp;exclude={$item.exclude|escape:'url'}&amp;profile={$item.profile|escape:'url'}&amp;size={$item.last_size|escape:'url'}&amp;old_id={$item.typo_id}">{$item.include|escape:'html'}</a></b></td>
<td sortvalue="{$item.s}">&nbsp;</td>
<td style="max-width:100px">{$item.exclude|escape:'html'}</td>
<td>{$item.profile} <small>(<a href="?toggle={$item.typo_id}">Toggle</a>)</small></td>
<td>{$item.last_time}</td>
<td align="right"><b>{$item.last_results|thousends}</b></td>
<td align="right" style="color:gray">{if $item.profile != 'keywords'}{$item.last_size|thousends}{/if}</td>
<td sortvalue="{$item.updated}"><a href="/admin/typohunter.php?include={$item.include|escape:'url'}&amp;exclude={$item.exclude|escape:'url'}&amp;profile={$item.profile|escape:'url'}&amp;size={$item.last_size|escape:'url'}&amp;old_id={$item.typo_id}">Run now</a> | <a href="?delete={$item.typo_id}&amp;profile={$item.profile|escape:'url'}">Delete</a></td>
<td>{$item.updated}</td>
</tr>
{/foreach}
</tbody>
</table>

<ul>
	<li><b>Disabled</b> : Use the disabled profile so wont be used for watchlist, but can still run it manually if want</li>
	<li><b>Delete</b> : Deletes the search (only use this if it's not a useful typo search)</li>
</ul>

<hr/>
* Profile schedule for adding photos the <a href="/admin/watchlist.php">watchlist</a> page:
<ul>
	<li><b>phrase</b> : these are checked against the whole archive <b>every 7 days</b>, and against recent edits <b>hourly</b></li>
	<li><b>expression</b> : these are checked against the whole archive <b>every 7 days</b>, and against recent edits <b>hourly</b></li>
	<li><b>keywords</b> : these are checked against the whole archive <b>nightly</b>, and against new submissions <b>hourly</b></li>
	<li><b>disabled</b> : not checked on any automatic schedule - useful to keep the item in the above list</li>
</ul>
Note: Items with a 'exclude' are not at this time automatically run for the watchlist.

{else}
  <p>Nothing to see here.</p>
{/if}

<br/><hr/>
<h3>Add new rule to this list</h3>
<p>To add another to the list, just run a <a href="/admin/typohunter.php">search</a>: <small>(if find results, can then save the search for future)</small></p>
<div class="interestBox">
	<form action="/admin/typohunter.php" method="get">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" />
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude" />
		<input type="submit" value="Find" /><br/>
		Profile/Engine: <select name="profile">
			<option value="phrase">phrase - legacy style 'substring' matching</option>
			<option value="keywords">keywords - new style whole word keyword matching</option>
			<option value="expression">expression - case sensitive regular-expression engine</option>
		</select>
	</form>
</div>

<p>or bulk add: <small>(one 'include' per line, or a line in 'legacy' search text syntax*)</small></p>
<div class="interestBox">
	<form action="/admin/typolist.php" method="post">
		<textarea rows="10" cols="40" name="rows"></textarea>
		<input type="submit" value="Add All" /><br/>
		Profile/Engine: <select name="profile">
			<option value="phrase">phrase - legacy style 'substring' matching</option>
			<option value="keywords" selected>keywords - new style whole word keyword matching</option>
			<option value="expression">expression - case sensitive regular-expression engine</option>
		</select>
	</form>
</div>
<p>* only the words are extracted, special chars like ^ and + are ignored, as are negations.</p>

	<br/><br/>


{include file="_std_end.tpl"}
