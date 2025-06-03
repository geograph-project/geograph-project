{assign var="page_title" value="Check Submissions Tool"}
{include file="_std_begin.tpl"}

<h2>Check YOUR Submissions Tool</h2>

{if $errormsg}
<p class="error-text"><b>{$errormsg}</b></p> {/* style="color:red" */}
{/if}
<form action="/search.php" method="get" name="theForm">
	<input type="hidden" name="form" value="check"/>
	<div class="tabHolder">
		<a href="/search.php?form=text" class="tab">Advanced search</a>
		<a href="/search.php?form=simple" class="tab">Simple search</a>
		<a href="/search.php?form=first" class="tab">First Geographs</a>
		<span class="tabSelected">Check Submissions</span>
	</div>
	<div class="search-form-grid">
		<fieldset>
			<legend>Find images matching:</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>Find images matching:</b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell">&nbsp;</div>
				<div class="form-cell">{html_checkboxes name="check" options=$checks selected=$schecks separator="<br/>"}</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell">match</div>
				<div class="form-cell">{html_radios name="glue" options=$glues selected=$glue}</div>
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></div>
			</div>
			<div class="form-row">
				<div class="form-cell">options</div>
				<div class="form-cell">{html_checkboxes name="editpage_options" options=$editpage_options selected=$seditpage_options separator="&nbsp;"}</div>
				<div class="form-cell">&nbsp;</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Specify how you would like the results displayed</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>And specify how you would like the results displayed: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="displayclass">Format</label></div>
				<div class="form-cell">
					{html_radios name=displayclass options=$displayclasses selected=$displayclass separator="<br/>"}
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="breakby" id="l_breakby">Break by</label></div>
				<div class="form-cell colspan-2">
					<select name="breakby" id="breakby" size="1">
						{html_options options=$breakdowns selected=$breakby}
					</select>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="orderby" id="l_orderby">Order</label></div>
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
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Optionally limit results to</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>You can optionally limit to results to: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="user_name">Contributor</label></div>
				<div class="form-cell colspan-2">
					<input type="text" name="user_name" id="user_name" value="{dynamic}{$user_name|escape:'html'}{/dynamic}" class="searchinput" title="enter the nickname of a contributor, the full name should work too. if you know it you can enter the users ID followed by a colon" readonly="readonly"/> {/* style="width:200px" */}
					<div class="error-text"> {/* style="color:red" */}
						<img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="15" height="15" class="icon-spacer-right" align="left"/> {/* style="margin-right:10px" */}
						You can only search your images with this tool.
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="moderation_status">Classification</label></div>
				<div class="form-cell">
					<select name="moderation_status" id="moderation_status" size="1" class="searchinput">
						<option value=""> </option>
						{html_options options=$imagestatuses selected=$moderation_status}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="imageclass">Category</label></div>
				<div class="form-cell">
					<script type="text/javascript" src="/categories.js.php?full=1"></script>
					<select name="imageclass" id="imageclass" size="1" class="searchinput"  onfocus="prePopulateImageclass()" disabled="disabled">
						<option value=""></option>
						{if $imageclass}
							<option value="{$imageclass}" selected="selected">{$imageclass}</option>
						{/if}
						<option value="Other"></option>
					</select><input type="button" name="imageclass_enable_button" value="enable" onclick="prePopulateImageclass()"/>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
	{if $enable_forums}
			<div class="form-row">
				<div class="form-cell"><label for="topic_id">Discuss topic</label></div>
				<div class="form-cell">
					<select name="topic_id" id="topic_id" size="1" class="searchinput">
						<option value=""> </option>
						{html_options options=$topiclist selected=$topic_id}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
	{/if}
			<div class="form-row">
				<div class="form-cell"><label for="reference_index">Country</label></div>
				<div class="form-cell">
					<select name="reference_index" id="reference_index" size="1" class="searchinput">
						<option value=""> </option>
						{html_options options=$references selected=$reference_index}
					</select>
				</div>
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="submitted_startDay">Date submitted</label></div>
				<div class="form-cell colspan-2">
					between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
					and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="taken_startDay">Date taken</label></div>
				<div class="form-cell colspan-2">
					between {html_select_date prefix="taken_start" time=$taken_start start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
					and {html_select_date prefix="taken_end" time=$taken_end start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3"><small>
				You can just specify part of a date, for example just a year, or just month and year. Setting both the start and end date to the same value allows you to find pictures during that period, eg 'Jan 2001' or even just 1988. If you select just a month, then we will find just images taken during that month.</small>
				</div>
			</div>
		</fieldset>
	</div>
</form>

{literal}
<script type="text/javascript"><!--

function updateTarget(that) {
	ele = document.theForm.displayclass;
	val = '';
	for(q=0;q<ele.length;q++) {
		if (ele[q].checked)
			val = ele[q].value;
		ele[q].onclick = updateTarget;
	}
	ele[0].form.target = (val == 'searchtext')?'_search':'_self';
}

updateTarget();

// updateBreakBy function might be needed if not globally available from search_text.tpl's JS includes
// For now, assuming it's available or will be handled if an error arises.
// function updateBreakBy(that) { ... }


{/literal}
//--></script>

{include file="_std_end.tpl"}
