{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}

<h2><a href="?">Canonical Category Mapping</a> :: Rename</h2>

{dynamic}

{if $suggestion}
	<p>Use this form to suggest a renaming of canonical categories, for example to correct typos, or if think there is better wording for the canonical category.</p>
{else}
	<p>Use this form to rename recent canonical categories <b>you have suggested</b>, for example to correct typos, or if you have changed your mind about the wording of the canonical category.</p>
{/if}

<p>Can also use it to merge a canonical category with another one. Enter the name of the category merging into, in the 'New' box.


	
{if $suggestion}
	<form method="post" action="{$script_name}?rename=2">
{else}
	<form method="post" action="{$script_name}?rename=1">
{/if}
	<table class="report sortable" id="events">
	<thead><tr>
		<td>Old</td>
		<td>New</td>
		<td>Use Count</td>
	</tr></thead>
	<tbody>


	{if $list}
	{foreach from=$list item=item}
		<tr>
			<td>{$item.canonical|escape:"html"}</td>
			<td><input type="text" name="new[{$item.canonical|escape:"html"}]" value="{$item.canonical|escape:"html"}" maxlength="32"/></td>
			<td align="right">{$item.count}</td>
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>
	{if $suggestion}
		<input type=submit name="submit" value="Submit suggestion"/>
	{else}
		<input type=submit name="submit" value="Submit changes"/>
	{/if}
</form>

{/dynamic}

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
