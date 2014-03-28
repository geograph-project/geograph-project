{assign var="page_title" value="Idea Matrix"}
{include file="_std_begin.tpl"}

{literal}<style type="text/css">
        .black_overlay{
            display: none;
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index:1001;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }
        .white_content {
            display: none;
            position: absolute;
            top: 20%;
            left: 20%;
            width: 60%;
            height: 60%;
            padding: 6px;
            border: 6px solid orange;
            background-color: white;
            z-index:1002;
            overflow: auto;
        }
.closer {
	float:right;
	background-color:pink;
	padding:4px;
}
.closer a {
	color:black;
}
.ideamatrix {
	font-size:0.9em;
}
.ideamatrix th.titlebar {
	background-color:black;
	color:white;
	padding:10px;
	text-align:left;
}
.ideamatrix th.titlebar h3 {
	margin-top:0;	
	border-bottom:1px solid gray;
}
.ideamatrix th a {
	color:cyan;
}
.ideamatrix td {
	font-family: 'Comic Sans MS',Georgia,Verdana,Arial,serif;
	padding-bottom:20px;
}
.ideamatrix td h3 {
	margin:0;
	padding:2px;
	background-color:silver;
	text-transform:uppercase;
}
.ideamatrix form {
	float:right;
}
.ideamatrix td span {
	font-family: Georgia;
	margin:1px;
	border-top:1px solid silver;
	padding:1px;
	color:gray;
}
.ideamatrix td span a {
	color:gray;
}
</style>
<script type="text/javascript">
function showbox(formm,idea_id,idea,tone) {
	document.getElementById('light'+formm).style.display='block';
	document.getElementById('fade').style.display='block';
	if (idea_id) {
		document.forms['createComment'].elements['idea_id'].value = idea_id;
		document.forms['createComment'].elements['idea'].value = idea;
		document.forms['createComment'].elements['tone'].value = tone;
	}
	return false;
}
</script>
{/literal}


<h2>Idea Matrix :: Evolving the moderation system</h2>

<p><a href="javascript:void(showbox(1))">Add new Idea / Proposal</a></p>

{dynamic}

<table class="ideamatrix" border="1" cellspacing=0 cellpadding=3>
	{foreach from=$ideas item=idea key=idea_id}
		<tr>
			<th colspan=7 class="titlebar">
				{if !$idea.ownvote && $idea.user_id != $user->user_id}
					<form method="post" action="{$script_name}?voteIdea">
						<input type=hidden name="idea_id" value="{$idea_id}">
						<input type=submit value="I support this">
					</form>
				{/if}
				<h3>{$idea.title|escape:'html'}{if $idea.votes}, <b>{$idea.votes}</b> votes{/if}</h3>
				{$idea.description|escape:'html'|nl2br|geographlinks}
			</th>
		</tr>
		<tr>
			{foreach from=$idea.columns item=col key=tone}
				<td valign="top">
					<h3>{$tone|escape:'html'}</h3>
					{foreach from=$col item=comment}
						{if !$comment.ownvote && $comment.user_id != $user->user_id}
							<form method="post" action="{$script_name}?voteComment">
								<input type=hidden name="idea_id" value="{$idea_id}">
								<input type=hidden name="comment_id" value="{$comment.comment_id}">
								<input type=submit value="I would say this too">
							</form>
						{/if}
						{$comment.comment|escape:'html'}<br/>
						<span>by <a href="/profile{$comment.user_id}">{$comment.realname|escape:'html'}</a>, {$comment.day|escape:'html'}{if $comment.votes}, <b>{$comment.votes}</b> votes{/if}</span>
						<hr/>	
					{/foreach}
					<a href="javascript:void(showbox(2,{$idea_id},'{$idea.title|escape:'javascript'}','{$tone}'))">Add a <b>{$tone}</b> comment</a>
				</td>
			{/foreach}
		</tr>
		<tr>
			<td colspan=7></td>
		</tr>
	{/foreach}
</table>


{/dynamic}

{include file="_std_end.tpl"}

<div id="light1" class="white_content">
	<div class="closer"><a href="javascript:void(0)" onclick="document.getElementById('light1').style.display='none';document.getElementById('fade').style.display='none'">Close</a></div>
        <form method="post" action="{$script_name}?createIdea" name="createIdea">
		<h3>Create a new Idea/Proposal</h3>
		Title: <input type=text name="title" size=50 maxlength=64 placeholder="Enter a short title here"><br/>
		<textarea name="description" rows="15" cols="60" placeholder="Enter a short description here"></textarea><br/>
		Just put the facts of the idea here. Can expand on the pros and cons seperatly (as comments).<br/>
		<input type="submit" value="create">
	</form>
</div>
<div id="light2" class="white_content">
	<div class="closer"><a href="javascript:void(0)" onclick="document.getElementById('light2').style.display='none';document.getElementById('fade').style.display='none'">Close</a></div>
        <form method="post" action="{$script_name}?createComment" name="createComment">
		<h3>Add new Comment to a Idea/Proposal</h3>
		<input type=hidden name=idea_id value="">
		<input type=text name=idea value="" size="64" readonly=readonly><br/>
		Tone:<input type=text name=tone value="" size="40" readonly=readonly><br/>
		<textarea name="comment" rows="15" cols="60" placeholder="Enter your comment here"></textarea><br/>
		If you have multiple points please submit as SEPERATE comments, rather than bundle all together. A comment should be short, easily digestable, and self-contained. <br/>
		<input type="submit" value="submit">
	</form>
</div>
<div id="fade" class="black_overlay"></div>

