{assign var="page_title" value="Quizes"}
{include file="_std_begin.tpl"}

<h2><a href="?">Quizes</a></h2>

<p></p>

{dynamic}
	
	<h3>Published Quizes</h3>
		<table class="report sortable" id="events">
		<thead><tr>
			<td>Title</td>
			{if $user_id}
			<td>Series</td>
			<td>Public</td>
			{/if}
			<td>Questions</td>
			<td>Players</td>
		</tr></thead>
		<tbody>
		
		{if $quizs}
		{foreach from=$quizs item=item}
			<tr>
				<td><a href="?go={$item.quiz_id}">{$item.title|escape:"html"}</a></td>
				{if $user_id}
				<td>{$item.tag|escape:"html"}</td>
				<td>{if $item.public}yes{/if}</td>
				{/if}
				<td>{$item.count|escape:"html"}</td>
				<td>{$item.players|escape:"html"}</td>
				{if $item.user_id == $user_id}
				<td><a href="?close={$item.quiz_id|escape:"url"}">Close Quiz</a></td>
				<!--td><a href="?results={$item.quiz_id|escape:"url"}">Results</a></td-->
				{/if}
			</tr>
		{/foreach}
		{else}
			<tr><td colspan="3">- nothing to show -</td></tr>
		{/if}
	
		</tbody>
	</table>
	{if $tags}
		<p>To publish a new quiz, view questions from a series below

		<h3>Series</h3>
		<table class="report sortable" id="events">
		<thead><tr>
			<td>Series</td>
			<td>Questions</td>
			{if $user_id}
			   <td>Yours</td>
			{/if}
		</tr></thead>
		<tbody>

		{foreach from=$tags item=item}
			<tr>
				<td>{$item.title|escape:"html"}</td>
				<td><a href="?questions={$item.tag_id}">{$item.count|escape:"html"}</a></td>
				{if $user_id}
				<td><a href="?questions={$item.tag_id}&amp;user_id={$user_id}">{$item.count_user|escape:"html"}</a></td>
				<td><a href="?create={$item.tag_id|escape:"url"}">Add Question</a></td>
				{/if}
			</tr>
		{/foreach}
		
		</tbody>
		</table>


		<form method="post">
			<h4>Create new series</h4>
			Series title: <input type="text" name="title" size="50" maxlength="64"/>
			<input type="submit" name="create" value="Create"/>
		</form>
	
	{/if}
{/dynamic}

<br/><br/>

{include file="_std_end.tpl"}
