{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/location/dragdrop.css"|revision}" media="screen" />

<script src="{"/location/dragdrop.js"|revision}" type="text/javascript"></script>
<script src="{"/location/edit.js"|revision}" type="text/javascript"></script>
	
{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<div style="position:relative;float:right;width:300px;height:100%; background-color:#eeeeee">

<form onsubmit="return false;" name="searchForm">
<label for="hq">Image Search:</label>
<input type="text" name="q" id="hq" value="test"/>
<input type="button" value="Find" onclick="performSearch(this.form.q.value);"/>
</form>

<div id="searchResults">

</div>


</div>


<form class="simpleform" action="/location/edit.php" method="post" name="theForm">

<input type="hidden" name="location_id" value="{$location_id|escape:"html"}"/>


<fieldset style="clear:none">


{if $title == 'New Location'}
<legend>Create Location</legend>
{else}
<legend>Edit Location</legend>

<div class="field">
	{if $errors.url}<div class="formerror"><p class="error">{$errors.url}</p>{/if}
	 
	<label for="url">URL:</label>
	<span class="nowrap"><small><tt>http://{$http_host}/location/</tt></small><input type="text" name="url" value="{$url|escape:"html"}" maxlength="64" size="40"/></span>

	<div class="fieldnotes">Shouldn't be changed once location published.</div>
	
	{if $errors.url}</div>{/if}
</div>
{/if}

<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}
	 
	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	{if $errors.title}</div>{/if}
</div>

<div class="field">
	{if $errors.licence}<div class="formerror"><p class="error">{$errors.licence}</p>{/if}
	 
	<label for="licence">Licence:</label>
	<select name="licence">
	{html_options options=$licences selected=$licence}
	</select> by
	<a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>
	

	{if $errors.licence}</div>{/if}
</div>





<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	 
	<label for="grid_reference">Grid Reference:</label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="10"/>
	
	<div class="fieldnotes">For larger features please use a central location</div>
	
	{if $errors.grid_reference}</div>{/if}
</div>


<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}
	 
	<label for="content">Short Description:</label>
	<textarea rows="6" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea></p>
	
	{if $errors.content}</div>{/if}
</div>

</fieldset>

<div style="position:relative; border:1px solid blue; background-color:silver; width:640px;padding:5px">
<input type="button" value="new folder">

	<div class="dividerBox" id="folder1" >
		<input type="text" value="Folder 1"/>
	</div>
	<div class="imageBox" id="imageBox147867">
		<div style="border:none">&lt;-- drag <br/> images here</div>
	</div>
	


	<div class="dividerBox" id="folder2" >
		<input type="text" value="Folder 2"/>
	</div>
	<div class="imageBox" id="imageBox147867">
		<div style="border:none">&lt;-- drag <br/> images here</div>
	</div>
	
	<br style="clear:both;"/>
	
	<div id="insertionMarker">
		<img src="images/marker_top.gif">
		<img src="images/marker_middle.gif" id="insertionMarkerLine">
		<img src="images/marker_bottom.gif">
	</div>
	<div id="dragDropContent">
	</div>
	<div id="debug" style="clear:both">
	</div>
	
</div>

<br/><br/><br/>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');" style="color:green"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em; color:green"/> {if $title == 'New Location'}<br/>(Location will only show on the site once they have been approved by a site moderator){/if}</p>
</form>





{include file="_std_end.tpl"}
{/dynamic}
