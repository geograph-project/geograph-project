{assign var="page_title" value="Add Word to Regional Glossery"}
{include file="_std_begin.tpl"}

<h2>Add Word to Regional Glossary</h2>


	<p style="font-size:0.8em">Add the details of the word below...</p>
	
<form action="{$script_name}" method="post">

<table>

<tr>
	<th>Regional Word:</th>
	<td><input type="text" name="source_word" size="40" maxlength="64"/></td>
	<td>eg 'lect'</td>
</tr>
<tr>
	<th>Language:</th>
	<td><select name="source_lang">
	<option value="">... choose ...</option>
	<option value="ga">Irish</option>
	<option value="cy">Welsh</option>
	<option value="gd">Scotish</option>
	<option value="fr">French</option>
	<option value="gb">English: British</option>
	<option value="us">English: American</option>
	<option value="ot">Other... (please let us know)</option>
	</select></td>
</tr>
<tr>
	<th>Dialect (optional):</th>
	<td><input type="text" name="source_dialect" size="30" maxlength="30"/></td>
	<td>eg 'Cornish'</td>
</tr>

<tr>
	<th>&nbsp;</th>
</tr>

<tr>
	<th>General Usage:<br/><small>can be 1 or 2 words</small></th>
	<td><input type="text" name="tran_word" size="40" maxlength="64"/></td>
	<td>eg 'mill stream'</td>
</tr>
<tr>
	<th>Language:</th>
	<td><select name="tran_lang">
	<option value="">... choose ...</option>
	<option value="gb" selected="selected">English: British</option>
	<option value="us">English: American</option>
	<option value="ga">Irish</option>
	<option value="cy">Welsh</option>
	<option value="gd">Scotish</option>
	<option value="fr">French</option>
	<option value="ot">Other... (please let us know)</option>
	</select></td>
</tr>
<tr>
	<th>Dialect (optional):</th>
	<td><input type="text" name="tran_dialect" size="30" value="Standard" maxlength="30"/></td>
	<td>eg 'Standard'</td>
</tr>
<tr>
	<th>Definition (optional):</th>
	<td><textarea name="tran_definition" rows="3" cols="40" wordwrap="none"></textarea></td>
	
</tr>

</table>

<input type="submit" name="add" value="Add Word" onclick="return validate_form(this.form)"/>

</form>


{literal}

<script language="JavaScript">
function validate_form(form) {
	var msg ='';
	var ele;

	if (form.tran_lang.selectedIndex == 0) {
		msg = "Please select General Word Language\n" + msg;
		ele = form.tran_lang;
	}

	if (form.tran_word.value.length == 0) {
		msg = "Please enter General Word\n" + msg;
		ele = form.tran_word;
	}
	
	if (form.source_lang.selectedIndex == 0) {
		msg = "Please select Regional Word Language\n" + msg;
		ele = form.source_lang;
	}

	if (form.source_word.value.length == 0) {
		msg = "Please enter Regional Word\n" + msg;
		ele = form.source_word;
	}


	if (msg != '') {
		alert(msg);
		ele.focus();
		return false;
	}
	return true;
}
</script>

{/literal}


{include file="_std_end.tpl"}
