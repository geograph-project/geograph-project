{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}" method="post">

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

	<div class="fieldnotes">Optional, Relevent Square for the entry </div>

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

	<label for="description">Content:</label>
	<textarea rows="10" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea></p>

	<div class="fieldnotes">Blog entry text </div>

	{if $errors.extract}</div>{/if}
</div>

<div class="field">
	{if $errors.tags}<div class="formerror"><p class="error">{$errors.tags}</p>{/if}

	<label for="title">Tags:</label>
	<input type="text" name="tags" value="{$tags|escape:"html"}" style="font-size:1.1em" maxlength="255" size="47"/>

	<div class="fieldnotes">Optional tags for this entry. Seperate multiple tags with commas</div>

	{if $errors.title}</div>{/if}
</div>

</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> {if $title == 'New Entry'}<br/>(Entries will only show on the site once they have been approved by a site moderator){/if}</p>
</form>



{include file="_std_end.tpl"}
{/dynamic}
