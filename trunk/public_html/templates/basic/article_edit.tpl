{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="/article/edit.php" method="post">

<input type="hidden" name="article_id" value="{$article_id|escape:"html"}"/>

 
<fieldset>
<legend>Create/Edit Article</legend>

{if $title != 'New Article'}
<div class="field">
	{if $errors.url}<div class="formerror"><p class="error">{$errors.url}</p>{/if}
	 
	<label for="url">URL:</label>
	<span class="nowrap"><small><tt>http://{$http_host}/article/</tt></small><input type="text" name="url" value="{$url|escape:"html"}" maxlength="64" size="40"/></span>

	<div class="fieldnotes">Shouldn't be changed once article published.</div>
	
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
	{if $errors.publish_date}<div class="formerror"><p class="error">{$errors.publish_date}</p>{/if}
	 
	<label for="publish_date">Publish Date:</label>
	{html_select_date prefix="publish_date" time=`$publish_date` start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}
	
	<div class="fieldnotes">Date of copyright, when the piece was written.</div>
	
	{if $errors.publish_date}</div>{/if}
</div>

<div class="field">
	{if $errors.article_cat_id}<div class="formerror"><p class="error">{$errors.article_cat_id}</p>{/if}
	 
	<label for="article_cat_id">Category:</label>
	<select name="article_cat_id">
	{html_options options=$article_cat selected=$article_cat_id}
	</select> 
	

	{if $errors.licence}</div>{/if}
</div>


<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	 
	<label for="grid_reference">Relevent Grid Square:</label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="6"/>
	
	<div class="fieldnotes">Optional, currently unused, to be used to help organise articles.</div>
	
	{if $errors.grid_reference}</div>{/if}
</div>

<div class="field">
	{if $errors.extract}<div class="formerror"><p class="error">{$errors.extract}</p>{/if}
	 
	<label for="extract">Short Description:</label>
	<input type="text" name="extract" value="{$extract|escape:"html"}" maxlength="255" size="90" style="width:58em"/>
	
	<div class="fieldnotes">Please provide a meaningful short description for the content.</div>
	
	{if $errors.extract}</div>{/if}
</div>

<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}
	 
	<label for="content">Content:</label>
	<textarea rows="40" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea></p>
	
	{if $errors.content}</div>{/if}
</div>

</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> {if $title == 'New Article'}<br/>(Articles will only show on the site once they have been approved by a site moderator){/if}</p>
</form>

<div style="padding:5px; border: 1px solid gray; background-color:silver; font-family: monospace; font-size:0.9em">
| <b>Quick Reference</b> | [<a href="/article/Help_on_formatting_of_articles" target="_blank">Full Reference</a> (opens in new window) |

<h2>[h2]Big Title[/h2]</h2>
<h3>[h3]Sub Title[/h3]</h3>
<small>&middot; h2, h3 and h4 will create an automatic table of contents</small>

<p>[b]<b>Bold</b>[/b], [i]<i>Italic</i>[/i] and [big]<big>Big</big>[/big]</p>

<p>* Bulleted List<br/>
* Item Two</p>

<p>[[[123434]] <small>&middot; insert the thumbnail of a geograph image - get the id from the image url</small></p>


<p>[url=http://www.example.com]Goto this site[/url]</p>

<small>&middot; See the full reference for including tables, map extracts, external images and order features.</small>
</div>

{include file="_std_end.tpl"}
{/dynamic}
