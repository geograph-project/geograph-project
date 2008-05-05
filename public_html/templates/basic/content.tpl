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
			<td width="80%">
<iframe src="/content/?inner{$extra}" width="100%" height="550" frameborder="0" name="content"></iframe>
			</td>
			<td valign="top" class="helpbox">

<ul>
	<li><a href="/content/?inner" target="content">Recently Updated</a></li>
	<li><a href="/content/?inner&amp;order=created" target="content">Recently Created</a></li>
	<li><a href="/content/?inner&amp;loc" target="content">Location Specific</a></li>
	<li><a href="/content/?inner&amp;docs&amp;order=title" target="content">Geograph Documents</a></li>
</ul>
<br/>
<form action="/content/" method="get" target="content">
<input type="hidden" name="inner" value="1"/>
<lable for="q_title">Title Search:</label><br/>
<input type="text" name="q" id="q_title" size="30"/><br/>
<input type="submit" value="Find"/>
</form>
<ul>
	<li><a href="/content/?inner&amp;q=Geographical+Features" target="content" onclick="document.getElementById('q_title').value='Geographical Features'">Geographical Features</a></li>
	<li><a href="/content/?inner&amp;q=Symbols" target="content" onclick="document.getElementById('q_title').value='Symbols'">OS Map Symbols</a></li>
	<li><a href="/content/?inner&amp;q=Wind+Farm" target="content" onclick="document.getElementById('q_title').value='Wind Farm'">Wind Farm</a></li>
	<li><a href="/content/?inner&amp;q=Coast" target="content" onclick="document.getElementById('q_title').value='Coast'">Coast</a></li>
	<li><a href="/content/?inner&amp;q=Railway" target="content" onclick="document.getElementById('q_title').value='Railway'">Railway</a></li>
</ul>
<hr/>
This section is new and under development, and will include more options. For the moment you can use the above box to search the titles (only), we have also noted a few example searches.
<hr/>

<b>Comprising:</b>
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
