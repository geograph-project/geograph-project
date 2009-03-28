{assign var="page_title" value="Content Themes"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div class="interestBox">
<h2 style="margin-bottom:0">User Contributed Content</h2>
Documents are assigned automatically to groups, this is still experimental! <small>[
{if $v}
<a href="?">Short</a>/<b>Long</b> 
{else}
<b>Short</b>/<a href="?v=2">Long</a> 
{/if} ]
Version
</small><br/>
- Click the + symbol to expand a group.
</div>

<br/>
<ul id="treemenu2" class="treeview" style="font-size:1.2em">
{assign var="lastid" value="0"}
{foreach from=$list item=item}
{if $lastcat != $item.label}
{if $lastcat}
</li></ul>
{/if}
<li><b>{$item.label}</b><ul>
{assign var="lastname" value=""}
{/if}
	<li><b><a title="{$item.extract|default:'View Content'}" href="{$item.url}">{$item.title}</a></b><br/>
	<small id="att{$lastid+1}"><small style="color:lightgrey">by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a></small></small>

	</li>
	{if $lastname == $item.realname && $user->realname != $lastname}
		<script>document.getElementById('att{$lastid}').style.display='none'</script>
	{/if}
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.label}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no articles to display at this time.</i>
{/foreach}
{if $lastcat}
</li></ul>
{/if}
</ul>

<br style="clear:both"/>
<script type="text/javascript">
var static_host = '{$static_host}';
{literal}
function setuptreemenu() {
	ddtreemenu.createTree("treemenu2", true, 5);
}
AttachEvent(window,'load',setuptreemenu,false);
{/literal}</script>
<script type="text/javascript" src="{"/js/simpletreemenu.js"|revision}"></script>


{include file="_std_end.tpl"}
