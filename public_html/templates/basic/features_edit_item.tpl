{dynamic}
{assign var="page_title" value="Edit::$title"}

{if $inner}
	{include file="_basic_begin.tpl"}
{else}
	{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript">{literal}
function unloadMess() {
	var ele = document.forms['theForm'].elements['content'];
	if (ele.value == ele.defaultValue) {
		return;
	}
	return "**************************\n\nYou have unsaved changes in the content box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;

function cancelMess() {
	window.onbeforeunload=null;
}
function setupSubmitForm() {
	AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


{/literal}</script>

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}?type_id={$item.feature_type_id}{if $inner}&amp;inner=1{/if}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>


<fieldset>
<legend>Create/Edit Item</legend>

{foreach from=$columns key=name item=value}
	<div class="field">
		{if $errors.$name}<div class="formerror"><p class="error">{$errors.$name}</p>{/if}

		<label for="{$name}">{$name}:</label>
		{if $name == 'nearby_images'}
			<input type="text" disabled value="{$item.$name|escape:"html"}" size=5>(Automatically calculated)
		{elseif $name == 'sorter' || $name == 'radius' || $name == 'gridimage_id'}
			<input type="number" name="{$name}" value="{$item.$name|escape:"html"}" style="font-size:1.1em" size="10"/> (number only)
		{else}
			<input type="text" name="{$name}" value="{$item.$name|escape:"html"}" style="font-size:1.1em" maxlength="128" size="47"/>
		{/if}

		{if $errors.$name}</div>{/if}
	</div>
{/foreach}


</fieldset>

{if $inner} 
	<br><br><br><br>
	<div style="position:fixed;background-color:silver;bottom:0;left:0;width:100%">
{else}
	<div>
{/if}

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." onclick="autoDisable(this);"/>
{if $inner}
	<a href="javascript:void(parent.closePopup());">Close without saving</a>
{/if}

</div>

</form>


{if $inner}
	</body>
	</html>
{else}
	{include file="_std_end.tpl"}
{/if}

{/dynamic}
