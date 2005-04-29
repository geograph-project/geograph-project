{include file="_std_begin.tpl"}
{dynamic}

<h2>Advanced Search <small>[<a href="/search.php?i={$i}&amp;form=simple">simple form</a>]</small></h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}
<form action="/search.php" method="post" name="theForm">
	<p><b>Use the following options to customise your search</b>.<br/> </p>
		<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td colspan="3"><hr/><b>centre results on:</b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="gridref" id="l_gridref">grid reference</label></td> 
			 <td><input type="text" name="gridref" id="gridref" value="{$gridref|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg TQ 7050 or N2343</td> 
		  </tr> 
		  <tr> 
			 <td><label for="postcode" id="l_postcode">postcode</label></td> 
			 <td><input type="text" name="postcode" id="postcode" value="{$postcode|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg RH13 1BU (GB &amp; NI)</td> 
		  </tr> 
		  <tr> 
			 <td><label for="placename" id="l_placename">placename</label></td> 
			 <td><input type="text" name="placename" id="placename" value="{$placename|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg Peterborough</td> 
		  </tr> 
		  <tr> 
			 <td><label for="county_id" id="l_county_id">centre of county</label></td> 
			 <td> 
				<select name="county_id" id="county_id" size="1" class="searchinput" onchange="onlyone(this)" onblur="onlyone(this)"/> 
				  <option value=""> </option> 
					{html_options options=$countylist selected=$county_id}				  
				  
				</select></td> 
			 <td></td> 
		  </tr>
		  <tr> 
			 <td colspan="3"><b>or show: </b></td> 
		  </tr> 
		  <tr> 
			 <td><label for="textsearch" id="l_textsearch">containing text</label></td> 
			 <td><input type="text" name="textsearch" id="textsearch" value="{$textsearch|escape:'html'}" class="searchinput" onkeyup="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>eg Island</td> 
		  </tr> 
		  <tr> 
			 <td><label for="all_ind" id="l_all_ind">all images</label></td> 
			 <td><input type="checkbox" name="all_ind" id="all_ind" {$all_checked} onclick="onlyone(this)" onblur="onlyone(this)"/></td> 
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><small>
			 Once you have selected one option the others will become unavailable, to choose a different search just clear your current selection. If you don't select anything you will be shown all images.</small><br/><br/><hr/><b>you can optionally limit to results to: </b></td> 
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
			 <td>&nbsp;<input type="submit" value="Find"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3"><hr/><b>and specify how you would like the results displayed: </b></td> 
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
				<select name="resultsperpage" id="resultsperpage" size="1"> 
					{html_options values=$pagesizes output=$pagesizes selected=$resultsperpage}
				</select> <label for="resultsperpage">results per page</label></td>
			 <td>&nbsp;<input type="submit" value="Find"/></td>
			 
		  </tr> 
		</table></form>
{/dynamic}   
{literal}
<script type="text/javascript"><!--

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
	if (that.name != 'textsearch') {
		f.textsearch.disabled = isvalue;
		document.getElementById('l_textsearch').className = classname;
	}
	if (that.name != 'all_ind') {
		f.all_ind.disabled = isvalue;
		document.getElementById('l_all_ind').className = classname;
	}
	iscenter = (isvalue && (that.name == 'gridref' || that.name == 'postcode' || that.name == 'placename' || that.name == 'county_id') );
	classname = (iscenter)?'disabledLabel':'';

	f.orderby.disabled = iscenter;
	if (iscenter)
		f.orderby.selectedIndex = 2 //todo this shouldnt be hardcoded!
	else if (f.orderby.selectedIndex == 2)
		f.orderby.selectedIndex = 0;
	document.getElementById('l_orderby').className = classname;
	f.reverse_order_ind.disabled = iscenter;
	document.getElementById('l_reverse_order_ind').className = classname;
}
{/literal}
{dynamic}
{if $elementused}
	onlyone(document.theForm.{$elementused});
{/if}
{/dynamic}
//--></script>


{include file="_std_end.tpl"}
