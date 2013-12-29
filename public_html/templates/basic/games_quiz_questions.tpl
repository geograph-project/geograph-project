{assign var="page_title" value="Quizes"}
{include file="_std_begin.tpl"}

<h2><a href="?">Quizes</a> :: Questions</h2>

<p></p>

{dynamic}
		
	<h3>Questions for {$tag.title|escape:'html'}</h3>
	<p><a href="?create={$tag.tag_id}">Add a new question</a></p>
	
	<table class="report sortable" id="events">
	<thead><tr>
		<td>Question</td>
		<td>Answers</td>
	</tr></thead>
	<tbody>


	{if $questions}
	{foreach from=$questions item=item}
		<tr>
			<td>{$item.question|escape:"html"}</td>
			<td>{$item.count|escape:"html"}</td>
			{if $user->user_id == $item.user_id}
			<td><a href="?edit={$item.question_id|escape:"url"}">Edit Question</a></td>
			<td><a href="?delete={$item.question_id|escape:"url"}&amp;tag_id={$item.tag_id}" onclick="return confirm('Are you sure? If this question is used in active quiz(es) it may change the result.">Delete Question</a></td>
			{/if}
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="3">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>

{if $questions}
	<form method="post">
		<h3>Create new quiz from this series</h3>
		<input type=hidden name="tag_id" value="{$tag.tag_id}"/>
		Quiz title: <input type="text" name="title" value="{$tag.title|escape:'html'}" size="50" maxlength="64"/>
		<input type="submit" name="create" value="Create"/><br/>
		<input type="checkbox" name="owner" value=1 {if $user_id}checked{/if}/> Only include questions I've Created<br/>
		<input type="checkbox" name="public" value=1 checked/> List this quiz publically<br/>
		Results visible to: <input type="radio" name="results" value=0 checked/> Only you /
			<input type="radio" name="results" value=1 /> Anyone playing the quiz /
			<input type="radio" name="results" value=2 /> Publically
		
		<br/>
		<i>(Non public quizes, will only be visible to you and/or anyone you send a direct link to)</i>
	</form>
{/if}

{/dynamic}

<br/><br/>

{include file="_std_end.tpl"}
