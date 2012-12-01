{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}


{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>



<fieldset>
<legend>Edit links on Project :: {$title|escape:'html'}</legend>

<table id="thetable">
<tr>
	<th>Title (optional)</th>
	<th>Link</th>
</tr>
{foreach from=$links item=link}
	<tr id="row{$link.project_link_id}">
		<td><input type="text" name="title[{$link.project_link_id}]" value="{$link.title|escape:'html'}" size="40" placeholder="title (optional)" /></td>
		<td><input type="text" name="link[{$link.project_link_id}]" value="{$link.link|escape:'html'}" size="60" placeholder="http://...." /></td>
		<td><input type="button" value="delete" onclick="delete_row(this.form, {$link.project_link_id})" /></td>
	</tr>
{/foreach}
<tr id="lastrow">
	<td><input type="text" name="title[]" size="40" placeholder="title (optional)" /></td>
	<td><input type="text" name="link[]" size="60" placeholder="http://...." /></td>
</tr>
</table>
<input type="button" id="addnew" value="add another"/>

</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/></p>
</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>{literal}

var i = 1;
$("#addnew").click(function() {
  $("#lastrow").clone().find("input").each(function() {
    $(this).val('').attr('id', function(_, id) { return id + i });
  }).end().appendTo("#thetable");
  i++;
});

function delete_row(form, idx) {
	$('#row'+idx).hide();
	form.elements['link['+idx+']'].value = '-deleted-';
}

{/literal}</script>

{include file="_std_end.tpl"}
{/dynamic}
