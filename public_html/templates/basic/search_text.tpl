{assign var="page_title" value="Advanced Search"}
{include file="_std_begin.tpl"}


{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
{if $i}
	{if $fullText}
		<div class="interestBox" style="border:1px solid pink;display:none; " id="show1">
			This search was powered by the new <a href="/help/search_new">word search index</a>, in particular this is less flexible than the old search so this page is reduced. 
			{if !$engine->criteria->sphinx.no_legacy}
				You can access the <a href="/search.php?i={$i}&amp;form=advanced&amp;legacy=true">old advanced form here</a>.
			{/if}
			<br/><br/>
			<a href="javascript:void(hide_tree(1));">close</a>
		</div>

		<div class="interestBox" style="border:1px solid pink; float:right; width:200px; position:relative; " id="hide1"><b>Not seeing the page you expect?</b>
		<a href="javascript:void(show_tree(1));">expand...</a>

		</div>
	{/if}

<h2>Advanced Photo Search</h2>


<p>Original Search: <tt>images{$searchdesc|escape:"html"}</tt></p>

{else}
	<h2>Photograph Search</h2>
{/if}
<form action="/search.php?form=text" method="post" name="theForm" onsubmit="this.imageclass.disabled=false">
	
	<div class="tabHolder">
		<a href="/search.php?form=simple" class="tab">simple search</a>
		<span class="tabSelected">Advanced Search</span>
		{dynamic}
		{if $user->registered}
		<a href="/search.php?form=advanced&amp;legacy=true" class="tab"><small>old advanced</small></a>
		{/if}
		{/dynamic}
		<a href="/search.php?form=first" class="tab">first geographs</a>
	</div>
	<div class="interestBox">
		<div style="text-align:right">({newwin href="/article/Searching-on-Geograph" text="More information on the Search Engine"})</div>
		<b>Centered Search:</b> (choose one)
	</div>
	
	
		<table cellpadding="3" cellspacing="0"> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="gridref" id="l_gridref">grid reference</label></td> 
			 <td><input type="text" name="gridref" id="gridref" value="{$gridref|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg <tt>TQ 7050</tt> or <tt>N2343</tt></td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="postcode" id="l_postcode">postcode</label></td> 
			 <td><input type="text" name="postcode" id="postcode" value="{$postcode|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td class="nowrap">eg <tt>RH13 1BU</tt> (GB &amp; NI)</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="placename" id="l_placename">placename</label></td> 
			 <td><input type="text" name="placename" id="placename" value="{$placename|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg <tt>Peterborough</tt></td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="county_id" id="l_county_id">centre of county</label></td> 
			 <td> 
				<select name="county_id" id="county_id" size="1" class="searchinput" onchange="onlyone(this)" onblur="onlyone(this)"/> 
				  <option value=""> </option> 
					{html_options options=$countylist selected=$county_id}				  
				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr>
		  <tr> 
			 <td colspan="3"><small><small>
			 Once you have selected one option the others will become unavailable, to choose a different search just clear your current selection. If you don't select anything you will be shown all images (matching filters below).</small></small>
			 </td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="distance" id="l_distance">distance to above</label></td> 
			 <td> 
				<select name="distance" id="distance" size="1" style="text-align:right" onchange="onlyone_part2(this.form)" onblur="onlyone_part2(this.form)"> 
				  <option value=""> </option> 
					{html_options options=$distances selected=$distance}
				</select></td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;">and/or <b>Word Match search:</b>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>({newwin href="/article/Word-Searching-on-Geograph" text="open <b>word search help</b>"})</small></td>
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="searchtext" id="l_searchtext">keywords</label></td> 
			 <td><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput" size="60" style="width:400px" maxlength="250"/>
			 </td> 
			 <td>eg <tt>bridge</tt></td> 
		  </tr> 
		  <tr> 
			 <td colspan="2"><small>&middot; <b>Looking for exact match?</b> Prefix a keyword with <tt>=</tt>
			 (otherwise <tt>bridge</tt> matches bridges, bridging etc)</small></td> 
			 <td>eg <tt>road =bridge</tt></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><small>&middot; Currently searches the title, description, category, photographer name and image taken date (<tt>20071103</tt>, <tt>200711</tt> or just <tt>2007</tt>) fields, as well the subject grid-reference <span class="nowrap">(<tt>SH1234</tt>, <tt>SH13</tt> or just <tt>SH</tt>)</span>, separate multiple keywords with spaces.</small></td> 
		  </tr> 
		  <tr> 
			 <td colspan="2"><small>
			 &middot; Only matches whole words, punctuation is ignored, can match phrases [ <tt>"road bridge"</tt> ], allows the OR keyword <span class="nowrap">[ <tt>bridge OR bont OR pont</tt> ]</span>, can exclude words [ <tt>-river</tt> ], and is not case sensitive.</small><br/>
			 <br/></td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>Optionally limit to results to: </b></td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="user_name">contributor</label></td> 
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
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="moderation_status">classification</label></td> 
			 <td> 
				<select name="moderation_status" id="moderation_status" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$imagestatuses selected=$moderation_status}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="imageclass">category</label></td> 
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
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="reference_index">country</label></td> 
			 <td> 
				<select name="reference_index" id="reference_index" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$references selected=$reference_index} 
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="gridsquare">myriad ({newwin href="/help/squares" title="What is a Myriad?" text="?"})</label></td> 
			 <td> 
				<select name="gridsquare" id="gridsquare" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$prefixes selected=$gridsquare}
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="submitted_startDay">submitted</label></td> 
			 <td colspan="2"> 
				between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="taken_startDay">taken</label></td> 
			 <td> 
				between {html_select_date prefix="taken_start" time=$taken_start start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				and {html_select_date prefix="taken_end" time=$taken_end start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><small>
			 You can just specify part of a date, for example just a year, or just month and year. Setting both the start and end date to the same value allows you to find pictures on during that period, eg 'Jan 2001' or even just 1988.</small>
			 </td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Specify how you would like the results displayed: </b></td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="displayclass">format</label></td> 
			 <td> 
				<select name="displayclass" id="displayclass" size="1"> 
					{html_options options=$displayclasses selected=$displayclass}
				</select></td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="breakby" id="l_breakby">group by</label></td> 
			 <td colspan="2"> 
				<select name="breakby" id="breakby" size="1"> 
					{html_options options=$breakdowns selected=$breakby}
				</select> then...</td> 
		  </tr>
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td><label for="orderby" id="l_orderby">order by</label></td> 
			 <td colspan="2"> 
				<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);"> 
					{html_options options=$sortorders selected=$orderby}
				</select> <input type="checkbox" name="reverse_order_ind" id="reverse_order_ind" {$reverse_order_checked}/> <label for="reverse_order_ind" id="l_reverse_order_ind">reverse order</label></td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'"> 
			 <td>&nbsp;</td> 
			 <td> {dynamic}
				<select name="resultsperpage" id="resultsperpage" style="text-align:right" size="1"> 
					{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
				</select> <label for="resultsperpage">results per page</label>{/dynamic}</td>
			 <td>&nbsp;<input type="submit" value="Find" style="font-size:1.3em"/></td>
			 
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
		enabled = (name.length && ele.options[q].value.indexOf(name) == 0) || name.length == 0;
		ele.options[q].style.color = enabled?'':'#999999';
		if (ele.options[q].selected && !enabled)
			ele.selectedIndex = 0;
	}
}


var isvalue;
var iscenter = false;

function onlyone(that) {
	if (that.name == 'county_id') {
		isvalue = (that.selectedIndex > 0)?true:false;
	} else if (that.name == 'all_ind') {
		isvalue = that.checked;
	} else {
		isvalue = (that.value.length > 0)?true:false;
	}
	classname = (isvalue)?'disabledLabel':'';
	f = that.form;
	if (that.name != 'gridref') {
		f.gridref.disabled = isvalue;
		document.getElementById('l_gridref').className = classname;
	}

	if (that.name != 'postcode') {
		f.postcode.disabled = isvalue;
		document.getElementById('l_postcode').className = classname;
	}
	if (that.name != 'placename') {
		f.placename.disabled = isvalue;
		document.getElementById('l_placename').className = classname;
	}
	if (that.name != 'county_id') {
		f.county_id.disabled = isvalue;
		document.getElementById('l_county_id').className = classname;
	}
	iscenter = (isvalue && (that.name == 'gridref' || that.name == 'postcode' || that.name == 'placename' || that.name == 'county_id') );
	
	onlyone_part2(f);
}
	
function onlyone_part2(f) {
	
	classname = (iscenter)?'disabledLabel':'';

	f.distance.disabled = !iscenter;
	document.getElementById('l_distance').className = (iscenter)?'':'disabledLabel';

	if (f.distance.selectedIndex > 0) {
		f.orderby.disabled = false;
		document.getElementById('l_orderby').className = '';
		f.reverse_order_ind.disabled = false;
		document.getElementById('l_reverse_order_ind').className = '';
				
		f.orderby.options[1].className = '';
	} else {
		f.orderby.disabled = iscenter;
		if (iscenter)
			f.orderby.selectedIndex = 1 //todo this shouldnt be hardcoded!
		else if (f.orderby.selectedIndex == 1)
			f.orderby.selectedIndex = 0;
		document.getElementById('l_orderby').className = classname;
		
		f.reverse_order_ind.disabled = iscenter;
		document.getElementById('l_reverse_order_ind').className = classname;

		f.orderby.options[1].className = classname;
	}
	
}


onlyone_part2(document.theForm);

{/literal}

{if $elementused}
	onlyone(document.theForm.{$elementused});
{/if}

//--></script>


{include file="_std_end.tpl"}
