{assign var="meta_description" value="User contributed image collections"}
{assign var="page_title" value="Collections"}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}

<h2>{$orders.$order} Photo Collections</h2>

<p>Here is a small selection of recently updated collections, grouped by type.</p>

<style>{literal}
.boxlist {
	float:left;
	margin:5px;
	width:300px;
	height:400px;
	overflow:hidden;
	border-radius:10px;
}
.boxlist ul {
        margin:0;padding:0;
}
.boxlist li {
	margin:0;padding:0px;
	list-item-style:none;
	font-family:"Comic Sans MS", Georgia, Verdana, Arial, serif;
	font-size:0.8em;
	padding-left: 16px ;
	text-indent: -12px ;
	border-bottom: 1px solid silver;
}
.boxlist a {
	text-decoration:none;
}
.boxlist div {
	font-weight:bold;
	padding-left:10px;
	text-size:1.1em;
	background-color:black;
}
.boxlist div a {
	color: white;
}
{/literal}</style>


{foreach from=$lists item=list key=source}
	{if $list}
	<div class="boxlist" style="background-color:#{$colours.$source}">
	<div><a href="/content/?scope[]=$source">{$sources.$source|regex_replace:'/y$/':'ie'|regex_replace:'/s$/':''}s</a></div>

	<ul>
	{foreach from=$list item=item}

		<li>
		<a href="{$item.url}" title="{$item.extract|escape:'html'}{if $item.user_id}, {if $item.source == 'themed' || $item.source == 'gallery'} started{/if} by {$item.realname|escape:'html'}{/if}">{$item.title|escape:'html'}</a></b>
		</li>

	{/foreach}
	</ul>
	</div>
	{/if}
{/foreach}



<br style="clear:both"/>


{include file="_std_end.tpl"}
