{assign var="page_title" value="Refine Search"}
{include file="_std_begin.tpl"}

<h2>Refine Search</h2>
{dynamic}

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}

{if $token}
	<p>Your search has been updated, <a href="/search.php?i={$i}&amp;t={$token}">continue to results</a></p>
{else}

{if $i}
<p>Search for images<i>{$searchdesc|escape:"html"}</i> with {$count} results</p>
{/if}


{if $user->user_id != $criteria->user_id}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	<b>This is not your search</b>, as a moderator you can edit the search, but please only use this facility with good reason.<br/>
	</div>
<br/>

{/if}

<form action="/refine.php?i={$i}" method="post" name="theForm">

		<table cellpadding="3" cellspacing="0"> 
		  <tr> 
			 <td><label for="searchdesc">Title</label></td> 
			 <td colspan="2" style="font-size:1.1em;">images<input type="text" name="searchdesc" id="searchdesc" value="{$searchdesc|escape:'html'}" class="searchinput" size="80" style="font-size:1.1em; width:640px;" maxlength="255"/></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>import more images: </b></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">coming soon...</td> 
		  </tr> 
		  <!--tr> 
			 <td><label for="pastebox">paste</label></td> 
			 <td colspan="2"> Paste your list here, either comma or space seperated or just surrounded with [[[ ]]] <br/>
				
				<script type="text/javascript">{literal}
				current = readCookie('markedImages')
				if (current && current != '') {
				splited = current.commatrim().split(',');
				document.write('<div style="border: 1px solid lightgrey;">Marked Images['+(splited.length+0)+']: <a title="Insert marked image list" href="#" onclick="document.getElementById(\'pastebox\').value += \' \'+returnMarkedImages(); return false;" onMouseOver="window.status=\'Insert marked image list\'; return true" onMouseOut="window.status=\'\'; return true"><b>Insert into Box</b></a> (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear</a>)</div>');
				}{/literal}
				</script>
				<textarea name="pastebox" id="pastebox" rows="6" cols="80" style="font-size:0.9em"></textarea>
			 </td>
		  </tr> 
		  <tr> 
			 <td><label for="fromsearch">from Search</label></td> 
			 <td>identifier: <input type="text" name="fromsearch" id="fromsearch" value="" size="8" maxlength="12"/><br/><small> (enter the number from the 'i' parameter displayed on search result URL)</small></td> 
			 <td style="color:red">note: only first 500 results will be imported via this method</td> 
		  </tr--> 
		
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>reorder/remove current images: </b></td> 
		  </tr> 
		  <tr> 
			 <td colspan="3">coming soon...</td> 
		  </tr> 
		  <!--tr> 
			 <td>&nbsp;</td> 
			 <td><a href="/refine.php?i={$i}&amp;popup">Reorganise current images</a> (opens in a new window)</td>
			 <td>&nbsp;</td> 
		  </tr--> 
		
		  <tr> 
			 <td colspan="3" style="background:#dddddd;"><b>specify how you would like the results displayed: </b></td> 
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
			 <td>&nbsp;<input type="submit" name="submit" value="Save"/></td>
			 
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


{/literal}

//--></script>
{/if}
{/dynamic}
{include file="_std_end.tpl"}
