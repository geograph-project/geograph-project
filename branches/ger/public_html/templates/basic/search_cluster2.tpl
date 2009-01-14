{assign var="page_title" value="Experimental Themed Images Search"}
{include file="_std_begin.tpl"}

<h2>Experimental Themed Images Search</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="/search.php" method="get" name="theForm">
	<input type="hidden" name="cluster2" value="1"/>

	<p>This special form allows you to search the 511,750 images that have have been assigned a 'theme' label, 
	only images from busy squares are included as the system needs a reasonable corpus to be able to assign a label. 
	The results pages shows the theme next to each result, and also groups image into the same theme. 
	This whole process is highly experimental and almost guaranteed to produce unexpected results, 
	please treat with caution, but please do have fun exploring images in a new way.</p>

	<table cellpadding="3" cellspacing="0"> 
		  <tr> 
		 	 <td colspan="3" style="background:#dddddd;"><b>Limit to results to: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="gridref">GridRef</label></td> 
			 <td><input type="text" name="gridref" id="gridref" value="" class="searchinput" size="6"/></td> 
			 <td></td> 
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
			 <td colspan="3">&nbsp;</td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>and specify how you would like the results displayed: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="orderby" id="l_orderby">order by</label></td> 
			 <td colspan="2"> 
				<select name="orderby" id="orderby" size="1" onchange="updateBreakBy(this);"> 
					<option value="">none</option>
					<option value="grid_reference">grid reference</option>
					<option value="label">alphabetical label</option>
					<option value="crc32(label)" selected>mixed label</option>
				</select>  </td> 
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
