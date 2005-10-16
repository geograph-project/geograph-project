{include file="_std_begin.tpl"}

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="search.php" method="post" name="theForm">
	<p><b>Use the following options to customise your search</b>.<br/> </p>
		<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>search details:</b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="description">description</label></td> 
			 <td>images, <input type="text" name="description" id="description" value="{$description|escape:'html'}" class="searchinput"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td><label for="searchq">sql where</label></td> 
			 <td>and <input type="text" name="searchq" id="searchq" value="{$searchq|escape:'html'}" class="searchinput"/></td> 
			 <td></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>optionally centre results on:</b></td> 
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
		 	 <td colspan="3" style="background:#dddddd;"><b>you can optionally limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="distance" id="l_distance">distance to above</label></td> 
			 <td> 
				<select name="distance" id="distance" size="1" style="text-align:right" onchange="onlyone_part2(this.form)" onblur="onlyone_part2(this.form)"> 
				  <option value=""> </option> 
					{html_options values=$distances output=$distances selected=$distance}
				</select>km</td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="user_id">contributor</label></td> 
			 <td colspan="2"> 
				<select name="user_id" id="user_id" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{if $user->registered}
						<option value="{$user->user_id}">&nbsp; {$user->realname}</option>
						<option value=""> </option> 
					{/if}
					{html_options options=$userlist selected=$user_id}				  
				</select> <input type="checkbox" name="user_invert_ind" id="user_invert_ind" {$user_invert_checked}/> <label for="user_invert_ind">exclude this contributor</label></td> 
		  </tr> 
		  <tr> 
			 <td><label for="moduration_status">status</label></td> 
			 <td> 
				<select name="moduration_status" id="moduration_status" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$imagestatuses selected=$moduration_status}				  
				</select></td> 
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="imageclass">category</label></td> 
			 <td> 
				<select name="imageclass" id="imageclass" size="1" class="searchinput"> 
				  <option value=""> </option> 
					{html_options options=$imageclasslist selected=$imageclass}				  
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
			 <td>&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td><label for="gridsquare">grid square</label></td> 
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
			 <td><label for="orderby" id="l_orderby">order</label></td> 
			 <td colspan="2"> 
				<select name="orderby" id="orderby" size="1"> 
					{html_options options=$sortorders selected=$orderby}
				</select> <input type="checkbox" name="reverse_order_ind" id="reverse_order_ind" {$reverse_order_checked}/> <label for="reverse_order_ind" id="l_reverse_order_ind">reverse order</label></td> 
		  </tr> 
		  <tr> 
			 <td>&nbsp;</td> 
			 <td> 
				<select name="resultsperpage" id="resultsperpage" style="text-align:right" size="1"> 
					{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
				</select> <label for="resultsperpage">results per page</label></td>
			 <td>&nbsp;<input type="submit" name="submit" value="Count"/> <input type="submit" value="Find"/></td>
			 
		  </tr> 
		</table></form>


{include file="_std_end.tpl"}
