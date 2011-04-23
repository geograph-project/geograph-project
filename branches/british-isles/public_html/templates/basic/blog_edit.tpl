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
<legend>Create/Edit Blog Entry</legend>





<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="100" size="47"/>

	{if $errors.title}</div>{/if}
</div>





<div class="field">
	{if $errors.published}<div class="formerror"><p class="error">{$errors.published}</p>{/if}

	<label for="published">Published:</label>
	{html_select_date prefix="published" time=`$published` end_year="+10" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}

	{html_select_time prefix="published" time=`$published` use_24_hours=true}

	{if $errors.published}</div>{/if}
</div>

<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

	<label for="grid_reference">Grid Square:</label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="6"/>

	<div class="fieldnotes">Optional, enter a 4-figure grid reference for the entry </div>

	{if $errors.grid_reference}</div>{/if}
</div>

<div class="field">
	{if $errors.gridimage_id}<div class="formerror"><p class="error">{$errors.gridimage_id}</p>{/if}

	<label for="gridimage_id">Relevant Image ID:</label>
	<input type="text" name="gridimage_id" value="{$gridimage_id|escape:"html"}" maxlength="12" size="6"/>

	<div class="fieldnotes">Optional, enter a image id to illustrate this blog entry</div>

	{if $errors.gridimage_id}</div>{/if}
</div>

<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}

	<label for="description">Blog Entry:</label>
	<textarea rows="10" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea></p>

	{if $errors.extract}</div>{/if}
</div>

<div class="field">
	{if $errors.tags}<div class="formerror"><p class="error">{$errors.tags}</p>{/if}

	<label for="tags">Tags:</label>
	<input type="text" name="tags" value="{$tags|escape:"html"}" id="tags" maxlength="255" size="60"/>

	<div class="fieldnotes">Optional tag(s) for this entry, describing what it's about. Separate multiple tags with commas.<br/><br/>

	Suggestions (click to use): <i>But feel free to use your own!</i><br/><a href="javascript:void()" onclick="return useit(this.innerText)">Introducing Myself</a>, <a href="javascript:void()" onclick="return useit(this.innerText)">Geographing Trip Report</a>, <a href="javascript:void()" onclick="return useit(this.innerText)">Interesting Image</a>, <a href="javascript:void()" onclick="return useit(this.innerText)">Completing a Hectad</a>, <a href="javascript:void()" onclick="return useit(this.innerText)">Off-Topic Ramblings</a>.</div>

	{if $errors.title}</div>{/if}
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
