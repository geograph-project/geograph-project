{assign var="page_title" value="Special Search"}
{include file="_std_begin.tpl"}

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/js/datepicker/javascript/zebra_datepicker.js"></script>
<link rel="stylesheet" href="/js/datepicker/css/default.css" type="text/css">

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="search.php" method="post" name="theForm" target="_blank">
	<input type="hidden" name="form" value="admin"/>
	<p><b>Use the following options to customise your search</b>.<br/> </p>
		<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Search details:</b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="description">Description</label></td> 
			 <td>images, <input type="text" name="description" id="description" value="{$description|escape:'html'}" class="searchinput"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td><label for="searchq">sql where</label></td> 
			 <td>and <input type="text" name="searchq" id="searchq" value="{$searchq|escape:'html'}" class="searchinput" size="100"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Optionally centre results on:</b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="x">internal</label></td> 
			 <td>X<input type="text" name="x" id="x" value="{$x|escape:'html'}" class="searchinput"/>
			 Y<input type="text" name="y" id="y" value="{$y|escape:'html'}" class="searchinput"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>You can optionally limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="distance" id="l_distance">Distance to above</label></td> 
			 <td> 
				<select name="distance" id="distance" size="1" style="text-align:right" onchange="onlyone_part2(this.form)" onblur="onlyone_part2(this.form)"> 
				  <option value=""> </option> 
					{html_options options=$distances selected=$distance}
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="searchtext" id="l_searchtext"><b>containing keywords</b></label></td> 
			 <td><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput"/><br/>
			 <small>({newwin href="/help/search" text="open <b>text search help</b>"})</small></td> 
			 <td>eg Bridge</td> 
		  </tr> 
		  <tr> 
			 <td><label for="user_name">Contributor</label></td> 
			 <td colspan="2"> 
			 	<input type="text" name="user_name" id="user_name" value="{$user_name|escape:'html'}" class="searchinput" style="width:200px"
			 	title="enter the nickname of a contributor, the full name should work too. if you know it you can enter the users ID followed by a colon"/>
				{dynamic}
				{if $user->registered}
					<input type="button" value="you!" onclick="this.form.user_name.value='{$user->user_id}:{$user->realname|escape:"html"}'">
				{/if}
				{/dynamic}
				<input type="checkbox" name="user_invert_ind" id="user_invert_ind" {$user_invert_checked}/> <label for="user_invert_ind">exclude this contributor</label><br/>
				<small>({newwin href="/finder/contributors.php?popup" onclick="window.open(this.href,this.target); return false;" text="open Contributor Search screen"})</small></td> 
		  </tr> 
		  <tr> 
			 <td><label for="moderation_status">Classification</label></td> 
			 <td> 
				<select name="moderation_status" id="moderation_status" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$imagestatuses selected=$moderation_status}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="imageclass">Category</label></td> 
			 <td>
			 
<script type="text/javascript" src="/categories.js.php?full=1"></script>
			 
				<select name="imageclass" id="imageclass" size="1" class="searchinput"  onfocus="prePopulateImageclass()" disabled="disabled"> 
					<option value=""></option>
					{if $imageclass}
						<option value="{$imageclass}" selected="selected">{$imageclass}</option>
					{/if}
					<option value="Other"></option>				  
				</select><input type="button" name="imageclass_enable_button" value="enable" onclick="prePopulateImageclass()"/></td> 
			 <td>&nbsp;</td> 
		  </tr> 
{if $enable_forums}
		  <tr> 
			 <td><label for="topic_id">Discuss topic</label></td> 
			 <td> 
				<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$topiclist selected=$topic_id}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
{/if}
		  <tr> 
			 <td><label for="reference_index">Country</label></td> 
			 <td> 
				<select name="reference_index" id="reference_index" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$references selected=$reference_index} 
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="gridsquare">Myriad (<a href="/help/squares" title="What is a Myriad? (opens in new window)" target="_blank">?</a>)</label></td> 
			 <td> 
				<select name="gridsquare" id="gridsquare" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$prefixes selected=$gridsquare}
				</select></td> 
			 <td>&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td><label for="submitted_startDay">Date submitted</label></td> 
			 <td colspan="2"> 
                                between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type=text style="width:17px" name="__submitted_start" value="{$submitted_start}" id="submitted_start"/>
                                and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type=text style="width:17px" name="__submitted_end" value="{$submitted_end}" id="submitted_end"/>
			 </td> 
		  </tr> 
		  <tr> 
			 <td><label for="taken_startDay">Date taken</label></td> 
			 <td colspan="2"> 
				between {html_select_date prefix="taken_start" time=$taken_start start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type=text style="width:17px" name="__taken_start" value="{$taken_start}" id="taken_start"/>
                                and {html_select_date prefix="taken_end" time=$taken_end start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\""}<input type=text style="width:17px" name="__taken_end" value="{$taken_end}" id="taken_end"/>
				</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><small>
			 You can just specify part of a date, for example just a year, or just month and year. Setting both the start and end date to the same value allows you to find pictures during that period, eg 'Jan 2001' or even just 1988. If you select just a month, then we will find just images taken during that month.</small>
			 </td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>and specify how you would like the results displayed: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="displayclass">Format</label></td> 
			 <td> 
				<select name="displayclass" id="displayclass" size="1"> 
					{html_options options=$displayclasses selected=$displayclass}
				</select></td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="orderby" id="l_breakby">Break by</label></td> 
			 <td colspan="2"> 
				<select name="breakby" id="breakby" size="1"> 
					{html_options options=$breakdowns selected=$breakby}
				</select></td> 
		  </tr>
		  <tr> 
			 <td><label for="orderby" id="l_orderby">Order</label></td> 
			 <td colspan="2"> 
				<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);"> 
					{html_options options=$sortorders selected=$orderby}
				</select> <input type="checkbox" name="reverse_order_ind" id="reverse_order_ind" {$reverse_order_checked}/> <label for="reverse_order_ind" id="l_reverse_order_ind">reverse order</label></td> 
		  </tr> 
		  <tr> 
			 <td>&nbsp;</td> 
			 <td> {dynamic}
				<select name="resultsperpage" id="resultsperpage" style="text-align:right" size="1"> 
					{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
				</select> <label for="resultsperpage">results per page</label>{/dynamic}</td>
			 <td>&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></td>
			 
		  </tr> 
		</table></form>
  
{literal}
<script type="text/javascript"><!--

function updateBreakBy(that) {
	name = that.options[that.selectedIndex].value;
	if (name == 'gridimage_id')
		name = 'submitted';
	ele = that.form.breakby;
	for(q=0;q<ele.options.length;q++) {
		enabled = (name.length && ele.options[q].value.indexOf(name) == 0);
		ele.options[q].style.color = enabled?'':'#999999';
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
	setByValue(form.elements[name+'Year'],bits[0]); //value and text are the same :)
	setByValue(form.elements[name+'Month'],bits[1]); //month has zero padded value
	setByText(form.elements[name+'Day'],bits[2]); //day has zero padded text, but not value
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
	that.form.elements['__'+name].value = getSelText(that.form.elements[name+'Year'],2014)+'-'+getSelValue(that.form.elements[name+'Month'],'01')+'-'+getSelText(that.form.elements[name+'Day'],'01');
}
function getSelValue(ele,defa) {
	return ele.options[ele.selectedIndex].value || defa;
}
function getSelText(ele,defa) {
	return ele.options[ele.selectedIndex].text || defa;
}

$(document).ready(function() {

    $('#submitted_start').Zebra_DatePicker({
	direction: [false, '2005-02-08'],
	pair: $('#submitted_end'),
	zero_pad: true,
	onClear: clearDate,
	onSelect: updateDateDropdown
    });
    $('#submitted_end').Zebra_DatePicker({
        direction: [false, '2005-02-08'],
	//pair: $('#submitted_start'),
	zero_pad: true,
	onClear: clearDate,
	onSelect: updateDateDropdown
    });

    $('#taken_start').Zebra_DatePicker({
	direction: [false, '1880-01-01'],
	pair: $('#taken_end'),
	zero_pad: true,
	onClear: clearDate,
	onSelect: updateDateDropdown
    });
    $('#taken_end').Zebra_DatePicker({
	direction: [false, '1880-01-01'],
	//pair: $('#taken_start'),
	zero_pad: true,
	onClear: clearDate,
	onSelect: updateDateDropdown
    });

});

{/literal}
//--></script>


{include file="_std_end.tpl"}
