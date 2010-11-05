{assign var="page_title" value="Content Themes"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div class="tabHolder">
	<a href="/content/" class="tab">List/Search</a>
	<span class="tabSelected">Popular Title Words</span>
	<a href="?" class="tab">Short Clusters</a>
	<a href="?v=2" class="tab">Long Clusters</a>
	<a href="?v=3" class="tab">Collaborative</a>
</div>
<div class="interestBox">
	<h2>Common Themes</h2>
</div>

{foreach from=$words key=word item=count}
	<span class="nowrap">&middot; <a href="/content/?q=title:{$word}" style="font-size:{math equation="log(c)" c=$count}em">{$word}</a></span>
{/foreach}

<br style="clear:both"/>

{include file="_std_end.tpl"}
