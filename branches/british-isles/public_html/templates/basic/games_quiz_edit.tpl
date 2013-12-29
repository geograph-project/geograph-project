{assign var="page_title" value="Quizes"}
{include file="_std_begin.tpl"}

<h2><a href="?">Quizes</a> :: {if $question.question_id}Edit{else}Add{/if} Question</h2>

<p></p>

{dynamic}

{if $done}
	<p>Question created. Create another below, or <a href="?">return to homepage</a>.
{/if}
{if $used}
	<div class="interestBox" style="background-color:yellow">
		Visitors have already answered this question, and its used in active quizes. Please be careful to not change the meaning of the question by editing!
	</div>
{/if}


<form class="simpleform" action="{$script_name}" method="post" name="theForm">

	<input type=hidden name="tag_id" value="{$tag.tag_id}"/>
	<input type=hidden name="question_id" value="{$question.question_id}"/>

<fieldset>
<legend>Create/Edit Entry for {$tag.title|escape:'html'}</legend>


<div class="field">
	{if $errors.question}<div class="formerror"><p class="error">{$errors.question}</p>{/if}

	<label for="question">Question:</label>
	<textarea rows="10" cols="80" name="question" style="width:58em">{$question.question|escape:"html"}</textarea></p>

	<div class="fieldnotes">Use [[[1234]]] format to include an image thumbnail in the text. Can also use [smallmap TQ503453] to insert a small map extract - GB only. (also works in the answers below!)</div>
	
	{if $errors.question}</div>{/if}
</div>


{section name=looper start=1 loop=6 step=1}
  {assign var=answer value="answer`$smarty.section.looper.index`"}

<div class="field">
	{if $errors.$answer}<div class="formerror"><p class="error">{$errors.$answer}</p>{/if}

	<label for="title">Answer {$smarty.section.looper.index}:</label>
	<input type="text" name="{$answer}" value="{$question.$answer|escape:"html"}" maxlength="100" size="55"/>
	(<input type="radio" name="correct" value="{$answer}" {if $question.correct == $answer}checked{/if} /> Correct)

	{if $errors.$answer}</div>{/if}
</div>
{/section}


<div class="field">
	<div class="fieldnotes">Can use as few or as many answer boxes as want</div>
	
	{if $errors.options}<div class="formerror"><p class="error">{$errors.options}</p>{/if}

	<label for="options">Options:</label><br/>
	<input type=checkbox name="options[shuffle]" {if $options.shuffle}checked{/if}/> Shuffle/Randomize order of answers<br/>
	<input type=checkbox name="options[obscure]" {if $options.obscure}checked{/if} /> Don't link any thumbnails (makes it harder to cheat)<br/>
	<!-- <input type=checkbox name="options[]" {if $options.test}checked{/if}/> <br/> -->

	{if $errors.options}</div>{/if}
</div>



</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." onclick="autoDisable(this);" style="font-size:1.1em"/></p>
</form>




{/dynamic}



<br/><br/>

{include file="_std_end.tpl"}
