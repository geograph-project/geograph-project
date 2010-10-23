{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}

<h2><a href="?">Canonical Category Mapping</a> :: Rename</h2>

<p>Use this form to rename recent canonical categories <b>you have suggested</b>, for example to correct typos, or if you have changed your mind about the wording of the canonical category.</p>

{dynamic}

	
<form method="post" action="{$script_name}?rename=1">
	<table class="report sortable" id="events">
	<thead><tr>
		<td>Old</td>
		<td>New</td>

	</tr></thead>
	<tbody>


	{if $list}
	{foreach from=$list item=item}
		<tr>
			<td>{$item.canonical|escape:"html"}</td>
			<td><input type="text" name="new[{$item.canonical|escape:"html"}]" value="{$item.canonical|escape:"html"}" maxlength="32"/></td>
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>
	<input type=submit name="submit" value="Submit changes"/>
</form>

{/dynamic}

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
