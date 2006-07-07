{assign var="page_title" value="Special Search"}
{include file="_std_begin.tpl"}

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="/search.php" method="get" name="theForm">
	<p><b>Use the following options to customise your search</b>.<br/> </p>
		<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>Find First Geographs matching:</b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="first">Location</label></td> 
			 <td><input type="text" name="first" id="first" value="{$searchq|escape:'html'}" class="searchinput" size="6"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="font-size:0.7em">Enter either a <i>Hectad Reference</i> like <b>SH56</b> or
			 <i>Numberical Square</i> like <b>5546</b>, which will match on those numbers within any Myriad.</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>you can optionally limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="user_id">contributor</label></td> 
			 <td colspan="2"> 
				<select name="user_id" id="user_id" size="1" class="searchinput"> 
				  <option value=""> </option>
				  	{dynamic}
					{if $user->registered}
						<option value="{$user->user_id}">&nbsp; {$user->realname}</option>
						<option value=""> </option> 
					{/if}
					{/dynamic}
					{html_options options=$userlist selected=$user_id}				  
				</select> <input type="checkbox" name="user_invert_ind" id="user_invert_ind" {$user_invert_checked}/> <label for="user_invert_ind">exclude this contributor</label></td> 
		  </tr> 
		  <tr> 
			 <td><label for="moderation_status">status</label></td> 
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
			 
				<select name="imageclass" id="imageclass" size="1" class="searchinput"  onmouseover="prePopulateImageclass()" onfocus="prePopulateImageclass()"> 
					<option value=""></option>
					{if $imageclass}
						<option value="{$imageclass}" selected="selected">{$imageclass}</option>
					{/if}
					<option value="Other"></option>				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="topic_id">discuss topic</label></td> 
			 <td> 
				<select name="topic_id" id="topic_id" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$topiclist selected=$topic_id}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
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
			 <td><label for="orderby" id="l_orderby">order</label></td> 
			 <td colspan="2"> 
				<select name="orderby" id="orderby" size="1"> 
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


{include file="_std_end.tpl"}
