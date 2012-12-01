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

<hr/>
<div style="text-align:right;color:gray">{$published|date_format:"%a, %e %b %Y at %H:%M"}</div>



{if $user->user_id == $user_id || $isadmin}
	<p style="clear:both"><a href="/project/edit.php?id={$project_id}">Edit this entry</a></p>
{/if}

<br style="clear:both"/>


<div id="disqus_thread"></div>
<script type="text/javascript">{literal}
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = 'geograph'; // required: replace example with your forum shortname

    // The following are highly recommended additional parameters. Remove the slashes in front to use.{/literal}
    var disqus_identifier = 'project{$project_id}';
    var disqus_url = 'http://{$http_host}/project/entry.php?id={$project_id}';

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

