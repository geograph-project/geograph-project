{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}
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

<form class="simpleform" action="{$script_name}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>



<fieldset>
<legend>Create/Edit Idea</legend>





<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="100" size="47"/>

	{if $errors.title}</div>{/if}
</div>


<div class="field">
        {if $errors.type}<div class="formerror"><p class="error">{$errors.type}</p>{/if}

        <label for="type">Type:</label>

        <select name="type">
                                <option value="feature"{if $type eq 'feature'} selected{/if}>Brand New Feature</option>
                                <option value="extension"{if $type eq 'extension'} selected{/if}>Extension to existing Feature</option>
                                <option value="bugfix"{if $type eq 'bugfix'} selected{/if}>Fix a bug in existing Feature</option>
        </select>

        {if $errors.type}</div>{/if}
</div>

<div class="field">
        {if $errors.type}<div class="formerror"><p class="error">{$errors.status}</p>{/if}

        <label for="status">Status:</label>

        <select name="status">
                                <option value="invalid"{if $status eq 'invalid'} selected{/if}>Invalid</option>
                                <option value="new"{if $status eq 'new' || !$status} selected{/if}>New</option>
                                <option value="inprogress"{if $status eq 'inprogress'} selected{/if}>In Progress</option>
                                <option value="complete"{if $status eq 'complete'} selected{/if}>Complete</option>
        </select>

        {if $errors.type}</div>{/if}
</div>


<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}

	<label for="content">Main Description:</label>
	<textarea rows="10" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea>

	{if $errors.content}</div>{/if}
</div>


</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> {if $title == 'New Entry'}<br/>(Entries will only show on the site once they have been approved by a site moderator){/if}</p>
</form>

<script type="text/javascript">{literal}
function useit(text) {
	var ele = document.getElementById('tags');
	if (ele.value.length>0) {
		ele.value = ele.value + ", ";
	}
	ele.value = ele.value + text;
	return false;
}
{/literal}</script>

{include file="_std_end.tpl"}
{/dynamic}
