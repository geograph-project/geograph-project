{assign var="page_title" value="Check Submissions Tool"}
{include file="_std_begin.tpl"}

<h2>Check Submissions Tool</h2>
<br/><br/>
	<div style="color:red">
	<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="15" height="15" align="left" style="margin-right:10px"/>
	NOTE: Recently this form has been locked to only search your images. Let us <a href="/contact.php">know</a> if this is an issue for you. </div>
<br/><br/>


{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="/search.php" method="get" name="theForm">
	<div class="tabHolder">
		<a href="/search.php?form=text" class="tab">advanced search</a>
		<a href="/search.php?form=simple" class="tab">simple search</a>
		<a href="/search.php?form=first" class="tab">first geographs</a>
		<span class="tabSelected">Check Submissions</span>
	</div>
	<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Find images matching:</b></td> 
		  </tr> 
		  <tr> 
			 <td>&nbsp;</td> 
			 <td>{html_checkboxes name="check" options=$checks selected=$schecks separator="<br/>"}</td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td>match</td> 
			 <td>{html_radios name="glue" options=$glues selected=$glue}</td>
			 <td>&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></td>
		  </tr> 
		  <tr> 
			 <td>options</td> 
			 <td>{html_checkboxes name="editpage_options" options=$editpage_options selected=$seditpage_options separator="&nbsp;"}</td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>and specify how you would like the results displayed: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="displayclass">format</label></td> 
			 <td> 
				{html_radios name=displayclass options=$displayclasses selected=$displayclass separator="<br/>"}
			 </td>
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="orderby" id="l_breakby">break by</label></td> 
			 <td colspan="2"> 
				<select name="breakby" id="breakby" size="1"> 
					{html_options options=$breakdowns selected=$breakby}
				</select></td> 
		  </tr>
		  <tr> 
			 <td><label for="orderby" id="l_orderby">order</label></td> 
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
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>you can optionally limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="searchtext" id="l_searchtext"><b>containing text</b></label></td> 
			 <td><input type="text" name="searchtext" id="searchtext" value="{$searchtext|escape:'html'}" class="searchinput"/><br/>
			 <small>({newwin href="/help/search" text="open <b>text search help</b>"})</small></td> 
			 <td>eg Bridge</td> 
		  </tr> 
		  <tr> 
			 <td><label for="user_name">contributor</label></td> 
			 <td colspan="2"> 
			 	<input type="text" name="user_name" id="user_name" value="{dynamic}{$user_name|escape:'html'}{/dynamic}" class="searchinput" style="width:200px"
			 	title="enter the nickname of a contributor, the full name should work too. if you know it you can enter the users ID followed by a colon"
				readonly="readonly"/>
				<div style="color:red">
				<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="15" height="15" align="left" style="margin-right:10px"/>
				NOTE: Recently this form has been locked to only search your images. Let us <a href="/contact.php">know</a> if this is an issue for you. </div>

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
		</table></form>

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

{/literal}
//--></script>

{include file="_std_end.tpl"}
