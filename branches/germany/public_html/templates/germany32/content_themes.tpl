{assign var="page_title" value="Content Themes"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div class="interestBox">
<h2 style="margin-bottom:0">User Contributed Content</h2>
Documents are assigned automatically to groups, this is still experimental! <small>[
{if $v eq 2}
<a href="?">Short</a>/<b>Long</b>/<a href="?v=3">Alternative</a>
{elseif $v eq 3}
<a href="?">Short</a>/<a href="?v=2">Long</a>/<b>Alternative</b>
{else}
<b>Short</b>/<a href="?v=2">Long</a>/<a href="?v=3">Alternative</a> 
{/if} ]
Version
</small>
</div>



{assign var="lastid" value="0"}
{foreach from=$list item=item}
{if $lastcat != $item.label}
{if $lastcat}
</ul>
</div>
{cycle values=",<br style='clear:both'/>"}
{/if}
<div style="float:left;width:46%;position:relative; padding:5px;">
<h3>{$item.label}</h3>
<ul class="content">
{assign var="lastname" value=""}
{/if}
	<li><b><a title="{$item.extract|default:'View Content'}" href="{$item.url}">{$item.title|escape:'html'}</a></b><br/>
	<small id="att{$lastid+1}"><small style="color:lightgrey">by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname|escape:'html'}"  style="color:#6699CC">{$item.realname|escape:'html'}</a></small></small>

	</li>
	{if $lastname == $item.realname && $user->realname != $lastname}
		<script>document.getElementById('att{$lastid}').style.display='none'</script>
	{/if}
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.label}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no articles to display at this time.</i></li>
{/foreach}

</ul>
</div>
<br style="clear:both"/>

{include file="_std_end.tpl"}
