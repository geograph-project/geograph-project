{assign var="page_title" value="Advanced Search"}
{include file="_std_begin.tpl"}

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
{if $i}
	{if $fullText}
		<div class="interestBox" id="show1"> {/* style="border:1px solid pink;display:none;" */}
			This search was powered by the new <a href="/help/search_new">word search index</a>, which has different capabilities to the old database, so the options offered vary.
			{if !$engine->criteria->sphinx.no_legacy}
				You can access the <a href="/search.php?i={$i}&amp;form=advanced&amp;legacy=true">old advanced form here</a>.
			{/if}
			<br/><br/>
			<a href="javascript:void(hide_tree(1));">close</a>
		</div>

		<div class="interestBox" id="hide1"> {/* style="border:1px solid pink; float:right; width:200px; position:relative;" */}
			<b>Not seeing the page you expect?</b>
			<a href="javascript:void(show_tree(1));">expand...</a>
		</div>
	{/if}

<h2>Advanced Photo Search</h2>

<p>Original Search: <tt>images{$searchdesc|escape:"html"}</tt></p>

{else}
	<h2>Photograph Search <a href="/article/Searching-on-Geograph" text="More information on the Search Engine" class="about">About</a></h2>
{/if}
<form action="/search.php?form=text" method="post" name="theForm" onsubmit="this.imageclass.disabled=false"> {/* style="background-color:#f9f9f9" */}
        <input type="hidden" name="form" value="text{$i}"/>

	<div class="tabHolder">
		<a href="/search.php?form=basic" class="tab">Simple search</a>
		<span class="tabSelected">Advanced search</span>
		{dynamic}
		{if $user->registered}
		<a href="/search.php?form=advanced&amp;legacy=true" class="tab"><small>Old advanced</small></a>
		{/if}
		{/dynamic}
		<a href="/search.php?form=first" class="tab">First Geographs</a>
	</div>

	<div class="search-form-grid">
		<fieldset>
			<legend>Centered Search</legend>
			<div class="form-row">
				<div class="form-cell colspan-2"> {/* style="padding-top:8px" */}
					Show images within <select name="distance" id="distance" size="1"> {/* style="text-align:right" */}
					<option value=""> </option>
						{html_options options=$distances selected=$distance}
					</select> of <select id="selector" onchange="showLocationBox()">
					<option value="gridref"{if $gridref} selected{/if}>Grid reference</option>
					<option value="postcode"{if $postcode} selected{/if}>Postcode</option>
					<option value="placename"{if $placename} selected{/if}>Placename</option>
					<option value="county_id"{if $county_id} selected{/if}>County</option>
					</select>:
				</div>
				<div class="form-cell nowrap">&nbsp;<input type="submit" value="Find"/></div>
			</div>
			<div class="form-row" id="tr_gridref">
				<div class="form-cell"><label for="gridref" id="l_gridref">grid reference</label></div>
				<div class="form-cell"><input type="text" name="gridref" id="gridref" value="{$gridref|escape:'html'}" class="searchinput"/></div>
				<div class="form-cell">eg <tt>TQ 7050</tt> or <tt>N2343</tt></div>
			</div>
			<div class="form-row" id="tr_postcode">
				<div class="form-cell"><label for="postcode" id="l_postcode">postcode</label></div>
				<div class="form-cell"><input type="text" name="postcode" id="postcode" value="{$postcode|escape:'html'}" class="searchinput"/></div>
				<div class="form-cell nowrap">eg <tt>RH13 1BU</tt> (GB &amp; NI)</div>
			</div>
			<div class="form-row" id="tr_placename">
				<div class="form-cell"><label for="placename" id="l_placename">placename</label></div>
				<div class="form-cell"><input type="text" name="placename" id="placename" value="{$placename|escape:'html'}" class="searchinput"/></div>
				<div class="form-cell">eg <tt>Peterborough</tt></div>
			</div>
			<div class="form-row" id="tr_county_id">
				<div class="form-cell"><label for="county_id" id="l_county_id">centre of county</label></div>
				<div class="form-cell">
					<select name="county_id" id="county_id" size="1" class="searchinput">
					<option value=""> </option>
						{html_options options=$countylist selected=$county_id}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
		</fieldset>

		<div class="form-row">
			<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
		</div>

		<fieldset>
			<legend>Word Match Search <a href="/article/Word-Searching-on-Geograph" title="open word search help" class="about">About</a></legend>
			<div class="form-row">
				<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
			</div>
			<div class="form-row" onmouseover="showMyHelpDiv('keyword',true);" onmouseout="showMyHelpDiv('keyword',false);">
				<div class="form-cell"><label for="searchtext" id="l_searchtext">keywords</label></div>
				<div class="form-cell"><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput" size="60" maxlength="250" onfocus="showMyHelpDiv('keyword',true);" onblur="showMyHelpDiv('keyword',false);"/>
				</div>
				<div class="form-cell">eg <tt>bridge</tt></div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3 section-spacer">&nbsp;
					<div id="keyword_help">
						<div>
							<ul>
								<li>Separate multiple keywords with spaces, all keywords are required (ie AND)</li>
								<li>Enter a <b>tag</b>, in [...], for example: <tt>[footpath]</tt></li>
								<li>Only matches whole words, punctuation is not searchable</li>
								<li>Not case sensitive</li>
								<li><b>Looking for exact match?</b> <tt>=bridge</tt><br/>&nbsp; Prefix a keyword with <tt>=</tt> (<tt>bridge</tt> matches bridges, bridging etc too)</small></li>
								<li><b>Currently searches</b>
									<ul>
										<li>title, description, tags, category, photographer name and Shared Description</li>
										<li>image taken date ( <tt>20071103</tt>, <tt>200711</tt>, <tt>2007</tt> or even <tt>April</tt>)</li>
										<li>subject grid-reference <span class="nowrap">( <tt>SH1234</tt>, <tt>SH13</tt> or just <tt>SH</tt> )</span></li>
									</ul>
									<i>(can optionally limit matches to a particular field, see 'About' above)</i>
								</li>
								<li>Can match phrases <tt>"road bridge" (requires words be adjacent)</tt></li>
								<li>Can use OR between keywords <span class="nowrap"><tt>bridge OR bont OR pont</tt></span></li>
								<li>Can exclude words/terms <tt>canal -river</tt> or <tt>river -"road bridge"</tt></li>
								<li>Instead run an ANY search <tt>~bridge road river</tt></li>
								<li><i>... plus more. See 'About' just above.</i></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</fieldset>

		<div class="form-row">
			<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
		</div>

		<fieldset>
			<legend>Limit Results To</legend>
			<div class="form-row">
				<div class="form-cell colspan-3 section-header">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="tag_select">Tag</label></div>
				<div class="form-cell colspan-2">
					<select name="tag_select[]" id="tag_select" multiple="multiple" class="searchinput" style="width: 100%;"></select>
				</div>
            </div>
			<div class="form-row">
				<div class="form-cell"><label for="user_name">Contributor</label></div>
				<div class="form-cell colspan-2">
					<input type="text" name="user_name" id="user_name" value="{$user_name|escape:'html'}" class="searchinput" title="enter the nickname of a contributor, the full name should work too. if you know it you can enter the users ID followed by a colon"/>
					{dynamic}
					{if $user->registered}
						<input type="button" value="you!" onclick="this.form.user_name.value='{$user->user_id}:{$user->realname|escape:"html"}'">
					{/if}
					{/dynamic}
					&nbsp; <input type="checkbox" name="user_invert_ind" id="user_invert_ind" {$user_invert_checked}/> <label for="user_invert_ind">exclude this contributor</label><br/>
					<small>({newwin href="/finder/contributors.php?popup" onclick="window.open(this.href,this.target); return false;" text="open Contributor Search screen"}) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <small>(NOTE: exclude <u>ONLY</u> works if enter something in keywords box above)</small></small>
				</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="moderation_status">Classification</label></div>
				<div class="form-cell">
					| <input type="radio" name="moderation_status" value="" checked/>either
					| {html_radios name="moderation_status" options=$imagestatuses selected=$moderation_status separator=" | "}
					<input type="checkbox" name="first" value="1" {if $first}checked{/if}/>first only
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
			<div class="form-row">
				<div class="form-cell"><label for="reference_index">Country</label></div>
				<div class="form-cell">
					| <input type="radio" name="reference_index" value="" checked/>either
					| {html_radios name="reference_index" options=$references selected=$reference_index separator=" | "}
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell"><label for="gridsquare">Myriad ({newwin href="/help/squares" title="What is a Myriad?" text="?"})</label></div>
				<div class="form-cell">
					<select name="gridsquare" id="gridsquare" size="1" class="searchinput">
					<option value=""> </option>
						{html_options options=$prefixes selected=$gridsquare}
					</select>
				</div>
				<div class="form-cell">&nbsp;</div>
			</div>
			<div class="form-row" onmouseover="showMyHelpDiv('date',true);" onmouseout="showMyHelpDiv('date',false);">
				<div class="form-cell"><label for="submitted_startDay">Date submitted</label></div>
				<div class="form-cell colspan-2">
					between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}<input type="text" class="date-display-field" name="__submitted_start" value="{$submitted_start|replace:'0-0-0':''}" id="submitted_start"/>
					and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}<input type="text" class="date-display-field" name="__submitted_end" value="{$submitted_end|replace:'0-0-0':''}" id="submitted_end"/>
				</div>
			</div>
			<div class="form-row" onmouseover="showMyHelpDiv('date',true);" onmouseout="showMyHelpDiv('date',false);">
				<div class="form-cell"><label for="taken_startDay">Date taken</label>
					<div id="date_help">
						<div>
							<ul>
								<li>You can just specify part of a date, for example just a year, or just month and year.</li>
								<li>Setting both the start and end date to the same value allows you to find pictures during that period, eg 'Jan 2001' or even just 1988</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="form-cell">
					between {html_select_date prefix="taken_start" time=$taken_start start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}<input type="text" class="date-display-field" name="__taken_start" value="{$taken_start|replace:'0-0-0':''}" id="taken_start"/>
					and {html_select_date prefix="taken_end" time=$taken_end start_year="1880" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onchange=\"updateHiddenDate(this);\" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}<input type="text" class="date-display-field" name="__taken_end" value="{$taken_end|replace:'0-0-0':''}" id="taken_end"/>
				</div>
				<div class="form-cell">&nbsp;<input type="submit" value="Find"/></div>
			</div>
		</fieldset>

		<div class="form-row">
			<div class="form-cell colspan-3 section-spacer">&nbsp;</div>
		</div>

		<fieldset>
			<legend>Display Options</legend>
			<div class="form-row">
				<div class="form-cell colspan-3 section-header">&nbsp;</div>
			</div>
			<div class="form-row">
				<div class="form-cell colspan-3">I want to display a <select name="displayclass" id="displayclass" size="1">
						{html_options options=$displayclasses selected=$displayclass}
					</select> of <select name="resultsperpage" id="resultsperpage" size="1">
						{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
					</select> images per page,<br/> at most {newwin href="/faq3.php?q=search#172" title="Read more" text="one"} image from each <select name="groupby" id="groupby" size="1">
						{html_options options=$groupbys selected=$groupby}
					</select>,<br/> would like a heading separating images by <select name="breakby" id="breakby" size="1">
						{html_options options=$breakdowns selected=$breakby}
					</select>,<br/> and sorted in <span class="nowrap">(<input type="checkbox" name="reverse_order_ind" {$reverse_order_checked}/> reverse)
					<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);">
						{html_options options=$sortorders selected=$orderby}
					</select> order.</big>
				</div>
			</div>
		</fieldset>

		<div class="form-row">
			<div class="form-cell colspan-2">&nbsp;</div>
			<div class="form-cell">&nbsp;<input type="submit" value="Find"/></div>
		</div>
	</div>
</form>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/js/datepicker/javascript/zebra_datepicker.js"></script>
<link rel="stylesheet" href="/js/datepicker/css/default.css" type="text/css">
{literal}
<script type="text/javascript">
// Ensure Select2 CSS and JS are linked in _std_begin.tpl or similar global template
// For example:
// <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
// <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
// (Assuming these are added in the main page layout)

var today = '{/literal}{$smarty.now|date_format:"%Y-%m-%d"}{literal}';

$(document).ready(function() {
    $('#tag_select').select2({
        ajax: {
            url: '/tags/tags.json.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data, params) {
                var ignoredPrefixes = ['term', 'category', 'cluster', 'wiki'];
                return {
                    results: $.map(data, function(item) {
                        var text = item.tag;
                        if (item.prefix && $.inArray(item.prefix, ignoredPrefixes) === -1) {
                            text = item.prefix + ':' + text;
                        }
                        // Remove any HTML tags from text, similar to original code
                        text = text.replace(/<[^>]*>/ig, "");
                        text = text.replace(/['"]+/ig, " ");


                        return { id: text, text: text };
                    })
                };
            },
            cache: true
        },
        placeholder: 'Type to search for tags or add new ones',
        minimumInputLength: 2,
        tags: true, // Allow creation of new tags
        tokenSeparators: [','], // Allow creating tags on comma
        allowClear: true,
        width: '100%' // Ensure it takes the cell width
    });

	/* Commented out loadTagSuggestions and useTag as they are no longer used with the select element
	$(function() {
		//$('#tagParent').hide(); // tagParent is removed
	});

	function loadTagSuggestions(that,event) { ... } // Removed for brevity
	function useTag(tag) { ... } // Removed for brevity
	*/

    function updateBreakBy(that) {
        var name = that.options[that.selectedIndex].value;
        if (name == 'gridimage_id')
            name = 'submitted';
        var ele = that.form.breakby;
        for(var q=0;q<ele.options.length;q++) {
            var enabled = (name.length && ele.options[q].value.indexOf(name) == 0) || name.length == 0;
            ele.options[q].style.color = enabled?'':'#999999';
            if (ele.options[q].selected && !enabled)
                ele.selectedIndex = 0;
        }
        that.form.reverse_order_ind.disabled = (that.value == 'dist_sqd' || that.value == 'sequence' || that.value == 'random' || that.value == 'relevance' || that.value == '');
    }
    // Attach to existing onchange, or call it if needed: updateBreakBy($('select[name="orderby"]')[0]);


    function showLocationBox() {
        var ele = document.getElementById('selector');
        for(var q=0;q<ele.options.length;q++) {
            var trElement = document.getElementById('tr_'+ele.options[q].value);
            if (trElement) { // Check if element exists
                trElement.style.display = ele.options[q].selected?'':'none';
            }
            var inputElement = document.getElementById(ele.options[q].value);
            if (inputElement) { // Check if element exists
                inputElement.disabled = !ele.options[q].selected;
            }
        }
    }
    // AttachEvent(window,'load',showLocationBox,false); // Already exists

    var timers = new Array();
    function showMyHelpDiv(which,show) {
        if (timers[which]) {
            clearTimeout(timers[which]);
        }
        timers[which] = setTimeout(function() {
            var helpDiv = document.getElementById(which+'_help');
            if (helpDiv) {
                helpDiv.style.display=show?'':'none';
            }
            clearTimeout(timers[which]);
            timers[which] = null;
        },400);
    }
    // Make showMyHelpDiv globally accessible if it wasn't already
    window.showMyHelpDiv = showMyHelpDiv;


    function clearDate(element) {
        updateDateDropdown('','--',null,element?element:this);
    }
    function updateDateDropdown(date_formatted,date_raw,date_object,element) {
        var name = $(element).prop('id'); // Use jQuery 'this' if element is not passed
        var form = $(element).get(0).form;
        var bits = date_raw.split(/-/);
        setByValue(form.elements[name+'Year'],bits[0]);
        setByValue(form.elements[name+'Month'],bits[1]);
        setByText(form.elements[name+'Day'],bits[2]);
    }
    function setByValue(ele,value) {
        for(var q=0;q<ele.options.length;q++)
            if (ele[q].value == value)
                ele.selectedIndex = q;
    }
    function setByText(ele,value) {
        for(var q=0;q<ele.options.length;q++)
            if (ele[q].text == value)
                ele.selectedIndex = q;
    }
    window.updateHiddenDate = function(that) { // Make it global for onchange attributes
        var name = that.name.replace(/(Year|Month|Day)$/,'');
        if (that.form.elements[name+'Year'].selectedIndex == 0 && that.form.elements[name+'Month'].selectedIndex  == 0 && that.form.elements[name+'Day'].selectedIndex == 0) {
            that.form.elements['__'+name].value = '';
        } else {
            that.form.elements['__'+name].value = getSelText(that.form.elements[name+'Year'],today.substring(0,4))+'-'+getSelValue(that.form.elements[name+'Month'],'01')+'-'+getSelText(that.form.elements[name+'Day'],'01');
        }
    }
    function getSelValue(ele,defa) {
        return ele.options[ele.selectedIndex].value || defa;
    }
    function getSelText(ele,defa) {
        return ele.options[ele.selectedIndex].text || defa;
    }
    // Make date functions globally accessible if used by inline onchange attributes
    window.clearDate = clearDate;
    window.updateDateDropdown = updateDateDropdown;


    var datePickerSharedOptions = {
        zero_pad: true,
        onClear: clearDate,
        onSelect: updateDateDropdown
    };

    $('#submitted_start').Zebra_DatePicker($.extend({}, datePickerSharedOptions, {
        direction: [false, today],
        pair: $('#submitted_end')
    }));
    $('#submitted_end').Zebra_DatePicker($.extend({}, datePickerSharedOptions, {
        direction: [$('#submitted_start').val() || false, today]
    }));

    $('#taken_start').Zebra_DatePicker($.extend({}, datePickerSharedOptions, {
        direction: [false, today],
        pair: $('#taken_end')
    }));
    $('#taken_end').Zebra_DatePicker($.extend({}, datePickerSharedOptions, {
        direction: [$('#taken_start').val() || false, today]
    }));

	$('input, textarea, select').not('#tag_select').change(function() { // Exclude select2 from this highlight logic
		var $this = $(this);
		if ($this.is(':checkbox') || $this.is(':radio')) {
			if ($this.prop("checked"))
				$this.addClass('selectedHighlight');
			else
				$this.removeClass('selectedHighlight');
		} else if (!$this.is(':button') && !$this.is(':submit')) {
			if ($this.val() && $this.val().length > 0)
				$this.addClass('selectedHighlight');
			else
				$this.removeClass('selectedHighlight');
		}
	});
	$('input, textarea, select').not('#tag_select').trigger('change'); // Exclude select2

    // AttachEvent(window,'load',showLocationBox,false); // This is already present outside literal block
    if (typeof showLocationBox === "function") { // Ensure it's defined
        showLocationBox(); // Call it on document ready as well
    }
    if (typeof updateBreakBy === "function" && document.theForm.orderby) { // Ensure it's defined
        updateBreakBy(document.theForm.orderby);
    }

});
</script>
{/literal}

{include file="_std_end.tpl"}
