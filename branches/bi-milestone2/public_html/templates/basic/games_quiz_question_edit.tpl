{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="/games/quiz/question_edit.php" method="post">

<input type="hidden" name="question_id" value="{$question_id|escape:"html"}"/>

 
<fieldset>
<legend>Create/Edit Question</legend>




<div class="field">
	{if $errors.type}<div class="formerror"><p class="error">{$errors.type}</p>{/if}
	 
	<label for="type">Question Type:</label>
	<select name="type">
	{html_options options=$types selected=$type}
	</select>
	

	{if $errors.type}</div>{/if}
</div>

<div class="field">
	{if $errors.question_cat_id}<div class="formerror"><p class="error">{$errors.question_cat_id}</p>{/if}
	 
	<label for="question_cat_id">Category:</label>
	<select name="question_cat_id">
	{html_options options=$question_cats selected=$question_cat_id}
	</select> 
	

	{if $errors.licence}</div>{/if}
</div>


<div class="field">
	{if $errors.gridimage_id}<div class="formerror"><p class="error">{$errors.gridimage_id}</p>{/if}
	 
	<label for="gridimage_id">Relevant Image ID:</label>
	<input type="text" name="gridimage_id" value="{$gridimage_id|escape:"html"}" maxlength="12" size="6"/>
	
	<div class="fieldnotes">Optional, enter a image id to illustrate this question</div>
	
	{if $errors.gridimage_id}</div>{/if}
</div>

<div class="field">
	{if $errors.hide_option}<div class="formerror"><p class="error">{$errors.hide_option}</p>{/if}
	 
	<label for="hide_option">Hide Image Source:</label>
	<input type="checkbox" name="hide_option" {if $hide_option} checked{/if} value="1"/>
	
	<div class="fieldnotes">Optional, tick box to make the thumbnail annonymous - useful for guessing games etc</div>
	
	{if $errors.hide_option}</div>{/if}
</div>

<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}
	 
	<label for="content">Question:</label>
	<textarea rows="5" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea>
	
	{if $errors.content}</div>{/if}
</div>

{foreach from=$answers key=key item=answer}
<div class="field">
	{if $errors.answer.$key}<div class="formerror"><p class="error">{$errors.answer.$key}</p>{/if}
	 
	<label for="answer[{$key}]">Answer {$key}:</label>
	<input type="text" name="answer[{$key}]" value="{$answer.content|escape:"html"}" maxlength="64" size="32"/> &nbsp;&nbsp;&nbsp;
	<label for="answer_correct[{$key}]" style="display:inline;float:none;">Correct?</label> 
		<input type="checkbox" name="answer_correct[{$key}]" {if $answer.correct} checked{/if} value="1"/>
	
	{if $errors.answer.$key}</div>{/if}
</div>
{/foreach}
<div class="fieldnotes">TIP: use [[12344]] in the answers to show thumbnails, honours the 'Hide Image Source' setting above</div>

<input type="submit" name="another" value="Add another answer..."/> 

</fieldset>

<hr/>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> {if $title == 'New Question'}<br/>(Questions will only show on the site once they have been approved by a site moderator){/if}</p>
</form>



{include file="_std_end.tpl"}
{/dynamic}
