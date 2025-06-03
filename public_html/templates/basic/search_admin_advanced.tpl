{assign var="page_title" value="Special Search"}
{include file="_std_begin.tpl"}

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/js/datepicker/javascript/zebra_datepicker.js"></script>
<link rel="stylesheet" href="/js/datepicker/css/default.css" type="text/css">

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p class="error-text"><b>{$errormsg}</b></p> {/* style="color:red" */}
{/if}
<form action="search.php" method="post" name="theForm" target="_blank">
	<input type="hidden" name="form" value="admin"/>
	<p><b>Use the following options to customise your search</b>.<br/> </p>
	<div class="search-form-grid">
		<fieldset>
			<legend>Search Details</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>Search details:</b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="description">Description</label></div>
				<div class="form-cell colspan-2">images, <input type="text" name="description" id="description" value="{$description|escape:'html'}" class="searchinput"/></div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="searchq">sql where</label></div>
				<div class="form-cell colspan-2">and <input type="text" name="searchq" id="searchq" value="{$searchq|escape:'html'}" class="searchinput" size="100"/></div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Optionally Centre Results On</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>Optionally centre results on:</b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="x">internal</label></div>
				<div class="form-cell colspan-2">
					X<input type="text" name="x" id="x" value="{$x|escape:'html'}" class="searchinput"/>
					Y<input type="text" name="y" id="y" value="{$y|escape:'html'}" class="searchinput"/>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Optionally Limit Results To</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>You can optionally limit to results to: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="distance" id="l_distance">Distance to above</label></div>
				<div class="form-cell">
					<select name="distance" id="distance" size="1" onchange="onlyone_part2(this.form)" onblur="onlyone_part2(this.form)"> {/* style="text-align:right" removed */}
						<option value=""> </option>
						{html_options options=$distances selected=$distance}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="searchtext" id="l_searchtext"><b>containing keywords</b></label></div>
				<div class="form-cell">
					<input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput"/><br/>
					<small>({newwin href="/help/search" text="open <b>text search help</b>"})</small>
				</div>
				<div class="form-cell">eg Bridge</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="user_name">Contributor</label></div>
				<div class="form-cell colspan-2">
					<input type="text" name="user_name" id="user_name" value="{$user_name|escape:'html'}" class="searchinput" title="enter the nickname of a contributor, the full name should work too. if you know it you can enter the users ID followed by a colon"/> {/* style="width:200px" removed */}
					{dynamic}
					{if $user->registered}
						<input type="button" value="you!" onclick="this.form.user_name.value='{$user->user_id}:{$user->realname|escape:"html"}'">
					{/if}
					{/dynamic}
					<input type="checkbox" name="user_invert_ind" id="user_invert_ind" {$user_invert_checked}/> <label for="user_invert_ind">exclude this contributor</label><br/>
					<small>({newwin href="/finder/contributors.php?popup" onclick="window.open(this.href,this.target); return false;" text="open Contributor Search screen"})</small>
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
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="gridsquare">Myriad (<a href="/help/squares" title="What is a Myriad? (opens in new window)" target="_blank">?</a>)</label></div>
				<div class="form-cell">
					<select name="gridsquare" id="gridsquare" size="1" class="searchinput">
						<option value=""> </option>
						{html_options options=$prefixes selected=$gridsquare}
					</select>
				</div>
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="submitted_startDay">Date submitted</label></div>
				<div class="form-cell colspan-2">
					between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type="text" name="__submitted_start" value="{$submitted_start}" id="submitted_start" class="date-display-field"/> {/* style="width:17px" removed */}
					and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type="text" name="__submitted_end" value="{$submitted_end}" id="submitted_end" class="date-display-field"/> {/* style="width:17px" removed */}
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="taken_startDay">Date taken</label></div>
				<div class="form-cell colspan-2">
					between {html_select_date prefix="taken_start" time=$taken_start start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type="text" name="__taken_start" value="{$taken_start}" id="taken_start" class="date-display-field"/> {/* style="width:17px" removed */}
					and {html_select_date prefix="taken_end" time=$taken_end start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type="text" name="__taken_end" value="{$taken_end}" id="taken_end" class="date-display-field"/> {/* style="width:17px" removed */}
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3"><small>
				You can just specify part of a date, for example just a year, or just month and year. Setting both the start and end date to the same value allows you to find pictures during that period, eg 'Jan 2001' or even just 1988. If you select just a month, then we will find just images taken during that month.</small>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Specify how you would like the results displayed</legend>
			<div class="form-row">
				<div class="form-cell section-header colspan-3"><b>and specify how you would like the results displayed: </b></div> {/* style="background:#dddddd;" */}
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="displayclass">Format</label></div>
				<div class="form-cell">
					<select name="displayclass" id="displayclass" size="1">
						{html_options options=$displayclasses selected=$displayclass}
					</select>
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
					<select name="resultsperpage" id="resultsperpage" size="1"> {/* style="text-align:right" removed */}
						{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
					</select> <label for="resultsperpage">results per page</label>{/dynamic}
				</div>
				<div class="form-cell">&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></div>
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
		ele.options[q].style.color = enabled?'':'#999999'; // Dynamic style, leave as is
		if (ele.options[q].selected && !enabled)
			ele.selectedIndex = 0;
	}
}

updateBreakBy(document.theForm.orderby);

function clearDate(element) {
	updateDateDropdown('','--',null,element?element:this);
}
function updateDateDropdown(date_formatted,date_raw,date_object,element) {
	var name = element.prop('id');
	var form = element.get(0).form;
	var bits = date_raw.split(/-/);
	setByValue(form.elements[name+'Year'],bits[0]);
	setByValue(form.elements[name+'Month'],bits[1]);
	setByText(form.elements[name+'Day'],bits[2]);
}
function setByValue(ele,value) {
	for(q=0;q<ele.options.length;q++)
		if (ele[q].value == value)
			ele.selectedIndex = q;
}
function setByText(ele,value) {
	for(q=0;q<ele.options.length;q++)
		if (ele[q].text == value)
			ele.selectedIndex = q;
}

function updateHiddenDate(that) {
	var name = that.name.replace(/(Year|Month|Day)$/,'');
	that.form.elements['__'+name].value = getSelText(that.form.elements[name+'Year'],today.substring(0,4))+'-'+getSelValue(that.form.elements[name+'Month'],'01')+'-'+getSelText(that.form.elements[name+'Day'],'01');
}
function getSelValue(ele,defa) {
	return ele.options[ele.selectedIndex].value || defa;
}
function getSelText(ele,defa) {
	return ele.options[ele.selectedIndex].text || defa;
}

var today = new Date(); // Define today for updateHiddenDate fallback if not already global from other scripts.
// It's better if 'today' is passed from Smarty like in search_text.tpl for consistency.
// For now, this JS 'today' will work if the Smarty var 'today' isn't available to updateHiddenDate's getSelText.


$(document).ready(function() {
    var datePickerOptions = {
        pair: null, // Will be set conditionally
        zero_pad: true,
        onClear: clearDate,
        onSelect: updateDateDropdown
    };

    $('#submitted_start').Zebra_DatePicker($.extend({ direction: [false, '2005-02-08'], pair: $('#submitted_end') }, datePickerOptions));
    $('#submitted_end').Zebra_DatePicker($.extend({ direction: [false, '2005-02-08'] }, datePickerOptions));

    $('#taken_start').Zebra_DatePicker($.extend({ direction: [false, '1880-01-01'], pair: $('#taken_end') }, datePickerOptions));
    $('#taken_end').Zebra_DatePicker($.extend({ direction: [false, '1880-01-01'] }, datePickerOptions));
});

{/literal}
//--></script>

{include file="_std_end.tpl"}
