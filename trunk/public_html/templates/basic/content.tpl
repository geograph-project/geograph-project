{assign var="page_title" value="Content submitted to Geograph"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.helpbox { float:right;padding:5px;background:#dddddd;position:relative;font-size:0.8em;margin-left:20px;z-index:10; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
</style>{/literal}




<div class="helpbox">
<div style="text-align:right"><a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></div>
<br/>

 Comprising:
 <ul>
  <li><a href="/article/">Articles</a></li>
  <li><a href="/gallery/">Galleries</a></li>
  {if false && $enable_forums}
	  <li><a href="/discuss/?action=vtopic&amp;forum=5">Grid Square Discussions</a></li>
	  <li><a href="/discuss/?action=vtopic&amp;forum=6">Submitted Articles</a></li>
  {/if}
 </ul>
</div>
<h2>Content submitted to Geograph</h2>



<table style="width:100%">
	<tbody>
		<tr>
			<td>
<iframe src="/content/?inner" width="80%" height="550" frameborder="0" name="content"></iframe>
			</td>
			<td>

<ul>
	<li><a href="/content/?inner" target="content">Recently Updated</a></li>
	<li><a href="/content/?inner&amp;order=created" target="content">Recently Created</a></li>
</ul>
See also 
<ul>
	<li><a href="/content/?inner&amp;docs&amp;order=title" target="content">Geograph Documents</a></li>
</ul>
</td>
		</tr>
	</tbody>
</table>

<br/><br/>


{include file="_std_end.tpl"}
