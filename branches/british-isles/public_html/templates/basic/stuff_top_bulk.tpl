{assign var="page_title" value="Geographical Context"}
{include file="_std_begin.tpl"}

<h2><a href="?">Geographical Context Category Mapping</a> :: Bulk Submit</h2>

<form method="post" action="{$script_name}?bulk=1">
<p>Apply <select name="top"><option value="">--- PLEASE CHOOSE ---</option><option value="-bad-">* Unallocated</option>
			{foreach from=$list item=i}<option{if $i.count < 3} style="color:gray"{/if}>{$i.top|escape:'html'}</option>{/foreach}
			</select> to ALL the ticked categories:</p>

{dynamic}

{literal}<script type="text/javascript">
function tickall(form,result) {
	var ele = form['elements'];
	for (q=0;q<ele.length;q++) 
		if (ele[q].length && ele[q].length > 1) {
			for(i=0;i<ele[q].length;i++)
				if (ele[q][i].tagName.toLowerCase() == 'input' && ele[q][i].type.toLowerCase() == 'checkbox')
					ele[q][i].checked = result;
		} else
			if (ele[q].tagName.toLowerCase() == 'input' && ele[q].type.toLowerCase() == 'checkbox')
				ele[q].checked = result;
}
</script>{/literal}
	<input type="button" value="Tick All" onclick="tickall(this.form,true);" />
	<input type="button" value="Untick All" onclick="tickall(this.form,false);" />

	<table class="report sortable" id="events">
	<thead><tr>
		<td>Main category</td>
		<td>Apply</td>

	</tr></thead>
	<tbody>


	{if $list}
	{foreach from=$rows item=item}
		<tr>
			<td>{$item.imageclass|escape:"html"}</td>
			<td><input type="checkbox" name="imageclass[]" value="{$item.imageclass|escape:"html"}" checked /></td>
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>

<input type=submit onclick="{literal}if (this.form.elements['top'].value.length > 0) { return confirm('Please confirm you have checked each and EVERY category in the list above matches ['+this.form.elements['top'].value+']...'); } else { alert('Please select a Geographical Context category'); return false; }{/literal}" />
</form>

{/dynamic}

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
