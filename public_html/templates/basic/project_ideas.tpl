{include file="_std_begin.tpl"}

<style>
{literal}
div.ideas div.idea {
	width:780px;
	height:170px;
	padding:3px;
	margin-top:10px;
}
div.ideas div.idea h4 {
	margin:0;
	padding:3px;
	background-color:#eeeeee;
	font-size:1.5em;
}
div.ideas div.idea h4 a {
	text-decoration:none;
}
div.ideas div.idea h4 a:hover {
	text-decoration:underline;
}
div.ideas div.idea p {
	margin:2px;
}
div.ideas div.number {
	width:80px;
	height:70px;
	margin:5px;
	float:right;
	background-color:silver;
	text-align:center;
}
div.ideas div.number big {
	display:block;
	font-size:2.2em;
}
{/literal}
</style>


	<h2>Ideas for Geograph</h2>

	<p>
		Use this page to view{if $user->registered}, and suggest new{/if} ideas for improving the Geograph website. 
		Use the dropdown below to choose a different ordering. 
		{if $user->registered}
			Use the items below to add specific support for ideas, or can <a href="ideas_vote.php">vote for your favorites, on separate page</a>.
		{else}
			Registered site users can vote on and suggest new ideas.
		{/if}
		Also can <a href="idea_activity.php">View summary of recent activity</a>
	</p>

	{if $ideas}

		<form method=get>
			Order By:
			<select name="order" onchange="this.form.submit()">
				<option value="created"{if $order eq 'created'} selected{/if}>Newest</option>
				<option value="score"{if $order eq 'score'} selected{/if}>Voting Score</option>
				<option value="reasons"{if $order eq 'reasons'} selected{/if}>Number of Reasons</option>
				<option value="pledges"{if $order eq 'pledges'} selected{/if}>Number of Pledges</option>
				<option value="activity"{if $order eq 'activity'} selected{/if}>Activity</option>
				<option value="updated"{if $order eq 'updated'} selected{/if}>Updated</option>
			</select>
		</form>

		<div class="ideas">
		{foreach from=$ideas item=idea}
			 <div class="idea">
				{if $idea.reasons}
				<div class="number" title="" onclick="location.href='idea.php?id={$idea.project_idea_id}'"><big>{$idea.reasons|thousends}</big>reasons</div>
				{/if}
				{if $idea.pledges}
				<div class="number" title="" onclick="location.href='idea.php?id={$idea.project_idea_id}'"><big>{$idea.pledges|thousends}</big>pledges</div>
				{/if}
				{if $idea.score>0}
				<div class="number" title="" onclick="location.href='idea.php?id={$idea.project_idea_id}'"><big>{$idea.score|string_format:"%.1f"}</big> points</div>
				{/if}
				<h4><a href="idea.php?id={$idea.project_idea_id}">{$idea.title|escape:'html'}</a></h4>
				<p>{$idea.content|escape:'html'|truncate:250:"... (<a href=idea.php?id=`$idea.project_idea_id`><i>read more</i></a>)"}</p>
				{if $idea.status == 'inprogress'}
					&middot; In Progress
				{/if}
				{if $user->user_id == $idea.user_id || $isadmin}
					&middot; <a href="idea_edit.php?id={$idea.project_idea_id}">Edit Idea</a>
				{/if}
				{if $user->registered}
					&middot; <a href="idea_item.php?id={$idea.project_idea_id}">Add Pledge/Reason</a>
				{/if}
			 </div>
		{/foreach}
		</div>
	{/if}

	{if $user->registered}
		<div class="interestBox" style="margin-top:30px"><a href="idea_edit.php?id=new">Add a <b>new</b> idea to this list</a> or <a href="ideas_vote.php">Vote for <b>your</b> favorite(s)</a></div>
	{/if}

{include file="_std_end.tpl"}
