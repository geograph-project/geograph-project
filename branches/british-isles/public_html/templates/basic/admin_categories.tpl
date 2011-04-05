{assign var="page_title" value="Category Consolidation"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Category Consolidation</h2>
<p>Use this page to correct and consolidate the user submitted image 'categories'. 
Use each text box to rename the categories.
You can merge multiple categories by setting them all to the new name.
Changed values are highlighted in grey. </p>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    
   

	<form action="{$script_name}" method="post">
	<p>Filter: <input type=text name=q value="{$q}"> (separate words with spaces) </p>
	<hr/>
	
	<p align=center>Change selected box to <select name="list" onchange="onc(this)">
	<option></option>
	
	{foreach key=val item=count from=$arr}
		<option value="{$val}">{$val} [{$count}]</option>
	{/foreach}
	</select></p>
	
	<table>
	<tr><th>Old Value</th><th>Count</th><th>New Value</th></tr>
	{foreach key=val item=count from=$arr name=loop}
		<tr>
		<td>{if $val}
		<a href="/search.php?imageclass={$val|escape:url}" target="_blank">{$val}</a>
		    {else}
		    	-blank-
		    {/if}</td>
		<td align=right><b>{$count}</b></td>
		{if $count > 0}
			<td><input type=hidden name="old{$smarty.foreach.loop.iteration}" value="{$val}">
			<input type=text name="new{$smarty.foreach.loop.iteration}" size=45 value="{$val}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration})">
			<input type=button value="Reset" onclick="oncl(this,{$smarty.foreach.loop.iteration})"></td>
		{/if}
		</tr>
	{/foreach}
	</table>
	<input type=hidden name=highc value="{$smarty.foreach.loop.total}">
	<input type=submit name=submit value="Commit Changes">
	</form>   
   
    
   {literal} 
<script>
var selectedItem;

function onf(that) {
	selectedItem = that;
	that.style.backgroundColor = 'yellow';
	that.form.list.selectedIndex = 0;
}

function onb(that,num) {
	selectedItem.style.backgroundColor = (that.form['old'+num].value == that.value)?'':'lightgrey';
	that.form.list.selectedIndex = 0;

}

function oncl(that,num) {
	that.form['new'+num].value = that.form['old'+num].value;
	that.form['new'+num].style.backgroundColor = '';

}

function onc(that) {
	selectedItem.value = that.options[that.selectedIndex].value;
	selectedItem.focus();
}
</script>
{/literal}
<p>Warning: Be careful using this page to swap categories, 
it can cope will two way swap, but three ways swaps will probably get confused</p>

{/dynamic}    
{include file="_std_end.tpl"}
