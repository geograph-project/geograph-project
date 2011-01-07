{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}" method="post">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>

 
<fieldset>
<legend>Create/Edit Event</legend>





<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}
	 
	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	{if $errors.title}</div>{/if}
</div>

<div class="field">
	{if $errors.url}<div class="formerror"><p class="error">{$errors.url}</p>{/if}
	 
	<label for="url">URL:</label>
	<input type="text" name="url" value="{$url|escape:"html"}" maxlength="255" size="40"/></span>

	<div class="fieldnotes">Link to more information (e.g. discussion forum)</div>
	
	{if $errors.url}</div>{/if}
</div>



<div class="field">
	{if $errors.event_date}<div class="formerror"><p class="error">{$errors.event_date}</p>{/if}
	 
	<label for="event_date">Event Date:</label>
	{html_select_date prefix="event_date" time=`$event_time` end_year="+10" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	
	{if $errors.publish_date}</div>{/if}
</div>

<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	 
	<label for="grid_reference">Relevant Grid Square:</label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="6"/>
	
	<div class="fieldnotes">Square in which this event is located</div>
	
	{if $errors.grid_reference}</div>{/if}
</div>

<div class="field">
	{if $errors.gridimage_id}<div class="formerror"><p class="error">{$errors.gridimage_id}</p>{/if}
	 
	<label for="gridimage_id">Relevant Image ID:</label>
	<input type="text" name="gridimage_id" value="{$gridimage_id|escape:"html"}" maxlength="12" size="6"/>
	
	<div class="fieldnotes">Optional, enter a image ID to illustrate this event</div>
	
	{if $errors.gridimage_id}</div>{/if}
</div>

<div class="field">
	{if $errors.description}<div class="formerror"><p class="error">{$errors.description}</p>{/if}
	 
	<label for="description">Short Description:</label>
	<input type="text" name="description" value="{$description|escape:"html"}" maxlength="255" size="90" style="width:58em"/>
	
	<div class="fieldnotes">Please provide a meaningful short description for the event</div>
	
	{if $errors.extract}</div>{/if}
</div>



</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> {if $title == 'New Article'}<br/>(Articles will only show on the site once they have been approved by a site moderator){/if}</p>
</form>



{include file="_std_end.tpl"}
{/dynamic}
