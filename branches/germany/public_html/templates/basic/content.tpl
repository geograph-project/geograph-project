{if $title}
{assign var="page_title" value="Geograph Content, `$title`"}
{assign var="meta_description" value="User contributed images collections, `$title`. "}
{else}
{assign var="page_title" value="Content submitted to Geograph"}
{assign var="meta_description" value="User contributed images collections. Showcasing a wide range of subject areas, from map symbols to lighthouses!"}
{/if}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.helpbox { padding:5px;background:#dddddd;position:relative;font-size:0.8em; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
</style>{/literal}



<div style="float:right"><a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></div>

{dynamic}
{if $user->registered && $content_count}
<div class="tabHolder">
	<span class="tabSelected">Content</span>
	<a href="/article/" class="tab">Articles</a>
	<a href="/article/?table" class="tab">Article List</a>
	<a href="/gallery/" class="tab">Galleries</a>
	{if $enable_forums}
		<a href="/discuss/index.php?action=vtopic&amp;forum=6" class="tab">Themed Topics</a>
		<a href="/article/Content-on-Geograph" class="tab">Contribute...</a>
	{/if}	
</div>
<div class="interestBox">
<h2 style="margin-bottom:0">Content submitted to Geograph</h2>
</div>
<br/>
{else}
<h2>Content submitted to Geograph</h2>
	{if $user->registered && $enable_forums}
		&middot; <a href="/article/Content-on-Geograph">Contribute your own Content...</a><br/><br/>
	{/if}
{/if}
{/dynamic}


<table width="100%">
	<tbody>
		<tr>
			<td width="80%" valign="top" height="2000">
<iframe src="/content/?inner{$extra}" width="100%" height="100%" frameborder="0" name="content"></iframe>
			</td>
			<td valign="top" class="helpbox">
<form action="/content/" method="get" target="content">
<input type="hidden" name="inner" value="1"/>
<div style="float:right">
<input type="submit" value="Find"/>
</div>
<lable for="qs">Search Content:</label> <br/>
<input type="text" name="q" id="qs" size="30"/>
</form><hr/>
Shortcuts:
<ul>
	<li><a href="/content/?inner" target="content">Recently Updated</a></li>
	<li><a href="/content/?inner&amp;order=created" target="content">Recently Created</a></li>
	<li><a href="/content/?inner&amp;order=views" target="content">Most Viewed</a></li>
	<li><a href="/content/?inner&amp;loc" target="content">Location Specific</a></li>
	<li><a href="/content/?inner&amp;docs&amp;order=title" target="content">Geograph Documents</a></li>
</ul><hr/>
Common Themes: (<a href="/content/themes.php">more...</a>)<br/>
{foreach from=$words key=word item=count}
	<span class="nowrap">&middot; <a href="/content/?inner&amp;q={$word}" target="content" onclick="document.getElementById('qs').value='{$word}'" style="font-size:{math equation="log(c)" c=$count}em">{$word}</a></span>
{/foreach}
<hr/>

<b>Content Comprises:</b>
 <ul>
  <li><a href="/article/">Articles</a></li>
  <li><a href="/gallery/">Galleries</a></li>
{dynamic}
  {if $enable_forums && $user->registered}
	  <li><a href="/discuss/?action=vtopic&amp;forum=6">Themed Topics</a></li>
  {/if}
{/dynamic}
  <li><a href="/help/sitemap">Help Documents</a></li>
 </ul>
 
 <p><b>Interested in Submitting Content?</b> See <a href="/article/Content-on-Geograph">this article</a> for where to do it</p>


</td>
		</tr>
	</tbody>
</table>

<br/><br/>


{include file="_std_end.tpl"}
