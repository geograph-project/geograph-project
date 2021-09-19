{include file="_std_begin.tpl"}

<link rel="stylesheet" type="text/css" title="Monitor" href="{"/discuss/bb_default_style.css"|revision}" media="screen" />

{dynamic}

{if $thread.title}
	<h2>Comment Thread for {$thread.title}</h2>
{else}
	<h2>Untitled Comment Thread</h2>
{/if}

{if $thread.for_right == 'forum'}
	Discussion with Forum Moderators
{elseif $thread.for_right == '"moderator"'}
        Discussion with Images Moderators
{elseif $thread.for_right == 'director'}
        Discussion with Directors
{elseif $thread.for_right == 'basic'}
        Discussion with Any Registered User
{elseif $thread.for_right == 'all'}
        Public Discussion
{/if}

{if $realname}
	 and User: <a href="/profile/{$thread.for_user_id}">{$realname|escape:'html'}</a></p>
{/if}


{if $topic_title}
	<p>Reference Thread: <a href="/discuss/?action=vthread&topic={$thread.for_topic_id}">{$topic_title|escape:'html'}</a></p>
{/if}


<table class=forums>
{foreach from=$posts key=post_id item=post name="posts"}
	<tr class={cycle values="tbCel1,tbCel2"}><td valign=top class=caption1><b>{$post.realname|escape:'html'}</b><br/>
	{$post.created}<br/><br/>
	<td valign=top class=caption1>{$post.comment|escape:'html'|nl2br}<br/><br/></td>
	</tr>
{foreachelse}
	<a href="?id={$thread.comment_thread_id}&login=true">You may need to login to view this thread</a>
{/foreach}
</table>

{if $user->registered && $thread.comment_thread_id}
<h3>Post Reply</h3>
<form name="postMsg" action="{$script_name}?id={$thread.comment_thread_id}" method="post" class="formStyle">

<textarea name="comment" cols="38" rows="12" class="textForm" tabindex="2" style="width:550px;"></textarea><br/>


<input type=checkbox name=anon value="forum"> Sign message from 'Geograph Forum Moderators' rather than you specifically<br>


<input type="SUBMIT" value="Post message" class="inputButton" tabindex="5">
</form>
(Note: The previous commenters, shown above, will be notified of your comment by email)


{else}
	<a href="?id={$thread.comment_thread_id}&login=true">Login to reply</a>
{/if}

{/dynamic}


{include file="_std_end.tpl"}

