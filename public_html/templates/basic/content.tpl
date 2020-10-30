{if $title}
{assign var="page_title" value="$title :: Collections"}
{assign var="meta_description" value="User contributed image collections, `$title`. "}
{else}
{assign var="page_title" value="Collections"}
{assign var="meta_description" value="User contributed image collections. Showcasing a wide range of subject areas, from map symbols to lighthouses!"}
{/if}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}



        {if $gridref}
                {include file="_bar_location.tpl"}
                <div class="interestBox">
			<h2>Photo Collections</h2>
                </div>
        {else}
		<h2>Photo Collections</h2>
        {/if}

{dynamic}
{if $pending}
	<p>Your non published articles:
	{foreach from=$pending item=row}
		<a href="/article/{$row.url|escape:'url'}">{$row.title|escape:'html'}</a>,
	{/foreach}
	click a title to view.</p>
{/if}
{/dynamic}

<style>{literal}

div.tickerbox {
	padding-bottom:3px;
}
div.tickerbox a {
	background-color:none !important;
}

input[type=checkbox]:checked + a {
	--font-weight:bold;
}

div.columns {
	position:relative;
}
div.sidebar {
	background-color:#dddddd;
	position:absolute;
	top:0;
	left:0;
	width:195px;
}
ul.content {
	margin-left:205px;
	font-family:"Comic Sans MS", Georgia, Verdana, Arial, serif;
	min-height:380px;
}
ul.content b {
	font-family:Georgia, Verdana, Arial, serif;
}

{/literal}</style>


<form action="/content/" method="get" name="theForm">

	<div class="tabHolder" style="float:right"><a href="/article/Content-on-Geograph" class="tab"><i>Contribute</b> to Collections</i></a></div>
	<div class="tabHolder">
                <a class="tabSelected">General Search</a>
		<a href="./recent.php" class="tab">Recent</a>
		<a href="./themes.php?v=1" class="tab">Common Words</a> 
		<a href="./themes.php?v=2" class="tab">Subject Index</a>
		<a href="/mapper/collections.php" class="tab">On a Map</a>
		 <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a>
	</div>

	<div class="interestBox">
		<div style="float:right">Order by: 
		<select name="order" onchange="this.form.submit()">
			{foreach from=$orders item=name key=key}
				<option value="{$key}"{if $order eq $key} selected{/if}>{$name}</option>
			{/foreach}
		</select></div>

		Keywords:  
		<input type="text" name="q" id="qs" size="40" {if $q} value="{$q|escape:'html'}"{/if} placeholder="enter keywords here"/>
		<input type="submit" value="Search..."/>
		<span class="nowrap">(<input type=checkbox name=in value="title" id="in_title" {if $in_title}checked{/if}/><label for="in_title">Search in <b>title only</b></label>)</span>

		{foreach from=$extra key=name item=value}
			<input type="hidden" name="{$name}" value="{$value}"/>
		{/foreach}

	</div><div class="columns"><div class="sidebar">

		Include:<br> (<a href="#" onclick="checkall(document.theForm.elements['scope[]'],true);return false;">Tick All</a>
			 <a href="#" onclick="checkall(document.theForm.elements['scope[]'],false);return false;">Untick All</a>)<br/><br>

		<div class="tickerbox">	<input type=checkbox name=scope[] value="article" onclick="clicked()" {if $scope_article}checked{/if}/><a href="/article/">Articles</a> {if $counts[4]}[<a href="#" onclick="return justcheck('article')">{$counts[4]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="gallery" onclick="clicked()" {if $scope_gallery}checked{/if}/><a href="/gallery/">Galleries</a> {if $counts[5]}[<a href="#" onclick="return justcheck('gallery')">{$counts[5]}</a>]{/if}</div>
		{if $enable_forums && $user->registered}
			<div class="tickerbox"> <input type=checkbox name=scope[] value="themed" onclick="clicked()" {if $scope_themed}checked{/if}/><a href="/discuss/index.php?action=vtopic&amp;forum=6">Themed Topics</a> {if $counts[6]}[<a href="#" onclick="return justcheck('themed')">{$counts[6]}</a>]{/if}</div>
		{/if}
		<div class="tickerbox"> <input type=checkbox name=scope[] value="blog" onclick="clicked()" {if $scope_blog}checked{/if}/><a href="/blog/">Blog Entries</a> {if $counts[2]}[<a href="#" onclick="return justcheck('blog')">{$counts[2]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="snippet" onclick="clicked()" {if $scope_snippet}checked{/if}/><a href="/snippets.php">Shared Descriptions</a> {if $counts[9]}[<a href="#" onclick="return justcheck('snippet')">{$counts[9]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="trip" onclick="clicked()" {if $scope_trip}checked{/if}/><a href="http://www.geograph.org.uk/geotrips/" title="Trip reports based on Geograph pictures and a GPS track log">Geo-Trips</a> {if $counts[1]}[<a href="#" onclick="return justcheck('trip')">{$counts[1]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="user" onclick="clicked()" {if $scope_user}checked{/if}/><a href="/finder/contributors.php">User Profiles</a> {if $counts[10]}[<a href="#" onclick="return justcheck('user')">{$counts[10]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="category" onclick="clicked()" {if $scope_category}checked{/if}/><a href="/stuff/canonical.php?final=1">Categories</a> {if $counts[11]}[<a href="#" onclick="return justcheck('category')">{$counts[11]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="context" onclick="clicked()" {if $scope_context}checked{/if}/><a href="/tags/primary.php">Geographical Context</a> {if $counts[12]}[<a href="#" onclick="return justcheck('context')">{$counts[12]}</a>]{/if}</div>
		<div class="tickerbox"> <input type=checkbox name=scope[] value="cluster" onclick="clicked()" {if $scope_cluster}checked{/if}/>Photo Clusters</a> {if $counts[16]}[<a href="#" onclick="return justcheck('cluster')">{$counts[16]}</a>]{/if}</div>
		<br style=clear:both>

		<p><small><b>Interested in contributing to Collections?</b> See <a href="/article/Content-on-Geograph">this Article</a> for where to do it</small></p>

</div>


<ul class="content">

	<p>
		{if $resultCount}
			{$shown} of {$resultCount|thousends}
		{/if}
		<b>{$title|escape:"html"}</b>
	</p>

{foreach from=$list item=item}

	<li style="border:0; background-color:{cycle values="#e9e9e9,#f0f0f0"}">
	<div style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
		{if $item.image}
		<a title="{$item.image->title|escape:'html'} by {$item.image->realname|escape:'html'} - click to view full size image" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a>
		{/if}
	</div>
	{if $item.images > 2 && ($item.source == 'themed' || $item.source == 'gallery' || $item.source == 'snippet' || $item.source == 'article')}
		<div style="position:relative;float:right;margin-right:10px">
			<a href="/browser/#/content_title={$item.title|escape:'url'}/content_id={$item.content_id}" title="View Images"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0"/></a>
		</div>
	{elseif $item.source == 'user' && $item.images > 2}
		<div style="position:relative;float:right;margin-right:10px">
			<a href="/browser/#/realname+%22{$item.title|escape:'url'}%22" title="View Images"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0"/></a>
		</div>
	{/if}
	<b><a href="{$item.url}">{$item.title|escape:'html'}</a></b><br/>
	{assign var="source" value=$item.source}
	<small><small style="background-color:#{$colours.$source}">{$sources.$source}</small><small style="color:gray">{if $item.user_id}{if $item.source == 'themed' || $item.source == 'gallery'} started{/if} by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname|escape:'html'}" style="color:#6699CC">{$item.realname|escape:'html'}</a>{/if}{if $item.posts_count}, with {$item.posts_count} posts{/if}{if $item.words|thousends}, with {$item.words} words{/if}{if $item.images}, {$item.images|thousends} images{/if}{if $item.views} and viewed {$item.views|thousends} times{/if}.
	{if $item.updated}Updated {$item.updated}.{/if}{if $item.created}Created {$item.created}.{/if}</small></small>
	{if $item.excerpt}
		<div style="font-size:0.7em;">{$item.excerpt}</div>
	{elseif $item.extract}
		<div title="{$item.extract|escape:'html'}" style="font-size:0.7em;">{$item.extract|escape:'html'|truncate:90:"... (<u>more</u>)"}</div>
	{/if}
	<div style="clear:left"></div>
	</li>


{foreachelse}
	<li><i>There are no matching Collections to display at this time.</i></li>
	{if $query_info}
		<li style="padding:20px">You have searched in Collections,<br/> you might like to try searching the <a href="/search.php?searchtext={$q|escape:'url'}&amp;do=1"><b>image database</b></a>:<br/><br/>

		<form action="/search.php" class="interestBox" style="width:420px">
			<input type="hidden" name="form" value="content"/>
			<input type=hidden name=do value="1"/>
			Keyword Search <input type=text name="searchtext" value="{$q|escape:'html'}"/>
			<input type=submit value="Search"/>
		</form></li>
	{/if}
{/foreach}

</ul>
</div>

</form>


<div style="margin-top:0px">
	{if $pagesString}
		( Page {$pagesString})
	{/if}
</div>

<br style="clear:both"/>

{if $query_info}
	<p>{$query_info}</p>
{/if}

<script type="text/javascript">{literal}

function checkall(ele,result) {
	for (q=ele.length-1;q>=0;q--) {
		ele[q].checked = result;
	}
}
function justcheck(value) {
	var ele = document.theForm.elements['scope[]'];
	for (q=ele.length-1;q>=0;q--) {
                ele[q].checked = (ele[q].value == value);
        }
	document.theForm.submit();
	return false;
}
function clicked() {
	//document.getElementById("find_button").style.display='';
}
{/literal}</script>



{include file="_std_end.tpl"}
