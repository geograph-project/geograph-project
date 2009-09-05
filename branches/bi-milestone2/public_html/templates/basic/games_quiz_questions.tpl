{assign var="page_title" value="Questions"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>
{dynamic}

<h2>User Contributed Questions</h2>

<p>Click a column header to reorder</p>

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Category</td>
		<td>Question</td>
		<td>Answers</td>
		<td>Updated</td>
		<td>Links</td>
	</tr>
</thead>
<tbody>
	{foreach from=$questions item=item}
	<tr>
		<td>{$item.category_name}</td>
		<td sortvalue="{$item.title}"><b>{if $item.approved != 1}<s>{/if}{$item.content|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}
		{if $item.approved != 1}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}Not publicly visible{/if}){/if}</td>
		<td style="font-size:0.9em" align="right">{$item.answers}</td>
		<td sortvalue="{$item.updated}" style="font-size:0.8em">{$item.updated|date_format:"%a, %e %b %Y"}</td>
		<td style="font-size:0.8em">
		{if $isadmin || $item.user_id == $user->user_id}
			[<a title="Edit {$item.title}" href="/games/quiz/question_edit.php?question_id={$item.question_id}">Edit</a>]
			
		{/if} 
		{if $is_mod}
			{if $item.approved == 1}
				[<a href="/games/quiz/questions.php?question_id={$item.question_id}&amp;approve=0">Disapprove</a>]
			{else}
				[<a href="/games/quiz/questions.php?question_id={$item.question_id}&amp;approve=1">Approve</a>]
			{/if}
		{/if}
		</td>
	</tr>
	{/foreach}
</tbody>
</table>


<br style="clear:both"/>

<div class="interestBox">
{if $user->registered} 
	<a href="/games/quiz/question_edit.php?question_id=new">Submit a new Question</a> (Registered Users Only)
{else}
	<a href="/login.php">Login</a> to Submit your own question!
{/if}
</div>
{/dynamic}
{include file="_std_end.tpl"}
