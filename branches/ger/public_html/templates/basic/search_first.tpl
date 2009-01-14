{assign var="page_title" value="First Geograph Search"}
{include file="_std_begin.tpl"}

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="/search.php" method="get" name="theForm">
	<div class="tabHolder">
		<a href="/search.php?form=simple" class="tab">simple search</a>
		<a href="/search.php?form=text" class="tab">advanced search</a>
		<span class="tabSelected">First Geographs</span>
	</div>
	<div class="interestBox">
		<b>Find First Geographs matching:</b>
	</div>
	
	<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><label for="first">Location</label></td> 
			 <td><input type="text" name="first" id="first" value="%" class="searchinput" size="6"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="font-size:0.7em">Enter either<br/> A) <i>Hectad Reference</i> like <b>SH56</b>, to find images within that Hectad<a href="/help/squares">?</a><br/> B)
			 <i>Numerical Square</i>{if $enable_forums && $forum_topic_numsquare >= 0}<a href="/discuss/index.php?action=vthread&amp;forum={$forum_generaldiscussion}&amp;topic={$forum_numsquare}">?</a>{/if} like <b>5546</b>, which matches those numbers in any Myriad or<br/> C) <i>4fig GR - Wildcard Search</i> like <b>SH6_56</b> where _ matches any digit, can also use % to match zero or more characters (useful for matching GB or Irish Grids).<br/> D) <i>Hectad - Wildcard Search</i> like <b>SH6_</b> where _ matches any digit</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>you can optionally limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="searchtext" id="l_searchtext"><b>containing phrase</b></label></td> 
			 <td><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput"/> (this is the old non-keyword search)<br/>
			 <small>({newwin href="/help/search" text="open <b>text search help</b>"})</small></td> 
			 <td>eg Bridge</td> 
		  </tr> 
		  <tr> 
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
		  <tr> 
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
{if $enable_forums}
		  <tr> 
			 <td><label for="topic_id">discuss topic</label></td> 
			 <td> 
				<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$topiclist selected=$topic_id}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
{/if}
		  <tr> 
			 <td><label for="reference_index">country</label></td> 
			 <td> 
				<select name="reference_index" id="reference_index" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$references selected=$reference_index} 
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="gridsquare">myriad ({newwin href="/help/squares" title="What is a Myriad?" text="?"})</label></td> 
			 <td> 
				<select name="gridsquare" id="gridsquare" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$prefixes selected=$gridsquare}
				</select></td> 
			 <td>&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td><label for="submitted_startDay">submitted</label></td> 
			 <td colspan="2"> 
				between {html_select_date prefix="submitted_start" time=$submitted_start start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				and {html_select_date prefix="submitted_end" time=$submitted_end start_year="2005" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</td> 
		  </tr> 
		  <tr> 
			 <td><label for="taken_startDay">taken</label></td> 
			 <td colspan="2"> 
				between {html_select_date prefix="taken_start" time=$taken_start start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				and {html_select_date prefix="taken_end" time=$taken_end start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
				</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><small>
			 You can just specify part of a date, for example just a year, or just month and year. Setting both the start and end date to the same value allows you to find pictures on during that period, eg 'Jan 2001' or even just 1988. If you select just a month, then we will find just images taken during that month.</small>
			 </td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>and specify how you would like the results displayed: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="displayclass">format</label></td> 
			 <td> 
				<select name="displayclass" id="displayclass" size="1"> 
					{html_options options=$displayclasses selected=$displayclass}
				</select></td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="orderby" id="l_breakby">group by</label></td> 
			 <td colspan="2"> 
				<select name="breakby" id="breakby" size="1"> 
					{html_options options=$breakdowns selected=$breakby}
				</select> then...</td> 
		  </tr>
		  <tr> 
			 <td><label for="orderby" id="l_orderby">order by</label></td> 
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
		enabled = (name.length && ele.options[q].value.indexOf(name) == 0) || name.length == 0;
		ele.options[q].style.color = enabled?'':'#999999';
		if (ele.options[q].selected && !enabled)
			ele.selectedIndex = 0;
	}
}

updateBreakBy(document.theForm.orderby);
{/literal}
//--></script>


{include file="_std_end.tpl"}
