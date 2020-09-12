{assign var="page_title" value="Idea :: $title"}
{assign var="meta_description" value=$content|truncate:200}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
</style>{/literal}


<div style="float:left; position:relative; padding-right:10px;height:40px"><h3 style="margin-top:0px"><a href="/project/ideas.php">Geograph ideas</a> ::</h3></div>

<h2 style="margin-bottom:0px" class="nowrap">{$title|escape:"html"}</h2>
{if $initiator}
	<div><i>proposed by {$initiator|escape:'html'}</i></div>
{else}
	<div><i>proposed by <a title="View profile" href="/profile/{$user_id}">{$realname|escape:'html'}</a></i></div>
{/if}
{if $status == 'inprogress'}
	<i>In Progress</i>
{elseif $status == 'complete'}
	<i>Complete</i>
{/if}

<p style="margin-left:auto;margin-right:auto;width:600px;background-color:#eee;padding:10px;">{$content|nl2br|GeographLinks:true}</p>

{if $items}
	{foreach from=$items item=item}
		{if $item.item_type == 'pledge'}{assign var=show_pledge value="1"}{/if}
		{if $item.item_type == 'reason'}{assign var=show_reason value="1"}{/if}
	{/foreach}
	{if $show_pledge}
		<fieldset style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px;padding:10px">
			<legend>Pledges in support of this idea</legend>
			{foreach from=$items item=item}
				{if $item.item_type == 'pledge'}
					&middot; <big>{$item.content|escape:'html'}</big>
						pledged {if $item.anon}<i>anonymouslly</i>{elseif $item.user_id == 0}by Geograph Project Ltd{else}by <a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>{/if}
					 {if $user->user_id == $item.user_id || $isadmin}<a href="idea_item.php?id={$project_idea_id}&id2={$item.project_idea_item_id|escape:'html'}">Edit</a>{/if}
					<br/>
				{/if}
			{/foreach}<br/>
			<i>If you built this feature, you get these rewards!</i>
		</fieldset>
	{/if}
	{if $show_reason}
		<fieldset style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px;padding:10px">
			<legend>Why people think this would be a good idea...</legend>
			{foreach from=$items item=item}
				{if $item.item_type == 'reason'}
					&middot; <big>{$item.content|escape:'html'}</big>
						by {if $item.anon}<i>anonymous</i>{elseif $item.user_id == 0}Geograph Project Ltd{else}<a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>{/if}
					 {if $user->user_id == $item.user_id || $isadmin}<a href="idea_item.php?id={$project_idea_id}&id2={$item.project_idea_item_id|escape:'html'}">Edit</a>{/if}
					<br/>
				{/if}
			{/foreach}
		</fieldset>
	{/if}
{/if}

{dynamic}
{if $user->registered}
<div class="interestBox" style="margin-top:20px"><a href="/project/idea_item.php?id={$project_idea_id}">Add Pledge/Reason for this project</a>, 
	or <a href="/project/ideas_vote.php?add={$project_idea_id}">vote for this idea</a>.</div>
{/if}
{/dynamic}

<hr/>
<div style="color:silver;text-align:right;font-size:0.8em">Created: {$created|date_format:"%a, %e %b %Y"}, Updated: {$updated|date_format:"%a, %e %b %Y"}</div>


{if $user->user_id == $user_id || $isadmin}
	<p style="clear:both"><a href="/project/idea_edit.php?id={$project_idea_id}">Edit this entry</a></p>
{/if}

<br style="clear:both"/>


<div id="disqus_thread"></div>
<script type="text/javascript">{literal}
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = 'geograph'; // required: replace example with your forum shortname

    // The following are highly recommended additional parameters. Remove the slashes in front to use.{/literal}
    var disqus_identifier = 'pidea{$project_idea_id}';
    var disqus_url = 'http://{$http_host}/project/idea.php?id={$project_idea_id}';

{literal}
    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'https://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
{/literal}</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">project comments powered by <span class="logo-disqus">Disqus</span></a>


{include file="_std_end.tpl"}

