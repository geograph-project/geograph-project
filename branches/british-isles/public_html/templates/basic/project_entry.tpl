{assign var="page_title" value="Project :: $title"}
{assign var="meta_description" value=$content|truncate:200}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
</style>{/literal}
<script src="{"/sorttable.js"|revision}"></script>


<div style="float:left; position:relative; padding-right:10px;height:40px"><h3 style="margin-top:0px"><a href="/project/">Geograph Projects</a> ::</h3></div>

<h2 style="margin-bottom:0px" class="nowrap">{$title|escape:"html"}</h2>
{if $initiator}
	<div><i>proposed by {$initiator|escape:'html'}</i></div>
{else}
	<div><i>proposed by <a title="View profile" href="/project/?u={$user_id}">{$realname|escape:'html'}</a></i></div>
{/if}

{if $purpose}
	<div style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px"><b>Purpose/Goal</b>:<div style="margin-left:10px;">{$purpose|nl2br|GeographLinks:true}</div></div>
{/if}
{if $reason}
	<div style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px"><b>Why do this project?</b><div style="margin-left:10px;">{$reason|nl2br|GeographLinks:true}</div></div>
{/if}

<p style="margin-left:auto;margin-right:auto;width:600px;background-color:#eee;padding:10px;">{$content|nl2br|GeographLinks:true}</p>

{if $links}
	<div style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px">
		<b>Links about this project</b><br/>
		{foreach from=$links item=link}
			&middot; <a href="{$link.link|escape:'html'}">{$link.title|escape:'html'|default:'untitled link'}</a><br/>
		{/foreach}
		</div>
{/if}

{if $registers}
	<div style="margin-left:auto;margin-right:auto;width:600px;margin-top:10px">
		<div style="float:left;width:280px;">
			<b>Supporters</b><br/>

			{foreach from=$registers item=register}
			    {if $register.supporter}
					&middot; <a href="/profile/{$register.user_id|escape:'html'}" title="{$register.role|escape:'html'}">{$register.realname|escape:'html'}</a><br/>
				{/if}
				{if $register.user_id eq $user->user_id}
					{assign var="current_register" value="1"}
				{/if}
			{/foreach}
			</ul>
		</div>
		<div style="float:left;width:280px;">
			<b>Helpers</b><br/>

			{foreach from=$registers item=register}
			    {if $register.helper}
					&middot; <a href="/profile/{$register.user_id|escape:'html'}" title="{$register.role|escape:'html'}">{$register.realname|escape:'html'}</a><br/>
				{/if}
			{/foreach}
			</ul>
		</div>
		<br style="clear:both"/>
	</div>
{/if}

<div class="interestBox" style="margin-top:10px"><a href="/project/register.php?id={$project_id}">{if $current_register}Modify{else}Register{/if} your interest in this project</a></div>

<hr/>
<div style="color:silver;text-align:right;font-size:0.8em">Created: {$published|date_format:"%a, %e %b %Y"}, Updated: {$updated|date_format:"%a, %e %b %Y"}</div>


{if $user->user_id == $user_id || $isadmin}
	<p style="clear:both"><a href="/project/edit.php?id={$project_id}">Edit this entry</a> | <a href="/project/links.php?id={$project_id}">Edit Links</a></p>
{/if}

<br style="clear:both"/>


<div id="disqus_thread"></div>
<script type="text/javascript">{literal}
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = 'geograph'; // required: replace example with your forum shortname

    // The following are highly recommended additional parameters. Remove the slashes in front to use.{/literal}
    var disqus_identifier = 'project{$project_id}';
    var disqus_url = '{$self_host}/project/entry.php?id={$project_id}';

{literal}
    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
{/literal}</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">project comments powered by <span class="logo-disqus">Disqus</span></a>


{include file="_std_end.tpl"}

