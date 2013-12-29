{assign var="page_title" value="Quiz"}
{include file="_std_begin.tpl"}

{dynamic}

<div style="float:right;position:relative">
	{$quiz.done} Done
	{if $quiz.correct}
		({$quiz.correct} Correct)
		<br/><a href="?save=1&quiz_id={$quiz.quiz_id}" style="font-size:x-small">Save your Score</a>
	{/if}
</div>

<h2><a href="?">Quizes</a> :: {$quiz.title|escape:'html'}</h2>

	{if $quiz.public}
		<!--This is a public quiz. Send this link to others so they can try this quiz:<br/>
		<tt>http://{$http_host}/games/quiz.php?go={$quiz.quiz_id}</tt>-->
	{elseif $user_id == $quiz.user_id}
		<div class="interestBox" style="width:480px; background-color:yellow; margin-left:auto; margin-right:auto">
			This is a private quiz and only visible to you. For others to play the quiz, you need to send them this link:<br/>
		<	tt>http://{$http_host}/games/quiz.php?go={$quiz.quiz_id}&amp;auth={$quiz.auth}</tt>
		</div>
	{/if}
<br/>

{if $question}

	<div class="interestBox">
		{$question.question}
	</div>
	<br/><hr/>

	<form action="{$script_name}?go={$quiz.quiz_id}{if $quiz.auth}&amp;auth={$quiz.auth}{/if}" method="post" name="theForm">
	<input type=hidden name="question_id" value="{$question.question_id}"/>

	{foreach name=looper from=$keys item=answer}
	  {if $question.$answer}
	     {$smarty.foreach.looper.iteration}.
	     <input type=radio name="answer" value="{$answer}" id="i{$answer}" ondblclick="this.form.submit()"/> <label for="i{$answer}">{$question.$answer}</label>
	     <hr/>
	  {/if}

	{/foreach}

	<input type="submit" name="submit" value="Submit Answer" onclick="autoDisable(this);" style="font-size:1.1em"/></p>
	</form>

{else}
	<p>No more questions!
		<b>{$quiz.done} Done</b>
		{if $quiz.correct}
			({$quiz.correct} Correct)</p>
			
			<p><a href="?save=1&quiz_id={$quiz.quiz_id}">Save your Score</a> (until you save nobody can see your score)</p>
		{/if}
	</p>
{/if}


{/dynamic}



<br/><br/>

{include file="_std_end.tpl"}
