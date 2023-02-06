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
<legend>Create/Edit Dataset</legend>


<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="100" size="47"/>

	{if $errors.title}</div>{/if}
</div>


{if $title != 'New Dataset'}
<div class="field">
        {if $errors.url}<div class="formerror"><p class="error">{$errors.url}</p>{/if}

        <label for="url">URL:</label>
        <span class="nowrap"><small><tt>{$self_host}/features/</tt></small><input type="text" name="url" value="{$url|escape:"html"}" maxlength="64" size="40"/></span>

        <div class="fieldnotes">Shouldn't be changed once dataset published.</div>

        {if $errors.url}</div>{/if}
</div>
{/if}


<div class="field">
        {if $errors.extract}<div class="formerror"><p class="error">{$errors.extract}</p>{/if}

        <label for="extract">Short Description:</label>
        <input type="text" name="extract" value="{$extract|escape:"html"}" maxlength="255" size="90" style="width:58em"/>

        <div class="fieldnotes">Please provide a meaningful short description of the content.</div>

        {if $errors.extract}</div>{/if}
</div>


<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}

	<label for="description">Introduction:</label>
	<textarea rows="10" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea>

	<div class="fieldnotes">General introduction to the dataset, probably a few paragraphs. But there can be longer.</div>

	{if $errors.content}</div>{/if}
</div>


<div class="field">
	{if $errors.footnote}<div class="formerror"><p class="error">{$errors.footnote}</p>{/if}

	<label for="footnote">Foot Note:</label>
	<textarea rows="5" cols="80" name="footnote" style="width:58em">{$footnote|escape:"html"}</textarea>

	<div class="fieldnotes">Optional extra information, to put at the end, below the data table</div>

	{if $errors.content}</div>{/if}
</div>


<div class="field">
        {if $errors.licence}<div class="formerror"><p class="error">{$errors.licence}</p>{/if}

        <label for="licence">Licence:</label>
        <select name="licence">
        {html_options options=$licences selected=$licence}
        </select> by
        <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>

        {if $errors.licence}</div>{/if}
</div>


<div class="field">
        {if $errors.source}<div class="formerror"><p class="error">{$errors.source}</p>{/if}

	<label for="source">Data Source:</label>
	<textarea rows="4" cols="80" name="source" style="width:58em">{$content|escape:"html"}</textarea>

	<div class="fieldnotes">Where the data comes from, including any copyright notice needed. Can include URL hyperlinks</div>

        {if $errors.source}</div>{/if}
</div>


<div class="field">
	{if $errors.published}<div class="formerror"><p class="error">{$errors.published}</p>{/if}

	<label for="published">Published:</label>
	{html_select_date prefix="published" time=`$published` end_year="+10" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}

	{html_select_time prefix="published" time=`$published` use_24_hours=true}

	{if $errors.published}</div>{/if}
</div>


</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." onclick="autoDisable(this);" style="font-size:1.1em"/> {if $title == 'New Entry'}<br/>(Entries will only show on the site once they have been approved by a site moderator){/if}</p>
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
