{assign var="page_title" value="Trending Topics"}
{include file="_std_begin.tpl"}

<h2>Trending Topics</h2>

 <div class="interestBox" style="margin:10px">
   <form method="get" action="{$script_name}" style="display:inline">
    <select name="s" onchange="this.form.submit()">
    	{html_options options=$types selected=$s}
    </select> in the last <select name="h" onchange="this.form.submit()">
    	{html_options options=$hours selected=$h}
    </select> 
  <noscript>
    <input type="submit" value="Update"/></noscript></form></div>
    
    <ul>
	{foreach from=$table item=item}
	 <li><a href="/discuss/index.php?&amp;action=vthread&amp;forum={$item.forum_id}&amp;topic={$item.topic_id}" title="{$item.topic_time|escape:'html'}">{$item.topic_title|escape:'html'}</a> <span style="color:gray">by {$item.poster_name|escape:'html'}</span></li>
	{foreachelse}
	 	<li>No threads match the selected options.</li>
	{/foreach}
    </ul>	



<hr/>
<p><small>Note: Page generated at 1 hour intervals, please don't refresh more often than that.</small></p> 

{include file="_std_end.tpl"}
