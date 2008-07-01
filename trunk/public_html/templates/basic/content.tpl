{assign var="page_title" value="Content submitted to Geograph"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.helpbox { padding:5px;background:#dddddd;position:relative;font-size:0.8em; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
</style>{/literal}



<div style="float:right"><a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></div>

<h2>Content submitted to Geograph</h2>



<table style="width:100%">
	<tbody>
		<tr>
			<td width="80%" valign="top" height="600">
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
Common Themes:<br/>
{foreach from=$words key=word item=count}
	<span class="nowrap">&middot; <a href="/content/?inner&amp;q={$word}" target="content" onclick="document.getElementById('q_title').value='{$word}'" style="font-size:{math equation="log(c)" c=$count}em">{$word}</a></span>
{/foreach}
<hr/>

<b>Content Comprises:</b>
 <ul>
  <li><a href="/article/">Articles</a></li>
  <li><a href="/gallery/">Galleries</a></li>
  {if false && $enable_forums}
	  <li><a href="/discuss/?action=vtopic&amp;forum=5">Grid Square Discussions</a></li>
	  <li><a href="/discuss/?action=vtopic&amp;forum=6">Submitted Articles</a></li>
  {/if}
  <li><a href="/help/sitemap">Help Documents</a></li>
 </ul>
 
 <p><b>Interested in Submitting Content?</b> See <a href="/article/Content-on-Geograph">this article</a> for where to do it</p>


</td>
		</tr>
	</tbody>
</table>

<br/><br/>


{include file="_std_end.tpl"}
