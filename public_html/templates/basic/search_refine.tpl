{assign var="page_title" value="Refine Search"}
{include file="_std_begin.tpl"}

<h2>Refine Search</h2>
{dynamic}

{if $errormsg}
<p class="error-text"><b>{$errormsg}</b></p> {/* style="color:red" */}
{/if}

{if $token}
	<p>Your search has been updated, <a href="/search.php?i={$i}&amp;t={$token}">continue to results</a></p>
{else}

{if $i}
<p>Search for images<i>{$searchdesc|escape:"html"}</i> with {$count} results</p>
{/if}


{if $user->user_id != $criteria->user_id}
	<div class="warning-box"> {/* style="background-color:pink; color:black; border:2px solid red; padding:10px;" */}
		<img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" class="icon-spacer-right"/> {/* style="margin-right:10px" */}
		<b>This is not your search</b>, as a moderator you can edit the search, but please only use this facility with good reason.<br/>
	</div>
	<br/>
{/if}

<form action="/refine.php?i={$i}" method="post" name="theForm">
	<div class="search-form-grid">
		<fieldset>
			<legend>Search Title</legend>
			<div class="form-row">
				<div class="form-cell"><label for="searchdesc">Title</label></div>
				<div class="form-cell colspan-2 font-large"> {/* style="font-size:1.1em;" */}
					images<input type="text" name="searchdesc" id="searchdesc" value="{$searchdesc|escape:'html'}" class="searchinput width-xlarge font-large" size="80" maxlength="255"/> {/* style="font-size:1.1em; width:640px;" */}
				</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Import More Images</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>import more images: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3">coming soon...</div>
			</div>
			{comment}
			<tr>
				<td><label for="pastebox">paste</label></td>
				<td colspan="2"> Paste your list here, either comma or space separated or just surrounded with [[[ ]]] <br/>
					<script type="text/javascript">{literal}
					current = readCookie('markedImages')
					if (current && current != '') {
					splited = current.commatrim().split(',');
					document.write('<div style="border: 1px solid lightgrey;">Marked Images['+(splited.length+0)+']: <a title="Insert marked image list" href="#" onclick="document.getElementById(\'pastebox\').value += \' \'+returnMarkedImages(); return false;" onMouseOver="window.status=\'Insert marked image list\'; return true" onMouseOut="window.status=\'\'; return true"><b>Insert into Box</b></a> (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear</a>)</div>');
					}{/literal}
					</script>
					<textarea name="pastebox" id="pastebox" rows="6" cols="80" class="font-small"></textarea> {/* style="font-size:0.9em" */}
				</td>
			</tr>
			<tr>
				<td><label for="fromsearch">from Search</label></td>
				<td>identifier: <input type="text" name="fromsearch" id="fromsearch" value="" size="8" maxlength="12"/><br/><small> (enter the number from the 'i' parameter displayed on search result URL)</small></td>
				<td class="text-danger">note: only first 500 results will be imported via this method</td> {/* style="color:red" */}
			</tr>
			{/comment}
		</fieldset>

		<fieldset>
			<legend>Reorder/Remove Current Images</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>reorder/remove current images: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3">coming soon...</div>
			</div>
			{comment}
			<tr>
				<td>&nbsp;</td>
				<td><a href="/refine.php?i={$i}&amp;popup">Reorganise current images</a> (opens in a new window)</td>
				<td>&nbsp;</td>
			</tr>
			{/comment}
		</fieldset>

		<fieldset>
			<legend>Specify how you would like the results displayed</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>specify how you would like the results displayed: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="displayclass">format</label></div>
				<div class="form-cell">
					<select name="displayclass" id="displayclass" size="1">
						{html_options options=$displayclasses selected=$displayclass}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="breakby" id="l_breakby">break by</label></div>
				<div class="form-cell colspan-2">
					<select name="breakby" id="breakby" size="1">
						{html_options options=$breakdowns selected=$breakby}
					</select>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="orderby" id="l_orderby">order</label></div>
				<div class="form-cell colspan-2">
					<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);">
						{html_options options=$sortorders selected=$orderby}
					</select> <input type="checkbox" name="reverse_order_ind" id="reverse_order_ind" {$reverse_order_checked}/> <label for="reverse_order_ind" id="l_reverse_order_ind">reverse order</label>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell">&nbsp;</div>
				<div class="form-cell"> {dynamic}
					<select name="resultsperpage" id="resultsperpage" size="1"> {/* style="text-align:right" */}
						{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
					</select> <label for="resultsperpage">results per page</label>{/dynamic}
				</div>
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Save"/></div>
			</div>
		</fieldset>
	</div>
</form>

{literal}
<script type="text/javascript"><!--

function updateBreakBy(that) {
	name = that.options[that.selectedIndex].value;
	if (name == 'gridimage_id')
		name = 'submitted';
	ele = that.form.breakby;
	for(q=0;q<ele.options.length;q++) {
		enabled = (name.length && ele.options[q].value.indexOf(name) == 0);
		ele.options[q].style.color = enabled?'':'#999999'; // This dynamic style should remain
		if (ele.options[q].selected && !enabled)
			ele.selectedIndex = 0;
	}
	that.form.reverse_order_ind.disabled = (that.value == 'dist_sqd' || that.value == 'sequence' || that.value == 'random' || that.value == 'relevance' || that.value == '');
}

updateBreakBy(document.theForm.orderby);

{/literal}
//--></script>
{/if}
{/dynamic}
{include file="_std_end.tpl"}
