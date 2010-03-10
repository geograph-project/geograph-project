{assign var="page_title" value="Advanced Search"}
{include file="_std_begin.tpl"}
<style type="text/css">
{literal}
#maincontent form label {
	font-size:1em;
}
{/literal}
</style>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
{if $i}
	{if $fullText}
		<div class="interestBox" style="border:1px solid pink;display:none; " id="show1">
			This search was powered by the new <a href="/help/search_new">word search index</a>, which has different capabilities to the old database, so the options offered vary. 
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
<form action="/search.php?form=text" method="post" name="theForm" onsubmit="this.imageclass.disabled=false" style="background-color:#f9f9f9">
	
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
		<b>Centered Search:</b>
	</div>
	
	
		<table cellpadding="3" cellspacing="0" width="100%"> 
		  <tr> 
			 <td colspan="2" style="padding-top:8px">
				Show images within <select name="distance" id="distance" size="1" style="text-align:right"> 
				  <option value=""> </option> 
					{html_options options=$distances selected=$distance}
				</select> of <select id="selector" onchange="showLocationBox()">
				<option value="gridref"{if $gridref} selected{/if}>Grid reference</option>
				<option value="postcode"{if $postcode} selected{/if}>Postcode</option>
				<option value="placename"{if $placename} selected{/if}>Placename</option>
				<option value="county_id"{if $county_id} selected{/if}>County</option>
				</select>:</td> 
			 <td class="nowrap">&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 	
		  <tr id="tr_gridref" onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="gridref" id="l_gridref">grid reference</label></td> 
			 <td><input type="text" name="gridref" id="gridref" value="{$gridref|escape:'html'}" class="searchinput"/></td> 
			 <td>eg <tt>TQ 7050</tt> or <tt>N2343</tt></td> 
		  </tr> 
		  <tr id="tr_postcode" onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="postcode" id="l_postcode">postcode</label></td> 
			 <td><input type="text" name="postcode" id="postcode" value="{$postcode|escape:'html'}" class="searchinput"/></td> 
			 <td class="nowrap">eg <tt>RH13 1BU</tt> (GB &amp; NI)</td> 
		  </tr> 
		  <tr id="tr_placename" onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="placename" id="l_placename">placename</label></td> 
			 <td><input type="text" name="placename" id="placename" value="{$placename|escape:'html'}" class="searchinput"/></td> 
			 <td>eg <tt>Peterborough</tt></td> 
		  </tr> 
		  <tr id="tr_county_id" onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="county_id" id="l_county_id">centre of county</label></td> 
			 <td> 
				<select name="county_id" id="county_id" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$countylist selected=$county_id}
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr>
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;">and/or <b>Word Match search:</b>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>({newwin href="/article/Word-Searching-on-Geograph" text="open <b>word search help</b>"})</small></td>
		  </tr> 
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef';showMyHelpDiv('keyword',true);" onmouseout="this.style.background='#f9f9f9';showMyHelpDiv('keyword',false);"> 
			 <td><label for="searchtext" id="l_searchtext">keywords</label></td> 
			 <td><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput" size="60" style="width:400px" maxlength="250" onfocus="showMyHelpDiv('keyword',true);" onblur="showMyHelpDiv('keyword',false);"/>
			 </td> 
			 <td>eg <tt>bridge</tt></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;
				<div style="position:relative; display:none" id="keyword_help">
					<div style="position:absolute;line-height:1.1em;top:0px;left:0px; background-color:#FFFFCC;width:600px;padding:5px; border-bottom:3px solid black">
						<ul>
							<li style="padding-bottom:5px">Separate multiple keywords with spaces, all keywords are required (ie AND)</li>
							<li style="padding-bottom:5px">Only matches whole words, and Punctuation is ignored</li>
							<li style="padding-bottom:5px">Not case sensitive</li>
							<li style="padding-bottom:5px"><b>Looking for exact match?</b><br/>&nbsp; Prefix a keyword with <tt>=</tt> (otherwise <tt>bridge</tt> matches bridges, bridging etc)</small></li>
							<li style="padding-bottom:5px"><b>Currently searches</b><ul>
								<li>title, description, category, photographer name and shared description</li>
								<li>image taken date ( <tt>20071103</tt>, <tt>200711</tt>, <tt>2007</tt> or even <tt>April</tt>)</li>
								<li>subject grid-reference <span class="nowrap">( <tt>SH1234</tt>, <tt>SH13</tt> or just <tt>SH</tt> )</span></li>
							</ul><i style="font-size:0.8em">(can optionally limit matches to a particular field, see 'word search help' above)</i></li>
							<li style="padding-bottom:5px">Can match phrases [ <tt>"road bridge"</tt> ]</li>
							<li style="padding-bottom:5px">Can use OR between keywords <span class="nowrap">[ <tt>bridge OR bont OR pont</tt> ]</span></li>
							<li style="padding-bottom:5px">Can exclude words/terms [ <tt>canal -river</tt> ] or [ <tt>river -"road bridge"</tt> ]</li>
							<li style="padding-bottom:5px">Instead run a ANY search [ <tt>~bridge road river</tt> ]</li>
							<li><i>... plus more. See 'word search help' above.</i></li>
						</ul>
					</div>
				</div>
			</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;">and/or <b>Limit results to:</b></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
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
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="moderation_status">classification</label></td> 
			 <td> 
				| <input type="radio" name="moderation_status" value="" checked/>either
				| {html_radios name="moderation_status" options=$imagestatuses selected=$moderation_status separator=" | "} 
				  <input type="checkbox" name="first" value="1" {if $first}checked{/if}/>first only
			 </td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
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
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="reference_index">country</label></td> 
			 <td> 
				| <input type="radio" name="reference_index" value="" checked/>either
				| {html_radios name="reference_index" options=$references selected=$reference_index separator=" | "} 
			 </td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td><label for="gridsquare">myriad ({newwin href="/help/squares" title="What is a Myriad?" text="?"})</label></td> 
			 <td> 
				<select name="gridsquare" id="gridsquare" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$prefixes selected=$gridsquare}
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef';showMyHelpDiv('date',true);" onmouseout="this.style.background='#f9f9f9';showMyHelpDiv('date',false);"> 
			 <td><label for="submitted_startDay">submitted</label></td> 
			 <td colspan="2"> 
				between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}
				and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}
				</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef';showMyHelpDiv('date',true);" onmouseout="this.style.background='#f9f9f9';showMyHelpDiv('date',false);"> 
			 <td><label for="taken_startDay">taken</label>
			 
				<div style="position:relative; display:none" id="date_help">
					<div style="position:absolute;top:17px;left:0px; background-color:#FFFFCC;width:600px;padding:5px; border-bottom:3px solid black">
						<ul>
							<li style="padding-bottom:5px">You can just specify part of a date, for example just a year, or just month and year.</li>
							<li>Setting both the start and end date to the same value allows you to find pictures on during that period, eg 'Jan 2001' or even just 1988</li>
						</ul>
					</div>
				</div>
			 </td> 
			 <td> 
				between {html_select_date prefix="taken_start" time=$taken_start start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}
				and {html_select_date prefix="taken_end" time=$taken_end start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" all_extra=" onfocus=\"showMyHelpDiv('date',true);\" onblur=\"showMyHelpDiv('date',false);\""}
				</td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Finally...</b></td> 
		  </tr>
		  <tr> 
			 <td colspan="3" style="line-height:0.1em">&nbsp;</td> 
		  </tr> 
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#f9f9f9'"> 
			 <td colspan="3">I want to display a <select name="displayclass" id="displayclass" size="1"> 
					{html_options options=$displayclasses selected=$displayclass}
				</select> of <select name="resultsperpage" id="resultsperpage" style="text-align:right" size="1"> 
					{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
				</select> images per page,<br/> at most {newwin href="/help/one_result_per_group" title="Read more" text="one"} image from each <select name="groupby" id="groupby" size="1"> 
					{html_options options=$groupbys selected=$groupby}
				</select><sup style="color:red">new</sup>,<br/> would like a heading seperating images by <select name="breakby" id="breakby" size="1"> 
					{html_options options=$breakdowns selected=$breakby}
				</select>,<br/> and sorted in <span class="nowrap">(<input type="checkbox" name="reverse_order_ind" {$reverse_order_checked}/> reverse)
				<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);"> 
					{html_options options=$sortorders selected=$orderby}
				</select> order.</big></td> 
		  </tr> 
		  <tr> 
			 <td colspan="2">&nbsp;</td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
			 
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


function showLocationBox() {
	var ele = document.getElementById('selector');
	
	for(q=0;q<ele.options.length;q++) {
		document.getElementById('tr_'+ele.options[q].value).style.display = ele.options[q].selected?'':'none';
	}
}

 AttachEvent(window,'load',showLocationBox,false);

function showMyHelpDiv(which,show) {
	document.getElementById(which+'_help').style.display=show?'':'none';
}

{/literal}
//--></script>


{include file="_std_end.tpl"}
